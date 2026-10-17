<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Audit record of one batch promotion (who moved which students from which term to which term). */
class Promotion extends Model
{
    protected $fillable = ['source_section_term_id', 'target_section_term_id', 'year_level', 'student_ids', 'skipped_ids', 'promoted_by'];

    protected $casts = ['student_ids' => 'array', 'skipped_ids' => 'array'];

    public function sourceTerm()
    {
        return $this->belongsTo(SectionTerm::class, 'source_section_term_id');
    }

    public function targetTerm()
    {
        return $this->belongsTo(SectionTerm::class, 'target_section_term_id');
    }

    public function promoter()
    {
        return $this->belongsTo(User::class, 'promoted_by');
    }
}
