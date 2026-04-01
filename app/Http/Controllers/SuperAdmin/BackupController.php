<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    public function index()
    {
        $files = collect(Storage::files('class-record-backup'))
            ->filter(fn($f) => str_ends_with($f, '.zip'))
            ->map(fn($f) => [
                'name'       => basename($f),
                'size_mb'    => round(Storage::size($f) / 1048576, 2),
                'created_at' => date('Y-m-d H:i:s', Storage::lastModified($f)),
            ])
            ->sortByDesc('created_at')
            ->values();

        return view('admin.backup.index', compact('files'));
    }

    public function run()
    {
        try {
            Artisan::call('backup:run');
            return redirect()->route('admin.backup.index')->with('success', 'Backup created successfully.');
        } catch (\Exception $e) {
            return redirect()->route('admin.backup.index')->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    public function download($filename)
    {
        $path = 'class-record-backup/' . $filename;
        abort_unless(Storage::exists($path), 404);
        return Storage::download($path, $filename);
    }

    public function delete($filename)
    {
        $path = 'class-record-backup/' . $filename;
        abort_unless(Storage::exists($path), 404);
        Storage::delete($path);
        return redirect()->route('admin.backup.index')->with('success', 'Backup deleted.');
    }

    public function restore(Request $request)
    {
        $request->validate(['sql_file' => 'required|file|mimes:sql,txt']);

        try {
            $sql = file_get_contents($request->file('sql_file')->getRealPath());
            DB::unprepared($sql);
            return redirect()->route('admin.backup.index')->with('success', 'Database restored successfully.');
        } catch (\Exception $e) {
            return redirect()->route('admin.backup.index')->with('error', 'Restore failed: ' . $e->getMessage());
        }
    }
}
