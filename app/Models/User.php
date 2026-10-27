<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Concerns\HasFormalName;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, HasFormalName;

    protected $fillable = [
        'name',
        'last_name',
        'first_name',
        'middle_name',
        'academic_rank',
        'credentials',
        'email',
        'username',
        'password',
        'role',
        'status',
        'department_id',
        'program_id',
        'approved_by',
        'approved_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'password' => 'hashed',
    ];

    /** 3 to 30 letters, numbers, dots, underscores or hyphens, starting with a letter or number. */
    public const USERNAME_REGEX = '/^[a-z0-9][a-z0-9._-]{2,29}$/i';

    protected static function booted(): void
    {
        // Accounts created without a username (seeders, factories) get one from the email or the name.
        static::creating(function (User $user) {
            if (blank($user->username)) {
                $user->username = static::uniqueUsername($user->email ?: trim("{$user->first_name}.{$user->last_name}", '.'));
            }
        });
    }

    /** Usernames are stored in lower case, so sign-in does not depend on capital letters. */
    public function setUsernameAttribute(?string $value): void
    {
        $this->attributes['username'] = $value === null ? null : strtolower(trim($value));
    }

    /** Validation rules for a username; pass the account's id when editing it. */
    public static function usernameRules(?int $ignoreId = null): array
    {
        return [
            'required',
            'string',
            function (string $attribute, mixed $value, \Closure $fail) {
                if (!preg_match(self::USERNAME_REGEX, (string) $value)) {
                    $fail('Use 3 to 30 letters, numbers, dots, underscores or hyphens.');
                }
            },
            \Illuminate\Validation\Rule::unique('users', 'username')->ignore($ignoreId),
        ];
    }

    /** A free username built from an email address or a name ("juan.cruz@x.edu" gives "juan.cruz", then "juan.cruz2"). */
    public static function uniqueUsername(string $seed): string
    {
        $base = \Illuminate\Support\Str::of($seed)->before('@')->lower()->replaceMatches('/[^a-z0-9._-]+/', '')->limit(25, '')->toString();
        $base = strlen($base) >= 3 ? $base : 'user';

        $name = $base;
        for ($i = 2; static::where('username', $name)->exists(); $i++) {
            $name = $base . $i;
        }

        return $name;
    }

    // Relationships
    public function assignedSections()
    {
        return $this->hasMany(Section::class, 'teacher_id');
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function approvedUsers()
    {
        return $this->hasMany(User::class, 'approved_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdGradeItems()
    {
        return $this->hasMany(GradeItem::class, 'created_by');
    }

    public function recordedGrades()
    {
        return $this->hasMany(StudentGrade::class, 'recorded_by');
    }

    public function recordedAttendance()
    {
        return $this->hasMany(AttendanceRecord::class, 'recorded_by');
    }

    // Role check helpers
    public function isSuperAdmin()
    {
        return $this->role === 'super_admin';
    }

    public function isDean()
    {
        return $this->role === 'dean';
    }

    public function isTeacher()
    {
        return $this->role === 'teacher';
    }

    public function isProgramHead()
    {
        return $this->role === 'program_head';
    }

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isPending()
    {
        return in_array($this->status, ['pending', 'pending_review'], true);
    }
}
