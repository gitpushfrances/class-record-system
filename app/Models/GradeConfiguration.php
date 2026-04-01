<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradeConfiguration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'section_id',
        'config_json',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'config_json' => 'array',
        'approved_at' => 'datetime',
    ];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getComponents(): array
    {
        return $this->config_json ?? [];
    }

    public function getComponentsByPeriod(string $period): array
    {
        return array_filter($this->getComponents(), fn($c) => $c['period'] === $period);
    }

    public function getWeight(string $key): float
    {
        foreach ($this->getComponents() as $c) {
            if ($c['key'] === $key) return (float) $c['weight'];
        }
        return 0.0;
    }

    public function isValidConfiguration(): bool
    {
        $midterm = array_sum(array_column(
            array_filter($this->getComponents(), fn($c) => $c['period'] === 'midterm'),
            'weight'
        ));
        $final = array_sum(array_column(
            array_filter($this->getComponents(), fn($c) => $c['period'] === 'final'),
            'weight'
        ));
        return abs($midterm - 100) < 0.01 && abs($final - 100) < 0.01;
    }
}
