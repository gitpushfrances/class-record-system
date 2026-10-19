<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Grade items and grade configurations belong to a section term (one semester of a section),
 * not to the section for good. Before this, a section that took the same subject again in a
 * later term showed the old term's items in its Class Record, kept numbering them ("Quiz 6"),
 * refused a new exam, and reused the old weights.
 *
 * Existing rows are assigned to their term by backfill():
 *  - an item goes to the term of the students who have scores on it; an item scored in several
 *    terms is split into one copy per term and each term's scores point to its own copy;
 *  - an item without scores goes to the term that was current when it was created;
 *  - a configuration goes to every term that used it (its items, the subject's assignment,
 *    final grades), one copy per term, so every class keeps the weights it was graded with.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grade_items', function (Blueprint $table) {
            $table->foreignId('section_term_id')->nullable()->after('section_id')->constrained('section_terms')->cascadeOnDelete();
        });

        Schema::table('grade_configurations', function (Blueprint $table) {
            $table->foreignId('section_term_id')->nullable()->after('section_id')->constrained('section_terms')->cascadeOnDelete();
            // The old unique key is the only index on section_id (MySQL dropped the foreign key's own
            // index when the unique key was added), so section_id gets its own index before it goes.
            // It goes before backfill(): a configuration used in several terms is copied once per term.
            $table->index('section_id');
            $table->dropUnique(['section_id', 'subject_id']);
        });

        $this->backfill();

        Schema::table('grade_configurations', function (Blueprint $table) {
            $table->unique(['section_term_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        // One configuration per section and subject again: the oldest row is kept.
        $keep = DB::table('grade_configurations')->selectRaw('MIN(id) AS id')->groupBy('section_id', 'subject_id')->pluck('id');
        DB::table('grade_configurations')->whereNotIn('id', $keep)->delete();

        Schema::table('grade_configurations', function (Blueprint $table) {
            $table->unique(['section_id', 'subject_id']);
            $table->dropForeign(['section_term_id']);
            $table->dropUnique(['section_term_id', 'subject_id']);
            $table->dropColumn('section_term_id');
            $table->dropIndex(['section_id']);
        });

        // Items split by term stay separate rows (each keeps its own scores).
        Schema::table('grade_items', function (Blueprint $table) {
            $table->dropForeign(['section_term_id']);
            $table->dropColumn('section_term_id');
        });
    }

    public function backfill(): void
    {
        foreach (DB::table('grade_items')->whereNull('section_term_id')->orderBy('id')->get() as $item) {
            $terms = DB::table('student_grades')
                ->join('enrollments', 'enrollments.id', '=', 'student_grades.enrollment_id')
                ->where('student_grades.grade_item_id', $item->id)
                ->distinct()
                ->orderBy('enrollments.section_term_id')
                ->pluck('enrollments.section_term_id');

            if ($terms->isEmpty()) {
                $termId = $this->termAt($item->section_id, $item->created_at);
                if ($termId) {
                    DB::table('grade_items')->where('id', $item->id)->update(['section_term_id' => $termId]);
                }
                continue;
            }

            DB::table('grade_items')->where('id', $item->id)->update(['section_term_id' => $terms->first()]);

            foreach ($terms->slice(1) as $termId) {
                $copy = (array) $item;
                unset($copy['id']);
                $copyId = DB::table('grade_items')->insertGetId(['section_term_id' => $termId] + $copy);

                DB::table('student_grades')
                    ->where('grade_item_id', $item->id)
                    ->whereIn('enrollment_id', DB::table('enrollments')->where('section_term_id', $termId)->select('id'))
                    ->update(['grade_item_id' => $copyId]);
            }
        }

        foreach (DB::table('grade_configurations')->whereNull('section_term_id')->orderBy('id')->get() as $config) {
            $termIds = DB::table('section_terms')->where('section_id', $config->section_id)->pluck('id');

            $terms = DB::table('grade_items')
                ->whereIn('section_term_id', $termIds)->where('subject_id', $config->subject_id)
                ->pluck('section_term_id')
                ->merge(DB::table('section_subject_teachers')
                    ->whereIn('section_term_id', $termIds)->where('subject_id', $config->subject_id)
                    ->pluck('section_term_id'))
                ->merge(DB::table('final_grades')
                    ->join('enrollments', 'enrollments.id', '=', 'final_grades.enrollment_id')
                    ->whereIn('enrollments.section_term_id', $termIds)->where('final_grades.subject_id', $config->subject_id)
                    ->pluck('enrollments.section_term_id'))
                ->map(fn ($id) => (int) $id)
                ->unique()->sort()->values();

            if ($terms->isEmpty()) {
                $termId = $this->termAt($config->section_id, $config->created_at);
                if ($termId) {
                    DB::table('grade_configurations')->where('id', $config->id)->update(['section_term_id' => $termId]);
                }
                continue;
            }

            DB::table('grade_configurations')->where('id', $config->id)->update(['section_term_id' => $terms->first()]);

            foreach ($terms->slice(1) as $termId) {
                $copy = (array) $config;
                unset($copy['id']);
                DB::table('grade_configurations')->insert(['section_term_id' => $termId] + $copy);
            }
        }
    }

    /** The section's term that was current at the given time: the last one created at or before it (else its first term). */
    private function termAt(int $sectionId, ?string $createdAt): ?int
    {
        $terms = fn () => DB::table('section_terms')->where('section_id', $sectionId)->whereNull('deleted_at');

        $id = $createdAt
            ? $terms()->where('created_at', '<=', $createdAt)->orderByDesc('created_at')->orderByDesc('id')->value('id')
            : null;

        return $id ?? $terms()->orderBy('created_at')->orderBy('id')->value('id');
    }
};
