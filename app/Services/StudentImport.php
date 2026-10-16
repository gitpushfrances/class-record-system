<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

/**
 * Bulk student import from .xlsx or .csv (first row = column headers).
 * All or nothing: if any row has an error, no student is created.
 * Student numbers that already exist are skipped and reported, never changed.
 */
class StudentImport
{
    public const COLUMNS          = ['student_number', 'last_name', 'first_name', 'middle_name', 'gender', 'year_level', 'student_type', 'email'];
    public const REQUIRED_COLUMNS = ['student_number', 'last_name', 'first_name', 'gender', 'year_level'];
    public const YEAR_LEVELS      = ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year'];
    public const MAX_ROWS         = 1000;
    public const FILE_RULES       = 'required|file|max:2048|mimes:xlsx,csv,txt';

    /** @return array{imported: int, skipped: string[], errors: string[]} */
    public static function run(UploadedFile $file, int $programId): array
    {
        try {
            $sheet = self::read($file);
        } catch (\Throwable $e) {
            return self::failed(['The file could not be read. Save it as .xlsx or .csv from the template.']);
        }

        $header  = array_map(fn ($h) => trim(preg_replace('/[^a-z0-9]+/', '_', strtolower(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), '_'), array_shift($sheet) ?? []);
        $missing = array_diff(self::REQUIRED_COLUMNS, $header);
        if ($missing) {
            return self::failed(['Missing column(s): ' . implode(', ', $missing) . '. Use the template headers.']);
        }

        $rows = [];
        foreach ($sheet as $i => $cells) {
            $values = array_map(fn ($v) => trim((string) $v), $cells);
            if (implode('', $values) === '') {
                continue;
            }
            $row = [];
            foreach (self::COLUMNS as $column) {
                $index        = array_search($column, $header, true);
                $row[$column] = $index === false ? '' : ($values[$index] ?? '');
            }
            $rows[$i + 2] = self::normalize($row);
        }

        if (!$rows) {
            return self::failed(['The file has no student rows.']);
        }
        if (count($rows) > self::MAX_ROWS) {
            return self::failed(['The file has ' . count($rows) . ' rows. Import at most ' . self::MAX_ROWS . ' at a time.']);
        }

        $existing = Student::whereIn('student_number', array_column($rows, 'student_number'))->pluck('student_number')->all();
        $errors   = [];
        $skipped  = [];
        $create   = [];
        $numbers  = [];
        $emails   = [];

        foreach ($rows as $line => $row) {
            $number = $row['student_number'];

            if ($number !== '' && isset($numbers[$number])) {
                $errors[] = "Row {$line}: student number {$number} is repeated (first on row {$numbers[$number]}).";
                continue;
            }
            $numbers[$number] = $line;

            if (in_array($number, $existing, true)) {
                $skipped[] = $number;
                continue;
            }

            $validator = Validator::make($row, [
                'student_number' => ['required', 'regex:/^[0-9]{2}-[0-9]{4}$/'],
                'last_name'      => 'required|string|max:255',
                'first_name'     => 'required|string|max:255',
                'middle_name'    => 'nullable|string|max:255',
                'gender'         => 'required|in:male,female',
                'year_level'     => 'required|in:' . implode(',', self::YEAR_LEVELS),
                'student_type'   => 'required|in:regular,irregular',
                'email'          => 'nullable|email|max:255|unique:students,email',
            ], [
                'student_number.regex' => 'student number must follow the format 00-0000.',
                'gender.in'            => 'gender must be Male or Female.',
                'year_level.in'        => 'year level must be 1st Year to 5th Year.',
                'student_type.in'      => 'student type must be regular or irregular.',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $errors[] = "Row {$line}: {$message}";
                }
                continue;
            }

            if ($row['email'] !== null) {
                if (isset($emails[$row['email']])) {
                    $errors[] = "Row {$line}: email {$row['email']} is repeated (first on row {$emails[$row['email']]}).";
                    continue;
                }
                $emails[$row['email']] = $line;
            }

            $create[] = $row + ['program_id' => $programId, 'status' => 'active'];
        }

        if ($errors) {
            return self::failed($errors);
        }

        DB::transaction(function () use ($create) {
            foreach ($create as $attributes) {
                Student::create($attributes);
            }
        });

        return ['imported' => count($create), 'skipped' => $skipped, 'errors' => []];
    }

    /** Header-only CSV template. */
    public static function template(): string
    {
        return implode(',', self::COLUMNS) . "\r\n";
    }

    /** Flash message for a successful import. */
    public static function summary(array $result): string
    {
        $message = $result['imported'] . ' ' . \Illuminate\Support\Str::plural('student', $result['imported']) . ' imported.';

        if ($result['skipped']) {
            $shown    = array_slice($result['skipped'], 0, 10);
            $more     = count($result['skipped']) - count($shown);
            $message .= ' ' . count($result['skipped']) . ' skipped because the student number already exists: ' . implode(', ', $shown) . ($more > 0 ? " and {$more} more" : '') . '.';
        }

        return $message;
    }

    private static function read(UploadedFile $file): array
    {
        $reader = strtolower($file->getClientOriginalExtension()) === 'xlsx' ? new Xlsx() : new Csv();
        $reader->setReadDataOnly(true);

        return $reader->load($file->getRealPath())->getActiveSheet()->toArray(null, true, false, false);
    }

    private static function normalize(array $row): array
    {
        $gender = strtolower($row['gender']);
        $row['gender'] = match ($gender) {
            'm', 'male'   => 'male',
            'f', 'female' => 'female',
            default       => $gender,
        };

        if (preg_match('/^([1-5])(st|nd|rd|th)?(\s*year)?$/i', $row['year_level'], $m)) {
            $row['year_level'] = self::YEAR_LEVELS[$m[1] - 1];
        }

        $row['student_type'] = strtolower($row['student_type']) ?: 'regular';
        $row['email']        = $row['email'] !== '' ? strtolower($row['email']) : null;
        $row['middle_name']  = $row['middle_name'] !== '' ? $row['middle_name'] : null;

        return $row;
    }

    private static function failed(array $errors): array
    {
        return ['imported' => 0, 'skipped' => [], 'errors' => $errors];
    }
}
