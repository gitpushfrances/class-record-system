# CHANGELOG - Class Record System

## Project Information
**System Name:** Faculty Class Record Management System  
**Tech Stack:** Laravel 10 LTS + MySQL + Blade + Tailwind CSS  
**PHP Version:** 8.4.11  
**Node Version:** 18.20.8  
**Started:** February 8, 2026  

---

## SYSTEM FLOW — FINALIZED ROLE PERMISSIONS

### 👑 Super Admin
- Create & manage Dean accounts
- Review & approve / reject Subject requests from Deans (with timestamp)
- Configure Academic Year & Semester
- Archive old data
- View system-wide stats (Deans, Active Teachers, Students, Subjects, Sections)

### 🎓 Dean
- Approve / reject Teacher registrations (individual or bulk)
- Manage all Faculty — view, deactivate
- Create Sections & assign Teachers
- Add & manage Students (master list owner)
- Tag students as Regular or Irregular
- Request new Subjects (pending until Super Admin approves)
- Approve / reject Teacher grade config change requests
- View faculty reports

### 📝 Teacher
- Self-register (pending until Dean approves)
- View assigned classes only
- Enroll students from master list into their class
- Configure grade weights per class (must = 100%, free on first setup)
- Enter grades — Quizzes, Exams, Projects, Assessments
- Enter attendance (date-based)
- View auto-calculated final grades
- Export grades to Excel / PDF
- Lock grades after deadline
- Request grade config change (Dean approval required if grades already entered)

### Key Rules
- Students — Dean owns master list; Teachers only enroll into classes
- Regular / Irregular — tagged by Dean, read-only label for Teacher
- Grade Config — free on first setup; Dean approval required after grades are entered
- Teacher accounts — self-register, pending until Dean approves
- Dean accounts — created directly by Super Admin
- Subjects — Dean requests per department, Super Admin approves with timestamp. Approved subjects are locked from editing.

---

## PHASE 1: FOUNDATION SETUP ✅ COMPLETED
**Date:** February 8, 2026

- Laravel 10.0 LTS with PHP 8.4 (via `--ignore-platform-req=php`)
- Laravel Breeze v1.26, Spatie Permission v5.11, Maatwebsite Excel v3.1+, Spatie Activity Log v4.8+, Laravel Debugbar v3.9
- Tailwind CSS + Vite configured, PostCSS fixed for Node.js 18 (CommonJS)
- Selected Blade over Livewire, Laravel 10 LTS over Laravel 11

---

## PHASE 2: DATABASE ARCHITECTURE ✅ COMPLETED
**Date:** February 8, 2026

**Custom Tables (11):**
- `students` — master list, soft deletes, `student_type` (regular/irregular)
- `subjects` — catalog with units, status, and approval workflow
- `sections` — class sections with teacher assignments
- `enrollments` — student-to-section pivot
- `grade_configurations` — component weights per section with approval workflow
- `grade_items` — individual quizzes, exams, projects
- `student_grades` — scores per student per item
- `attendance_records` — daily attendance
- `final_grades` — computed grades with locking
- `grade_change_logs` — audit trail
- `academic_periods` — school year and semester config *(added February 27, 2026)*

**users table additions:** `role`, `status` (pending/active/inactive/rejected), `approved_by`, `approved_at`

**Seeders:**
- SuperAdminSeeder — admin, dean, teacher (active), teacher (pending)
- SampleDataSeeder — 5 students, 5 subjects, 1 section (CS101-3A), all students enrolled

**Grading Formula:** `(total_earned / total_possible) × component_weight` → Philippine 1.00–5.00 scale

**Remarks:**
- Migration timestamp conflicts resolved by manual rename to enforce correct execution order
- Duplicate `grade_items` migration deleted
- Empty `student_grades` migration replaced with full schema
- Soft deletes and unique constraints on all main tables

---

## PHASE 3: AUTHENTICATION & AUTHORIZATION ✅ COMPLETED
**Date:** February 8, 2026

- `CheckRole` middleware — validates role against route
- `CheckStatus` middleware — blocks pending/inactive users
- Teacher self-registration sets status to `pending`
- Role-based login redirects use `$user->role ===` (not Spatie `hasRole()`)
- Route groups: `/admin/*`, `/dean/*`, `/teacher/*`

---

## PHASE 4: ACADEMIC STRUCTURE MANAGEMENT ✅ COMPLETED
**Date:** February 16–27, 2026

### Super Admin ✅
- Subject approval — review pending requests from Deans, approve/reject with reason and timestamp
- Dean Management CRUD (create, edit, activate/deactivate)
- Academic Period — add school year + semester, set active, delete inactive
- Dashboard stats — Deans, Active Teachers, Students, Subjects, Sections
- Navigation: Dashboard → Deans → Subjects → Academic Period

### Dean ✅
- Teacher Approval — approve/reject (reject sets `status = rejected`, does NOT delete)
- Section CRUD
- Student Master List CRUD — add, edit, tag Regular/Irregular
- Enrollment Management — assign students to sections
- Subject Requests — create, view status, edit/cancel pending requests
- Navigation: Dashboard → Pending Teachers → Sections → Enrollments → Students → Subjects

### Fixes Applied (February 27, 2026)
- Removed Teacher Approval and Student Management from Super Admin — moved to Dean
- `StudentController` moved from `SuperAdmin/` → `Dean/`
- `year_level` validation fixed to match enum (`1st Year`, `2nd Year`, etc.)
- `reject()` fixed — was `$user->delete()`, now `$user->update(['status' => 'rejected'])`
- `rejected` status added to users enum
- `student_type` field added to students migration
- Route model binding conflict fixed — `{user}` → `{dean}` parameter in `UserController`
- `hasRole()` replaced with `$user->role ===` across routes and redirects
- `navigation.blade.php` — removed dead routes `admin.teachers.pending`, `admin.students.index`
- `DashboardController` — removed `$pendingTeachers` query (no longer Super Admin's concern)
- `UserController` built from scratch (was empty)
- `AcademicPeriodController` + model + migration + view built from scratch

---

## PHASE 5: GRADING SYSTEM ✅ COMPLETED
**Date:** February 17, 2026

- Grade Configuration — weights per section, must sum to 100%, warning banner blocks entry if not set
- Grade Item Management — create items per component with name, max_score, date_given
- Score Entry — per grade item per student, auto-saves, logs changes to `grade_change_logs`
- Attendance — daily per section, Present/Absent/Late/Excused, Mark All quick actions
- Final Grades — live display (no button needed), Save writes to DB, Lock All freezes permanently
- Philippine 1.00–5.00 scale via `FinalGrade::convertToNumericalGrade()`

**Remarks:**
- `DB::transaction` removed from `computeGrades()` loop — replaced with per-enrollment try/catch
- `requireConfig()` helper redirects teacher to config if no grade config set
- Route name conflict `teacher.teacher.classes.record` fixed by removing redundant prefix

---

## PHASE 6: CLASS RECORD INTERFACE ✅ COMPLETED
**Date:** February 17, 2026

- DepEd-style spreadsheet — students as rows, grade items as columns
- Color-coded components: blue=quiz, purple=exam, green=project, orange=assessment, teal=attendance
- Frozen columns — Student No. and Name sticky on horizontal scroll
- Scores in `45/50` format, attendance in `18/20` format
- Class averages footer — per item, per component, overall average, Pass/Fail count
- Live data — always current, no button needed
- Locked grades show lock icon (Font Awesome)

**Remarks:**
- `$gradeMap = $enrollment->studentGrades->keyBy('grade_item_id')` for O(1) score lookup
- `FinalGrade::convertToNumericalGrade()` called with full namespace in Blade to avoid class-not-found error

---

## PHASE 7: EXCEL EXPORT ✅ COMPLETED
**Date:** March 4, 2026

- `app/Exports/ClassRecordExport.php` — DepEd-format export using Maatwebsite Excel v3.1
- Export route added: `GET /teacher/classes/{section}/record/export` → `teacher.classes.record.export`
- `export()` method added to `ClassController` — reuses `calculateComponentScores()`, same logic as live view
- Export button added to Class Record view (green, beside Print button)
- Filename format: `{SubjectCode}_{SectionName}_{Semester}_{AY}.xlsx`
- File structure: rows 1–4 header block, row 5 empty, row 6 component group headers with weights, row 7 item sub-headers, rows 8+ student data, last row class averages
- Color-coded headers: dark navy group row, medium blue sub-header row, light yellow average row
- Auto-width columns D onwards, fixed widths for No./Student No./Name columns
- Borders applied to entire data range

**Remarks:**
- `app/Exports/` directory created from scratch — did not exist prior
- First data row (row 8) was inheriting sub-header dark styling — fixed by explicitly resetting font/fill for data rows 8 to lastRow-1
- Attendance `—` was bleeding into weighted score cell — fixed by splitting into two separate cells: `$attendDisplay` and `$attendWeighted`
- Middle name was dropped from export name column — fixed with `substr($middle_name, 0, 1) . '.'` inline
- Duplicate `font` key in `WithStyles` sub-header block removed — PHP silently uses last key, caused style not applying correctly

---

## BUG FIXES & PATCHES — March 4, 2026

### SectionController — Validation Mismatch (CRITICAL)
- `store()` and `update()` were validating `year_level` as `in:1,2,3,4` and `semester` as `in:1,2`
- DB enum expects `'1st Year'`, `'2nd Year'` etc. and `'1st Semester'`, `'2nd Semester'`, `'Summer'`
- Fixed: validation rules updated to match enum values exactly

### SectionController — Missing `show()` Method
- Route `dean.sections.show` existed but `show()` method was never implemented
- Fixed: `show()` added, loads subject/teacher/enrollments, returns `dean.sections.show` view

### Section Create Form — Wrong Field Name
- Form was posting `name` but DB column and validation expect `section_name`
- Fixed: input `name` attribute and `old()` key updated to `section_name`

### Dean Navigation — Missing Students Link
- `dean.students.*` routes and `StudentController` existed but no nav link
- Fixed: Students link added to both desktop and mobile blocks in `layouts/navigation.blade.php`

### Student Views — Entirely Missing
- `resources/views/dean/students/` folder did not exist
- Created from scratch: `index.blade.php`, `create.blade.php`, `edit.blade.php`

### Section Show View — Missing
- `resources/views/dean/sections/show.blade.php` did not exist
- Created: shows section details + enrolled students table with link to manage enrollments

### Student Model — Missing `student_type` in `$fillable`
- Was not in `$fillable` — silently not saving on create/update
- Fixed: `student_type` added to `Student::$fillable`

---

## PHASE 8: SIDEBAR NAVIGATION ✅ COMPLETED
**Date:** March 4, 2026

- `resources/views/layouts/partials/sidebar.blade.php` — unified layout for all three roles
- `resources/views/layouts/partials/sidebar-link.blade.php` — reusable nav link partial with Font Awesome icons, active state highlight, and hover styles
- `app/View/Components/SidebarLayout.php` — Blade component class
- Fonts: `Fraunces` + `DM Sans`, Palette: `#1c1814` sidebar, `#c8a97e` accent, `#f0dfc0` text
- Font Awesome 6.5.1 via CDN
- Role badge: yellow pill = Super Admin, green pill = Dean, sand pill = Teacher
- Mobile: off-canvas sidebar with dark overlay + hamburger trigger in top bar
- Pure CSS + vanilla JS — no Alpine.js dependency
- All teacher and dean/admin views migrated to unified layout

**Remarks:**
- Alpine.js `:class` binding was not applying `lg:translate-x-0` on page load — switched entirely to CSS `position: sticky`
- `teacher.classes.index` route did not exist — fixed to point to `teacher.dashboard`
- Two layout systems (Breeze `app.blade.php` + custom `teacher.blade.php`) consolidated into one

---

## PHASE 4 REVISION: SUBJECT APPROVAL WORKFLOW ✅ COMPLETED
**Date:** March 12, 2026

### What changed
- Subject ownership moved: Super Admin full CRUD → Dean requests, Super Admin approves
- Matches real Philippine HEI workflow — department head requests, registrar/admin approves

### Database
- Migration `2026_03_12_070323_add_approval_fields_to_subjects_table` added to `subjects` table:
  - `department` (varchar) — Dean's department
  - `requested_by` (FK → users) — Dean who submitted the request
  - `approved_by` (FK → users) — Super Admin who actioned it
  - `approved_at` (timestamp) — when actioned
  - `rejected_reason` (text, nullable) — populated on reject
  - `status` enum changed: `active/inactive` → `pending/approved/rejected`
- Existing seeded subjects (5 rows, `active` status) mapped to `approved` during migration

### Controllers
- `SuperAdmin/SubjectController` — gutted to approval-only: `index`, `approve`, `reject`
- `Dean/SubjectController` — new: `index`, `create`, `store`, `edit`, `update`, `destroy`
  - Dean can only edit/delete their own pending subjects
  - Approved/rejected subjects are locked from Dean edits

### Routes
- `admin.subjects.*` resource removed — replaced with `index`, `approve`, `reject` only
- `dean.subjects.*` resource added under Dean route group

### Views
- `resources/views/dean/subjects/index.blade.php` — subject list with status badges (Pending/Approved/Rejected)
- `resources/views/dean/subjects/create.blade.php` — request form with SweetAlert2 preview before submit
- `resources/views/dean/subjects/edit.blade.php` — edit pending requests only
- `resources/views/admin/subjects/index.blade.php` — two-section layout: Pending (with badge count) + Approved catalog
- SweetAlert2: submit confirmation (Dean), approve confirmation (Admin), reject with required reason textarea (Admin)

### Sidebar
- Dean sidebar: `Subjects` link added pointing to `dean.subjects.index`
- Super Admin sidebar: `Subjects` link retained, now points to approval view

### Remarks
- MySQL strict mode blocked direct enum-to-enum change with existing data — resolved by converting to VARCHAR first, updating data, then setting final enum
- `department` column was partially added in a failed migration run — cleaned manually via tinker
- Migration marked complete manually after partial execution, then re-run cleanly

---

## QA FIXES & SCHEMA MIGRATION — March 15, 2026

### Background
QA identified 7 issues after a major architectural restructure that changed sections from a flat model to a two-layer model (`sections` → `section_terms` → `enrollments`). Many controllers and views were still referencing the old schema.

### Architecture Change (Previously Applied)
Sections restructured from tightly-coupled to a proper two-layer model:
- `sections` — persistent group (e.g. BSCS 3-A exists across all semesters)
- `section_terms` — per-semester instance, holds `adviser_id` + `status`
- `enrollments` — students enrolled per term, not permanently per section
- `departments` and `programs` tables added
- `sections` now tied to `program_id` instead of plain text
- `section_letter` changed to free-text input (supports "A", "Block 1", "Rizal", etc.)

### QA Issues Resolved

**Issue 1 — Section field rejecting values like 3A, 2A**
- Root cause: old `in:A,B,C,D` validation rule
- Status: Already fixed in prior session — `required|string|max:50`

**Issue 2 — No Add button on Sections page**
- Status: Button was already present — confirmed via grep

**Issue 3 — No Add Student button on Students tab**
- Status: Button was already present — confirmed via grep

**Issue 4 — Subject request Review & Submit button not working**
- Root cause: `dean/subjects/create.blade.php` was using `@extends('layouts.app')` instead of `<x-sidebar-layout>` — layout was broken, SweetAlert2 script not loading
- Fixed: View rewritten to use `<x-sidebar-layout>`, `@section`/`@endsection` removed, validation error display added per field

**Issue 5 — Enrollments: no add option, students only enrollable in one subject**
- Root cause: `EnrollmentController` was still querying `academic_year` and `semester` directly on `sections` — those columns moved to `section_terms`
- Fixed: `EnrollmentController` fully rewritten to use `SectionTerm` model
- Fixed: Both enrollment views (`index.blade.php`, `show.blade.php`) rewritten — old schema references (`$section->subject`, `$section->teacher`, `$section->semester`) replaced with `$term->section->program`, `$term->adviser`, `$term->semester`

**Issue 6 — Academic Period: no actions after adding**
- Root cause: `admin.academic.index` view did not exist — controller and routes existed but view was never created
- Fixed: `resources/views/admin/academic/index.blade.php` created from scratch with Add form, Set Active (PATCH), and Delete actions

**Issue 7 — Attendance: all students saved as Present**
- Root cause: `authorizeSection()` in `AttendanceController` was checking `$section->teacher_id` which no longer exists — was causing 403 or silent failures
- Fixed: `AttendanceController` rewritten — authorization now checks `section_terms.adviser_id`, enrollments loaded via active `section_term`

### Additional Schema Migration Fixes (same session)

**Teacher DashboardController**
- Was querying `Section::where('teacher_id', $teacher->id)` — `teacher_id` removed from `sections`
- Fixed: rewritten to query `SectionTerm::where('adviser_id', $teacher->id)->where('status', 'active')`
- Teacher dashboard view rewritten to use `$sectionTerms` — removed references to `$class->subject`, `$class->section_name`, `$class->semester`

**ClassController**
- Three `abort_if` checks still used `$section->teacher_id`
- `export()` filename still used `$section->subject->code` and `$section->section_name`
- Fixed: all three authorization checks updated to use active `section_term->adviser_id`
- Fixed: export filename now uses `$section->program->code`, `$section->year_number`, `$section->section_letter`

**GradeController**
- `authorizeSection()` still checked `$section->teacher_id`
- Four methods used `$section->enrollments` directly — enrollments now live under `section_terms`
- Fixed: full controller rewritten — all enrollment queries now scoped to active `section_term`
- Fixed: `authorizeSection()` updated to check `section_term->adviser_id`

**Section Model**
- Missing `gradeItems()` and `gradeConfiguration()` relationships
- Fixed: both `hasMany` and `hasOne` relationships added

**All Teacher Views**
- `attendance/summary.blade.php`, `classes/show.blade.php`, `classes/record.blade.php`, `grades/config.blade.php`, `grades/final.blade.php`, `grades/items.blade.php`, `grades/scores.blade.php`, `dashboard.blade.php`
- All references to `$section->subject->code`, `$section->section_name`, `$section->semester`, `$section->academic_year`, `$section->enrollments` replaced with correct `$section->program->code`, `$section->year_number`, `$section->section_letter`, `$currentTerm->semester`, `$currentTerm->enrollments`
- `@section('title')` directives removed from all views using `<x-sidebar-layout>` (directive does nothing in component layouts)
- `dashboard.blade.php` was duplicated to 195 lines from a failed write — overwritten clean (~50 lines)

### Grade Calculation Fix — Remarks Logic

**Problem:** `remarks` was computed as `$finalPercentage >= 75 ? 'passed' : 'failed'` — students with incomplete data (e.g. only quiz and attendance entered mid-semester) were showing as Failed because empty components scored 0

**Fix 1 — Correct passing threshold:**
- Changed to `$numerical <= 3.00 ? 'passed' : 'failed'`
- Matches Philippine HEI grading — numerical grade ≤ 3.00 = Passed, 5.00 = Failed
- Applied in all four locations: `GradeController@finalGrades`, `GradeController@computeGrades`, `ClassController@record`, `ClassController@export`

**Fix 2 — Weighted rescaling for incomplete data:**
- `calculateComponentScores()` was giving 0 to components with no data, dragging the total down
- Rewritten to track `$activeWeight` — only components with actual data contribute
- If `$activeWeight < 100`, all scores are rescaled by factor `100 / $activeWeight` so the grade reflects only what has been entered
- Method definition line was corrupted to `;` by a failed sed — full `GradeController` rewritten clean

---

## QA FIXES & PATCHES — March 23, 2026

### Teacher-Side Student Enrollment (NEW FEATURE)
- `enrollStudent()` and `unenrollStudent()` methods added to `Teacher\ClassController`
- `Student` and `Enrollment` models imported into `ClassController`
- Routes `teacher.classes.enroll` (POST) and `teacher.classes.unenroll` (DELETE) were already in `web.php` but had no controller methods — now implemented
- `$availableStudents` variable added to `ClassController@show` — queries active students not yet enrolled in the current section term, ordered by last name
- `resources/views/teacher/classes/show.blade.php` updated:
  - `+ Add Student` button added to Enrolled Students header (only shown when active term exists)
  - Modal with live search — filters by name or student number as user types
  - Already-enrolled students excluded from the modal list
  - Each student row is a form that POSTs directly on click
  - `Remove` button added per enrolled student row with DELETE form
  - `Action` column added to the enrolled students table
  - Success/error flash messages displayed inside modal

### Enrollment Status Fix
- `enrollStudent()` was inserting `status = 'active'` into `enrollments` table
- DB enum only accepts `'enrolled'`, `'dropped'`, `'completed'` — `'active'` caused `SQLSTATE[01000] Data truncated` error
- Fixed: changed insert value to `'enrolled'`

### Emoji Replacement — Font Awesome Icons
- All hardcoded emojis replaced with Font Awesome 6.5.1 icon classes across 9 view files
- Replacement done via PHP script using hex byte sequences to bypass Git Bash UTF-8 mangling
- Files cleaned:
  - `resources/views/teacher/classes/show.blade.php` — gear, pen, calendar, graduation cap, chart bar, warning triangle
  - `resources/views/teacher/classes/record.blade.php` — file-excel, print, lock
  - `resources/views/teacher/grades/final.blade.php` — floppy-disk, lock
  - `resources/views/teacher/grades/items.blade.php` — lock
  - `resources/views/teacher/grades/scores.blade.php` — lock
  - `resources/views/teacher/grades/config.blade.php` — circle-check
  - `resources/views/teacher/attendance/index.blade.php` — circle-check, circle-xmark, triangle-exclamation, circle-minus
  - `resources/views/admin/subjects/index.blade.php` — circle-check, circle-xmark
  - `resources/views/dean/subjects/index.blade.php` — circle-check, circle-xmark
  - `resources/views/dean/subjects/create.blade.php` — clock
  - `resources/views/dean/sections/index.blade.php` — xmark

### Student Delete Confirmation Modal (Dean Side)
- Replaced plain browser `confirm()` dialog on student remove button with a styled modal
- Modal displays the student's full name before confirming deletion
- Shows warning: "This will remove them from all enrolled classes permanently."
- Cancel button closes modal without action
- Confirm button submits the hidden DELETE form for that student
- `resources/views/dean/students/index.blade.php` updated — button now calls `confirmDelete(id, name)` JS function

### Orphaned Enrollment Fix — Cascading Delete
- Root cause: `Dean\StudentController@destroy` was only calling `$student->delete()` — did not remove the student's enrollment records
- When a student was deleted from the master list while enrolled in a class, their enrollment record remained with a null student relationship, causing `N/A` to display on the teacher's class view
- Fixed: `StudentController@destroy` now calls `Enrollment::where('student_id', $student->id)->delete()` before deleting the student
- Orphaned enrollment (ID: 1, student_id: 1) manually cleaned via tinker after fix was applied
- `\App\Models\Enrollment` import added to `Dean\StudentController`

---

## QA FIXES & PATCHES — August 5, 2026

### Grade Configuration Page — Row Alignment & Validation
- `teacher/grades/config.blade.php` rebuilt onto a single fixed CSS grid so preset and custom components render identically (label, weight, %, action icons no longer drift based on row type)
- Trash icon added to every row, including presets — previously only custom "Others" rows had a delete action
- Blank-name guard added in two layers: confirming a component with an empty name shows an inline error and blocks collapse into a saved row; form submit re-validates and blocks save if any component still has a blank name
- Confirmed backend already enforced `components.*.label => required`, so no invalid data had reached the database — this was purely a missing browser-side feedback gap

### Admin Accounts — Unified Index & Duplicate Banner Fix
- Root cause: `SuperAdmin\UserController@index` only ever queried `role = dean`, so Program Head and Teacher accounts created via `storeProgramHead()` / `storeTeacher()` succeeded but never appeared in the list
- Fixed: `index()` now queries all managed roles (`dean`, `program_head`, `teacher`) with an optional `?role=` filter; `admin.deans.index` view reframed as a general "Accounts" page with role badges and filter tabs
- Duplicate "success" banner fixed — was rendering once globally in `layouts/app.blade.php` and again locally in the index view; local copy removed
- New unified `createAccount()` / `storeAccount()` methods added with a role dropdown, replacing the need for per-role create screens going forward (old `createProgramHead`/`storeProgramHead`/`createTeacher`/`storeTeacher` routes and methods left in place, untouched, for backward compatibility)
- `edit()`, `update()`, `deactivate()`, `activate()` updated to authorize against all managed roles instead of `isDean()` only — previously non-Dean accounts would 403 on these actions

### Dean Subjects Page — RelationNotFoundException Fix
- `Dean\SubjectController@index` called `Subject::with('teacher')` — no singular `teacher()` relationship exists on `Subject`; real relationship is `teachers()` (plural, many-to-many via `section_subject_teachers`)
- Fixed: controller updated to `->with('teachers')`

### Teacher Assignment — Moved from Subject-Global to Section-Term-Scoped (Schema Change)
- Root cause: Dean's Subjects page had a leftover "Assign Teacher" modal writing to `subjects.teacher_id` — a column that never existed in the schema; real teacher assignment lives in the `section_subject_teachers` pivot, keyed by section
- Migration `2026_03_23_180812_add_teacher_id_to_subjects_table.php` updated in place (no new migration file) — pivot table `section_subject_teachers` now keys on `section_term_id` instead of `section_id`, matching how `adviser_id` is already scoped per `SectionTerm` rather than per `Section`
- Unique constraint changed to `[section_term_id, subject_id]` — one teacher per subject per term; reassigning a teacher replaces the existing pivot row instead of creating a duplicate
- `Section::subjects()` and `Section::assignedTeachers()` removed (confirmed unused elsewhere via grep)
- `SectionTerm::subjects()` added (belongsToMany Subject through the pivot, mirrors `adviser()`)
- `Subject::sectionTerms()` added; `Subject::teachers()` and `Subject::sections()` rewritten to join through `section_term_id`
- `Dean\SectionController` gains `attachSubject()` and `changeSubjectTeacher()`, both scoped to the section's active term
- Broken `assign_teacher` branch removed from `Dean\SubjectController@update()`
- Applied via `migrate:fresh --seed` (no production data to preserve)

### Dean Sections — Subjects & Teachers Management UI
- New "Subjects & Teachers" panel added to section details: lists subjects attached to the section's active term with assigned teacher, inline reassignment dropdown, and an "Add Subject" form (subject + teacher, filtered to approved subjects not yet attached to the term)
- Section cards on the Sections index converted from static display into a click-to-open modal (matches the existing "Change Adviser" modal pattern already used on the same page) — modal shows section details, current term/adviser, the Subjects & Teachers panel, and Edit/Delete actions, replacing the earlier plan of a separate details page
- `Dean\SectionController@index` and `@show` updated to eager-load `terms.subjects` and pass `$teachers` / available-subjects lists to the view

### Subject Requests Page — Assigned Teacher Visibility
- Dean's flat Subject Requests list now shows the assigned teacher name(s) directly per subject (deduplicated across sections), instead of a static "Manage via Sections page" placeholder — removes the need to check each section individually to see who's teaching a given subject
- `Dean\SubjectController@index` updated to eager-load `sectionTerms.section.program` for this display

---

## UI/UX REMINDER — Reusable Confirmation Component (Planned)

- Delete/remove actions across the app currently use a mix of raw browser `confirm()` and inline SweetAlert2 blocks copy-pasted per view (Dean student delete, Dean subject cancel, Dean assignment remove, etc.)
- Plan: build a single reusable `<x-confirm-form>` Blade component (action, method, title, description as props) backed by one global delegated-event script in the shared layout, then migrate every existing delete button to it in one consistent pass
- Deferred — noted here so it isn't lost

---

### Dean Assignments Page — Schema Fix + Confirmation Modal
- `Dean\AssignmentController` (a previously-undiscovered second interface to teacher assignment, separate from the Sections modal) broke after the `section_id` → `section_term_id` pivot change — rewritten to resolve each section's active term and enforce the same one-teacher-per-subject-per-term rule as the Sections modal
- Native browser `confirm()` on assignment removal replaced with a SweetAlert2 modal naming the specific assignment being removed, matching the pattern already used elsewhere in the app
- Noted in changelog: a reusable `<x-confirm-form>` component + one global delegated-event script is planned to replace all remaining raw `confirm()` / copy-pasted SweetAlert2 blocks across the app in one consistent pass — deferred, not yet built

### Teacher Self-Registration — Re-enabled with Admin Review Queue
- `Auth\RegisteredUserController@store` was a stub that saved nothing and redirected with "Self-registration is disabled" — rebuilt to actually create an account
- New `pending_review` status added (distinct from the existing `pending`, which continues to mean "role assigned, account created, not yet activated") — self-registered accounts get `role = null`, `status = pending_review`
- `role` column on `users` changed to nullable (previously defaulted to `teacher`) to support unassigned pending requests
- Super Admin's Accounts page gains a "Pending Requests" tab (with count badge) showing self-registered signups; clicking Approve lets the Admin assign a role (Dean/Program Head/Teacher) and activates the account in one step; Reject sets status to `rejected`, matching the existing rejection pattern
- No ID photo upload in this pass — deferred per client decision (no file storage package set up yet)

### Dean Students Page — Icon Actions, Duplicate Banner, Hard Delete
- Duplicate success banner removed (same root cause as the earlier Accounts/Sections/Final Grades instances — local `session('success')` block stacked on the global layout one)
- Edit/Remove text links converted to pencil/trash icon buttons with visible contrast against the white table background
- `SoftDeletes` removed from the `Student` model and the `deleted_at` column dropped from the `students` migration — previously, removing a student only soft-deleted them, so re-adding a student with the same `student_number` failed with a false "already taken" error since the old row still occupied it in the database
- Existing delete confirmation modal (built March 23) kept as-is as the safety net, replacing the need for soft-delete — client confirmed this is sufficient protection against accidental removal

### Attendance Now Counted Toward Final Grades (Previously Silently Ignored)
- Root cause: grade computation only ever read from `student_grades` (manually entered scores per grade item); the "Attendance" component in grade configs required a teacher to manually create a gradeable item and type in scores, while real attendance was tracked separately in `attendance_records` and never connected to grade math — attendance components silently contributed 0% and had their weight redistributed to other components via the existing rescaling logic
- Added `midterm_cutoff_date` to `section_terms` — teacher sets a single date per class per term (editable) from the Final Grades page; attendance records on or before this date count toward Midterm, after it count toward Final
- Attendance components (matched by key `attendance` / `attendance_f`) are now auto-computed: Present and Excused = full credit, Late = half credit, Absent = no credit; rate is applied against the component's configured weight
- Until a cutoff date is set, attendance components are treated as "not yet active" (same rescaling path as any other empty component) rather than guessing a boundary or silently scoring 0
- New route/method `Teacher\GradeController@updateCutoff`; `calculateComponentScores()` and `calculatePeriodScores()` both updated to branch on attendance components before falling back to the manual-score path

### Final Grades Page — Full UI Overhaul
- Page previously used dark-sidebar text colors (cream/gold) on the light main content area, rendering almost the entire table near-invisible
- Rewritten with proper light-background contrast throughout; duplicate success banner removed (same pattern as above); added the new Midterm Cutoff Date form with a visible warning when unset

### Program Head Login — 404 on Login, 419 on Logout (CRITICAL)
- Root cause: `AuthenticatedSessionController@store`'s role-based redirect after login checked Super Admin, Dean, and Teacher only — no branch existed for Program Head, so it fell through to a hardcoded fallback (`RouteServiceProvider::HOME` = `/dashboard`), a route that doesn't exist anywhere in the app, producing a 404 on every Program Head login regardless of device or session state
- A separate, correct redirect for Program Head already existed on the `/` catch-all route, but was never reached since the broken `/dashboard` redirect failed first
- The downstream 419 on logout was a side effect of the broken login leaving the session in an inconsistent state
- Fixed: added the missing `isProgramHead()` branch pointing to `/program-head/dashboard`; fallback changed from the nonexistent `/dashboard` to `/login` so any future unhandled role degrades safely instead of 404ing

### Enrollment Re-Add Failure — Soft Delete Removed (Same Pattern as Students)
- Same root cause as the earlier Student fix: `Enrollment` used `SoftDeletes`, so removing a student from a section and re-enrolling them into the same section/term hit the database's real unique constraint (`[student_id, section_term_id]`) against the old soft-deleted row, even though the app's own duplicate-check query (which excludes soft-deleted rows by default) said it was fine — resulting in a `UniqueConstraintViolationException`
- `SoftDeletes` removed from the `Enrollment` model; `deleted_at` column removed from the migration that actually defines the live `enrollments` table (`2026_03_15_075259_update_enrollments_table.php`, which fully recreates the table — the original `2026_02_08_071022` migration's `softDeletes()` call is dead code on a fresh install)
- Existing confirmation prompts on both Dean's and Teacher's removal actions confirmed adequate as the safety net in place of soft-delete

### Dean Enrollments Page — UI Overhaul + Confirmation Modal
- Duplicate success banner removed (same recurring pattern — local `session('success')` block stacked on the global layout one)
- Header restyled to match the rest of the app (Fraunces serif title, proper "← Back" link placement)
- "Remove" text link converted to a trash icon button matching the Students page style
- Native browser `confirm()` on removal replaced with a SweetAlert2 modal naming the specific student being removed, consistent with the Assignments and Students pages

---

## QA FIXES & PATCHES — August 13, 2026

### Add Student — Dean Master List (CRITICAL, client-reported)
- Client reported "Add Student" button non-functional on their device; root cause traced through source inspection since client device was unreachable for live debugging
- `Dean\StudentController@store()` — `status` was never explicitly set on new students; relied entirely on the migration's DB-level `default('active')`. Confirmed via tinker that this default does apply correctly at the database layer, so this was not an active data-corruption bug (`whereNull('status')->count()` returned 0 across all existing students) — but the app's own `Teacher\ClassController@show()` query (`where('status', 'active')`) depends on that value being reliably set, so leaving it implicit was fragile
- Fixed: `status = 'active'` now set explicitly in the validated array before `Student::create()`, rather than relying on the DB default

### Teacher Class View — Dead Event Listener on Page Load (CRITICAL)
- `resources/views/teacher/classes/show.blade.php` had its `<script>` block (containing `document.getElementById('confirmRemoveBtn').addEventListener(...)`) placed *before* the `#removeModal` HTML in the DOM, so the listener attach ran against a `null` element on every page load, throwing a silent `TypeError` in console and killing the Remove-confirmation flow
- Fixed: entire inline script wrapped in `DOMContentLoaded`; all previously-global functions (`openEnrollModal`, `closeEnrollModal`, `filterStudents`, `openRemoveModal`, `closeRemoveModal`) reattached to `window` explicitly so existing inline `onclick="..."` HTML attributes still resolve them correctly

### Dean Add Student Form — No Fallback if SweetAlert2 CDN Fails
- `confirmSubmit()` in `resources/views/dean/students/create.blade.php` called `Swal.fire(...)` with no guard — if `cdn.jsdelivr.net` failed to load (blocked, slow, or offline network), `Swal` would be `undefined` and the Add Student button would silently do nothing, with no visible error to a non-technical user
- Fixed: added `typeof Swal === 'undefined'` check with a native `confirm()` fallback so the button still functions if the CDN is unreachable
- Noted as a recurring pattern — same CDN-dependency risk exists on every other SweetAlert2-based confirm dialog across the app (Sections, Subjects, Assignments, Enrollments); only this instance was patched today. The already-planned `<x-confirm-form>` component (see UI/UX Reminder above) would resolve this app-wide in one pass instead of repeated one-off patches

### Verification
- Confirmed via `php artisan tinker` that explicit `status = 'active'` assignment behaves correctly end-to-end
- Confirmed via browser test (DevTools Console open) that both Dean-side Add Student and Teacher-side Add/Remove Student flows complete with zero console errors
- Confirmed SweetAlert2 loads successfully in current environment (Network tab, 200 response) — fallback path is written but not yet exercised by a real CDN failure

### Known Gap Identified (not fixed — flagged for Phase 9/10 planning)
- No UI path exists for a Dean to change a student's `status` after creation (`active` → `inactive`/`graduated`/`dropped`). Schema supports it (enum already defines all four values); neither `create.blade.php` nor `edit.blade.php` expose a status field. Students who graduate or drop out will remain permanently eligible for class enrollment under the current `where('status','active')` query. Needs a deliberate scope decision before capstone defense.

### Dean Enrollments — Duplicate Active Term Bug (CRITICAL, client-reported)
- Client reported students enrolled by the Dean were not appearing on the Teacher's class roster
- Root cause: `Dean\EnrollmentController@store()` used `SectionTerm::firstOrCreate()` keyed on the Dean's freely-typed `academic_year`/`semester` values. Any mismatch against the section's actual active term (extra whitespace, different formatting) silently created a **second active `SectionTerm`** for the same section, and the enrollment landed on that duplicate — a term the Teacher's `where('status','active')->first()` query wasn't necessarily returning
- Fixed: `store()` now resolves the section's existing active term directly (`$section->terms()->where('status','active')->first()`) instead of creating one; the Dean's enrollment form no longer sends `academic_year`/`semester` fields at all
- Confirmed via tinker: zero existing sections had duplicate active terms at time of fix (no historical data damage)
- Confirmed via browser test: Dean-enrolled student now appears immediately on Teacher's roster with no separate action needed

### SweetAlert2 CDN Fallback — App-Wide Sweep
- Following the Add Student CDN-fallback fix (see above), audited all remaining Dean-side confirm dialogs for the same unguarded `Swal.fire()` gap
- Patched: `dean/assignments/index.blade.php` (remove assignment), `dean/enrollments/show.blade.php` (remove enrollment), `dean/sections/create.blade.php` (confirm section creation), `dean/subjects/create.blade.php` (incomplete-fields warning + confirm request), `dean/subjects/index.blade.php` (cancel request) — each now falls back to a native `confirm()` if SweetAlert2 fails to load
- Verified via `grep -rl "Swal.fire" resources/views/dean/` that all instances are accounted for
- `<x-confirm-form>` reusable component (already noted as planned above) still not built — this was a one-off patch across six files, not a structural fix; remains recommended for a future session to prevent this pattern recurring on any new confirm dialog added later

### Data Note — `dean/subjects/index.blade.php` still uses legacy layout
- Uses `@extends('layouts.app')` instead of `<x-sidebar-layout>` — the same root cause documented under Issue 4 (March 15) for `subjects/create.blade.php`, which was fixed at the time but this sibling view was apparently missed
- Not fixed this session — flagged for follow-up; unclear whether this currently causes any visible/functional issue since page has not been visually inspected against the correct layout

---
f
## QA FIXES & PATCHES — August 17, 2026

### Grading System — Section-Level to Subject-Level Refactor (MAJOR)
- Root cause: grades (`grade_configurations`, `grade_items`, `final_grades`) were scoped only to `section_id`, but a section takes multiple subjects, often with different teachers — the schema had no way to keep one subject's grades separate from another's within the same section
- Migrations added: `subject_id` added to `grade_configurations` (with new composite unique `[section_id, subject_id]`), `grade_items`, and `final_grades` (with new composite unique `[enrollment_id, subject_id]`, replacing the old single-column unique on `enrollment_id`)
- `Section` model gained `gradeConfigurationFor($subjectId)`, `gradeItemsFor($subjectId)`, and `gradeConfigurations()` (hasMany); the old singular `gradeConfiguration()` (hasOne) relation kept in place but deprecated, in case anything else still calls it
- `Teacher\GradeController` and `Teacher\ClassController` — every method (`config`, `storeConfig`, `items`, `storeItem`, `destroyItem`, `scores`, `storeScores`, `finalGrades`, `updateCutoff`, `computeGrades`, `lockGrades`, `record`, `export`) now takes `Subject $subject` alongside `Section $section`, with every query, redirect, and view scoped accordingly
- All `teacher.grades.*` and `teacher.classes.record`/`export` routes updated to include `{subject}` in the URL
- Access model tightened: being a section **adviser** no longer grants grade access on its own — only being the teacher actually assigned to that specific subject does (`authorizeSectionSubject()`, added to both controllers). Advisory is now roster-only.
- Real bug fix riding along: `ClassController`'s attendance scoring was silently always 0 (never had cutoff-aware logic, unlike `GradeController`) — now computes correctly once a midterm cutoff date is set, consistent between both controllers
- `GradeConfiguration::buildComponentMatrix()` added — single source of truth for component ordering, grade-item grouping, and stable per-component color assignment, consumed by both the live Class Record view and the Excel export so they can no longer drift out of sync with each other
- `ClassRecordExport` rewritten to accept the shared matrix instead of rebuilding column logic independently
- `resources/views/teacher/classes/record.blade.php` rebuilt from hardcoded quiz/exam/project/assessment/attendance columns to a dynamic matrix-driven table

### Section Roster Page — Per-Subject Action Cards
- `teacher/classes/show.blade.php` (adviser-facing roster page) previously gated all grade-related quick actions behind a single section-wide `$hasConfig` flag pointing at routes that no longer accept a bare `{section}` — now lists each subject taught in the section as its own card, showing "Configured" / "Not configured" / "Not your subject" state per subject, with action links only rendered for subjects the logged-in teacher actually teaches
- `ClassController@show()` rewritten to build this per-subject data set instead of a single boolean

### Teacher Sidebar — Split Dashboard into Advisory / Teaching Tabs
- Combined "My Classes" dashboard split into three: `teacher.dashboard` (overview, unchanged), new `teacher.advisory` (sections where teacher is adviser), new `teacher.teaching` (subjects taught, across any section) — sidebar now shows all three as separate nav links
- `DashboardController` refactored with shared private query builders (`advisoryQuery()`, `teachingQuery()`) reused by `index()` and the two new actions

### Adviser Assignment — Missing One-Teacher-One-Section Rule (CRITICAL, client-reported: "may section nga di naka assign ha teacher pero nag appear ha iya dashboard")
- Root cause, part 1: `Dean\SectionController@changeAdviser()` had no check preventing the same teacher from being set as adviser on more than one section at once — confirmed via direct query that one teacher held two simultaneous active advisories
- Root cause, part 2: a soft-deleted `Section` still had an active `SectionTerm` row pointing at it with a live `adviser_id`; the teacher dashboard's `$term->section` relation returned null for it (respecting the soft-delete scope) and crashed the page outright (`Attempt to read property "program" on null"`) rather than silently misbehaving
- Fixed: `changeAdviser()` now checks for an existing active advisory for the selected teacher (excluding the section's own current term) before saving, returning a validation error naming the conflicting section instead of overwriting silently
- Fixed: `DashboardController`'s advisory query now excludes terms whose section no longer exists (`whereHas('section')`); `advisory.blade.php` additionally guards with `@continue(!$term->section)` as a second layer of defense against any future stale data
- Bad historical data (duplicate advisory assignment, orphaned term against a deleted section) corrected directly via tinker; not left for the next person to rediscover

### Dean Sections Page — Duplicate Error Banner + Uncaught Null Property Crash
- `$errors->any()` was rendering twice — once from the shared `layouts/app.blade.php` (correct, app-wide), once from a redundant local copy in `dean/sections/index.blade.php` — same recurring duplicate-banner pattern as several earlier sessions; local copy removed
- The adviser-conflict error message itself was crashing the page (`Attempt to read property "full_name" on null`) when the conflicting section had been soft-deleted, since eager-loading `->section` respects the soft-delete scope and returns null — fixed by fetching the conflicting section directly via `Section::find()` with a null-safe fallback label instead of relying on the eager-loaded relation

### SweetAlert2 Migration — Shared Layout Flash Messages
- All four flash/validation message blocks in `layouts/app.blade.php` (`success`, `warning`, `error`, `$errors`) converted from static colored `<div>` banners to `Swal.fire()` calls, applied app-wide in one pass
- SweetAlert2 CDN script tag added to the shared layout `<head>`

---

## QA FIXES & PATCHES — August 17, 2026

### Grading System — Section-Level to Subject-Level Refactor (MAJOR)
- Root cause: grades (`grade_configurations`, `grade_items`, `final_grades`) were scoped only to `section_id`, but a section takes multiple subjects, often with different teachers — the schema had no way to keep one subject's grades separate from another's within the same section
- Migrations added: `subject_id` added to `grade_configurations` (with new composite unique `[section_id, subject_id]`), `grade_items`, and `final_grades` (with new composite unique `[enrollment_id, subject_id]`, replacing the old single-column unique on `enrollment_id`)
- `Section` model gained `gradeConfigurationFor($subjectId)`, `gradeItemsFor($subjectId)`, and `gradeConfigurations()` (hasMany); the old singular `gradeConfiguration()` (hasOne) relation kept in place but deprecated, in case anything else still calls it
- `Teacher\GradeController` and `Teacher\ClassController` — every method (`config`, `storeConfig`, `items`, `storeItem`, `destroyItem`, `scores`, `storeScores`, `finalGrades`, `updateCutoff`, `computeGrades`, `lockGrades`, `record`, `export`) now takes `Subject $subject` alongside `Section $section`, with every query, redirect, and view scoped accordingly
- All `teacher.grades.*` and `teacher.classes.record`/`export` routes updated to include `{subject}` in the URL
- Access model tightened: being a section **adviser** no longer grants grade access on its own — only being the teacher actually assigned to that specific subject does (`authorizeSectionSubject()`, added to both controllers). Advisory is now roster-only.
- Real bug fix riding along: `ClassController`'s attendance scoring was silently always 0 (never had cutoff-aware logic, unlike `GradeController`) — now computes correctly once a midterm cutoff date is set, consistent between both controllers
- `GradeConfiguration::buildComponentMatrix()` added — single source of truth for component ordering, grade-item grouping, and stable per-component color assignment, consumed by both the live Class Record view and the Excel export so they can no longer drift out of sync with each other
- `ClassRecordExport` rewritten to accept the shared matrix instead of rebuilding column logic independently
- `resources/views/teacher/classes/record.blade.php` rebuilt from hardcoded quiz/exam/project/assessment/attendance columns to a dynamic matrix-driven table

### Section Roster Page — Per-Subject Action Cards
- `teacher/classes/show.blade.php` (adviser-facing roster page) previously gated all grade-related quick actions behind a single section-wide `$hasConfig` flag pointing at routes that no longer accept a bare `{section}` — now lists each subject taught in the section as its own card, showing "Configured" / "Not configured" / "Not your subject" state per subject, with action links only rendered for subjects the logged-in teacher actually teaches
- `ClassController@show()` rewritten to build this per-subject data set instead of a single boolean

### Teacher Sidebar — Split Dashboard into Advisory / Teaching Tabs
- Combined "My Classes" dashboard split into three: `teacher.dashboard` (overview, unchanged), new `teacher.advisory` (sections where teacher is adviser), new `teacher.teaching` (subjects taught, across any section) — sidebar now shows all three as separate nav links
- `DashboardController` refactored with shared private query builders (`advisoryQuery()`, `teachingQuery()`) reused by `index()` and the two new actions

### Adviser Assignment — Missing One-Teacher-One-Section Rule (CRITICAL, client-reported: "may section nga di naka assign ha teacher pero nag appear ha iya dashboard")
- Root cause, part 1: `Dean\SectionController@changeAdviser()` had no check preventing the same teacher from being set as adviser on more than one section at once — confirmed via direct query that one teacher held two simultaneous active advisories
- Root cause, part 2: a soft-deleted `Section` still had an active `SectionTerm` row pointing at it with a live `adviser_id`; the teacher dashboard's `$term->section` relation returned null for it (respecting the soft-delete scope) and crashed the page outright (`Attempt to read property "program" on null"`) rather than silently misbehaving
- Fixed: `changeAdviser()` now checks for an existing active advisory for the selected teacher (excluding the section's own current term) before saving, returning a validation error naming the conflicting section instead of overwriting silently
- Fixed: `DashboardController`'s advisory query now excludes terms whose section no longer exists (`whereHas('section')`); `advisory.blade.php` additionally guards with `@continue(!$term->section)` as a second layer of defense against any future stale data
- Bad historical data (duplicate advisory assignment, orphaned term against a deleted section) corrected directly via tinker; not left for the next person to rediscover

### Dean Sections Page — Duplicate Error Banner + Uncaught Null Property Crash
- `$errors->any()` was rendering twice — once from the shared `layouts/app.blade.php` (correct, app-wide), once from a redundant local copy in `dean/sections/index.blade.php` — same recurring duplicate-banner pattern as several earlier sessions; local copy removed
- The adviser-conflict error message itself was crashing the page (`Attempt to read property "full_name" on null`) when the conflicting section had been soft-deleted, since eager-loading `->section` respects the soft-delete scope and returns null — fixed by fetching the conflicting section directly via `Section::find()` with a null-safe fallback label instead of relying on the eager-loaded relation

### SweetAlert2 Migration — Shared Layout Flash Messages
- All four flash/validation message blocks in `layouts/app.blade.php` (`success`, `warning`, `error`, `$errors`) converted from static colored `<div>` banners to `Swal.fire()` calls, applied app-wide in one pass
- SweetAlert2 CDN script tag added to the shared layout `<head>`

---

## QA FIXES & PATCHES — August 17, 2026

### Grading System — Section-Level to Subject-Level Refactor (MAJOR)
- Root cause: grades (`grade_configurations`, `grade_items`, `final_grades`) were scoped only to `section_id`, but a section takes multiple subjects, often with different teachers — the schema had no way to keep one subject's grades separate from another's within the same section
- Migrations added: `subject_id` added to `grade_configurations` (with new composite unique `[section_id, subject_id]`), `grade_items`, and `final_grades` (with new composite unique `[enrollment_id, subject_id]`, replacing the old single-column unique on `enrollment_id`)
- `Section` model gained `gradeConfigurationFor($subjectId)`, `gradeItemsFor($subjectId)`, and `gradeConfigurations()` (hasMany); the old singular `gradeConfiguration()` (hasOne) relation kept in place but deprecated, in case anything else still calls it
- `Teacher\GradeController` and `Teacher\ClassController` — every method (`config`, `storeConfig`, `items`, `storeItem`, `destroyItem`, `scores`, `storeScores`, `finalGrades`, `updateCutoff`, `computeGrades`, `lockGrades`, `record`, `export`) now takes `Subject $subject` alongside `Section $section`, with every query, redirect, and view scoped accordingly
- All `teacher.grades.*` and `teacher.classes.record`/`export` routes updated to include `{subject}` in the URL
- Access model tightened: being a section **adviser** no longer grants grade access on its own — only being the teacher actually assigned to that specific subject does (`authorizeSectionSubject()`, added to both controllers). Advisory is now roster-only.
- Real bug fix riding along: `ClassController`'s attendance scoring was silently always 0 (never had cutoff-aware logic, unlike `GradeController`) — now computes correctly once a midterm cutoff date is set, consistent between both controllers
- `GradeConfiguration::buildComponentMatrix()` added — single source of truth for component ordering, grade-item grouping, and stable per-component color assignment, consumed by both the live Class Record view and the Excel export so they can no longer drift out of sync with each other
- `ClassRecordExport` rewritten to accept the shared matrix instead of rebuilding column logic independently
- `resources/views/teacher/classes/record.blade.php` rebuilt from hardcoded quiz/exam/project/assessment/attendance columns to a dynamic matrix-driven table

### Section Roster Page — Per-Subject Action Cards
- `teacher/classes/show.blade.php` (adviser-facing roster page) previously gated all grade-related quick actions behind a single section-wide `$hasConfig` flag pointing at routes that no longer accept a bare `{section}` — now lists each subject taught in the section as its own card, showing "Configured" / "Not configured" / "Not your subject" state per subject, with action links only rendered for subjects the logged-in teacher actually teaches
- `ClassController@show()` rewritten to build this per-subject data set instead of a single boolean

### Teacher Sidebar — Split Dashboard into Advisory / Teaching Tabs
- Combined "My Classes" dashboard split into three: `teacher.dashboard` (overview, unchanged), new `teacher.advisory` (sections where teacher is adviser), new `teacher.teaching` (subjects taught, across any section) — sidebar now shows all three as separate nav links
- `DashboardController` refactored with shared private query builders (`advisoryQuery()`, `teachingQuery()`) reused by `index()` and the two new actions

### Adviser Assignment — Missing One-Teacher-One-Section Rule (CRITICAL, client-reported: "may section nga di naka assign ha teacher pero nag appear ha iya dashboard")
- Root cause, part 1: `Dean\SectionController@changeAdviser()` had no check preventing the same teacher from being set as adviser on more than one section at once — confirmed via direct query that one teacher held two simultaneous active advisories
- Root cause, part 2: a soft-deleted `Section` still had an active `SectionTerm` row pointing at it with a live `adviser_id`; the teacher dashboard's `$term->section` relation returned null for it (respecting the soft-delete scope) and crashed the page outright (`Attempt to read property "program" on null"`) rather than silently misbehaving
- Fixed: `changeAdviser()` now checks for an existing active advisory for the selected teacher (excluding the section's own current term) before saving, returning a validation error naming the conflicting section instead of overwriting silently
- Fixed: `DashboardController`'s advisory query now excludes terms whose section no longer exists (`whereHas('section')`); `advisory.blade.php` additionally guards with `@continue(!$term->section)` as a second layer of defense against any future stale data
- Bad historical data (duplicate advisory assignment, orphaned term against a deleted section) corrected directly via tinker; not left for the next person to rediscover

### Dean Sections Page — Duplicate Error Banner + Uncaught Null Property Crash
- `$errors->any()` was rendering twice — once from the shared `layouts/app.blade.php` (correct, app-wide), once from a redundant local copy in `dean/sections/index.blade.php` — same recurring duplicate-banner pattern as several earlier sessions; local copy removed
- The adviser-conflict error message itself was crashing the page (`Attempt to read property "full_name" on null`) when the conflicting section had been soft-deleted, since eager-loading `->section` respects the soft-delete scope and returns null — fixed by fetching the conflicting section directly via `Section::find()` with a null-safe fallback label instead of relying on the eager-loaded relation

### SweetAlert2 Migration — Shared Layout Flash Messages
- All four flash/validation message blocks in `layouts/app.blade.php` (`success`, `warning`, `error`, `$errors`) converted from static colored `<div>` banners to `Swal.fire()` calls, applied app-wide in one pass
- SweetAlert2 CDN script tag added to the shared layout `<head>`

---

## QA FIXES & PATCHES — August 17, 2026

### Grading System — Section-Level to Subject-Level Refactor (MAJOR)
- Root cause: grades (`grade_configurations`, `grade_items`, `final_grades`) were scoped only to `section_id`, but a section takes multiple subjects, often with different teachers — the schema had no way to keep one subject's grades separate from another's within the same section
- Migrations added: `subject_id` added to `grade_configurations` (with new composite unique `[section_id, subject_id]`), `grade_items`, and `final_grades` (with new composite unique `[enrollment_id, subject_id]`, replacing the old single-column unique on `enrollment_id`)
- `Section` model gained `gradeConfigurationFor($subjectId)`, `gradeItemsFor($subjectId)`, and `gradeConfigurations()` (hasMany); the old singular `gradeConfiguration()` (hasOne) relation kept in place but deprecated, in case anything else still calls it
- `Teacher\GradeController` and `Teacher\ClassController` — every method (`config`, `storeConfig`, `items`, `storeItem`, `destroyItem`, `scores`, `storeScores`, `finalGrades`, `updateCutoff`, `computeGrades`, `lockGrades`, `record`, `export`) now takes `Subject $subject` alongside `Section $section`, with every query, redirect, and view scoped accordingly
- All `teacher.grades.*` and `teacher.classes.record`/`export` routes updated to include `{subject}` in the URL
- Access model tightened: being a section **adviser** no longer grants grade access on its own — only being the teacher actually assigned to that specific subject does (`authorizeSectionSubject()`, added to both controllers). Advisory is now roster-only.
- Real bug fix riding along: `ClassController`'s attendance scoring was silently always 0 (never had cutoff-aware logic, unlike `GradeController`) — now computes correctly once a midterm cutoff date is set, consistent between both controllers
- `GradeConfiguration::buildComponentMatrix()` added — single source of truth for component ordering, grade-item grouping, and stable per-component color assignment, consumed by both the live Class Record view and the Excel export so they can no longer drift out of sync with each other
- `ClassRecordExport` rewritten to accept the shared matrix instead of rebuilding column logic independently
- `resources/views/teacher/classes/record.blade.php` rebuilt from hardcoded quiz/exam/project/assessment/attendance columns to a dynamic matrix-driven table

### Section Roster Page — Per-Subject Action Cards
- `teacher/classes/show.blade.php` (adviser-facing roster page) previously gated all grade-related quick actions behind a single section-wide `$hasConfig` flag pointing at routes that no longer accept a bare `{section}` — now lists each subject taught in the section as its own card, showing "Configured" / "Not configured" / "Not your subject" state per subject, with action links only rendered for subjects the logged-in teacher actually teaches
- `ClassController@show()` rewritten to build this per-subject data set instead of a single boolean

### Teacher Sidebar — Split Dashboard into Advisory / Teaching Tabs
- Combined "My Classes" dashboard split into three: `teacher.dashboard` (overview, unchanged), new `teacher.advisory` (sections where teacher is adviser), new `teacher.teaching` (subjects taught, across any section) — sidebar now shows all three as separate nav links
- `DashboardController` refactored with shared private query builders (`advisoryQuery()`, `teachingQuery()`) reused by `index()` and the two new actions

### Adviser Assignment — Missing One-Teacher-One-Section Rule (CRITICAL, client-reported: "may section nga di naka assign ha teacher pero nag appear ha iya dashboard")
- Root cause, part 1: `Dean\SectionController@changeAdviser()` had no check preventing the same teacher from being set as adviser on more than one section at once — confirmed via direct query that one teacher held two simultaneous active advisories
- Root cause, part 2: a soft-deleted `Section` still had an active `SectionTerm` row pointing at it with a live `adviser_id`; the teacher dashboard's `$term->section` relation returned null for it (respecting the soft-delete scope) and crashed the page outright (`Attempt to read property "program" on null"`) rather than silently misbehaving
- Fixed: `changeAdviser()` now checks for an existing active advisory for the selected teacher (excluding the section's own current term) before saving, returning a validation error naming the conflicting section instead of overwriting silently
- Fixed: `DashboardController`'s advisory query now excludes terms whose section no longer exists (`whereHas('section')`); `advisory.blade.php` additionally guards with `@continue(!$term->section)` as a second layer of defense against any future stale data
- Bad historical data (duplicate advisory assignment, orphaned term against a deleted section) corrected directly via tinker; not left for the next person to rediscover

### Dean Sections Page — Duplicate Error Banner + Uncaught Null Property Crash
- `$errors->any()` was rendering twice — once from the shared `layouts/app.blade.php` (correct, app-wide), once from a redundant local copy in `dean/sections/index.blade.php` — same recurring duplicate-banner pattern as several earlier sessions; local copy removed
- The adviser-conflict error message itself was crashing the page (`Attempt to read property "full_name" on null`) when the conflicting section had been soft-deleted, since eager-loading `->section` respects the soft-delete scope and returns null — fixed by fetching the conflicting section directly via `Section::find()` with a null-safe fallback label instead of relying on the eager-loaded relation

### SweetAlert2 Migration — Shared Layout Flash Messages
- All four flash/validation message blocks in `layouts/app.blade.php` (`success`, `warning`, `error`, `$errors`) converted from static colored `<div>` banners to `Swal.fire()` calls, applied app-wide in one pass
- SweetAlert2 CDN script tag added to the shared layout `<head>`

---

## QA FIXES & PATCHES — August 17, 2026

### Grading System — Section-Level to Subject-Level Refactor (MAJOR)
- Root cause: grades (`grade_configurations`, `grade_items`, `final_grades`) were scoped only to `section_id`, but a section takes multiple subjects, often with different teachers — the schema had no way to keep one subject's grades separate from another's within the same section
- Migrations added: `subject_id` added to `grade_configurations` (with new composite unique `[section_id, subject_id]`), `grade_items`, and `final_grades` (with new composite unique `[enrollment_id, subject_id]`, replacing the old single-column unique on `enrollment_id`)
- `Section` model gained `gradeConfigurationFor($subjectId)`, `gradeItemsFor($subjectId)`, and `gradeConfigurations()` (hasMany); the old singular `gradeConfiguration()` (hasOne) relation kept in place but deprecated, in case anything else still calls it
- `Teacher\GradeController` and `Teacher\ClassController` — every method (`config`, `storeConfig`, `items`, `storeItem`, `destroyItem`, `scores`, `storeScores`, `finalGrades`, `updateCutoff`, `computeGrades`, `lockGrades`, `record`, `export`) now takes `Subject $subject` alongside `Section $section`, with every query, redirect, and view scoped accordingly
- All `teacher.grades.*` and `teacher.classes.record`/`export` routes updated to include `{subject}` in the URL
- Access model tightened: being a section **adviser** no longer grants grade access on its own — only being the teacher actually assigned to that specific subject does (`authorizeSectionSubject()`, added to both controllers). Advisory is now roster-only.
- Real bug fix riding along: `ClassController`'s attendance scoring was silently always 0 (never had cutoff-aware logic, unlike `GradeController`) — now computes correctly once a midterm cutoff date is set, consistent between both controllers
- `GradeConfiguration::buildComponentMatrix()` added — single source of truth for component ordering, grade-item grouping, and stable per-component color assignment, consumed by both the live Class Record view and the Excel export so they can no longer drift out of sync with each other
- `ClassRecordExport` rewritten to accept the shared matrix instead of rebuilding column logic independently
- `resources/views/teacher/classes/record.blade.php` rebuilt from hardcoded quiz/exam/project/assessment/attendance columns to a dynamic matrix-driven table

### Section Roster Page — Per-Subject Action Cards
- `teacher/classes/show.blade.php` (adviser-facing roster page) previously gated all grade-related quick actions behind a single section-wide `$hasConfig` flag pointing at routes that no longer accept a bare `{section}` — now lists each subject taught in the section as its own card, showing "Configured" / "Not configured" / "Not your subject" state per subject, with action links only rendered for subjects the logged-in teacher actually teaches
- `ClassController@show()` rewritten to build this per-subject data set instead of a single boolean

### Teacher Sidebar — Split Dashboard into Advisory / Teaching Tabs
- Combined "My Classes" dashboard split into three: `teacher.dashboard` (overview, unchanged), new `teacher.advisory` (sections where teacher is adviser), new `teacher.teaching` (subjects taught, across any section) — sidebar now shows all three as separate nav links
- `DashboardController` refactored with shared private query builders (`advisoryQuery()`, `teachingQuery()`) reused by `index()` and the two new actions

### Adviser Assignment — Missing One-Teacher-One-Section Rule (CRITICAL, client-reported: "may section nga di naka assign ha teacher pero nag appear ha iya dashboard")
- Root cause, part 1: `Dean\SectionController@changeAdviser()` had no check preventing the same teacher from being set as adviser on more than one section at once — confirmed via direct query that one teacher held two simultaneous active advisories
- Root cause, part 2: a soft-deleted `Section` still had an active `SectionTerm` row pointing at it with a live `adviser_id`; the teacher dashboard's `$term->section` relation returned null for it (respecting the soft-delete scope) and crashed the page outright (`Attempt to read property "program" on null"`) rather than silently misbehaving
- Fixed: `changeAdviser()` now checks for an existing active advisory for the selected teacher (excluding the section's own current term) before saving, returning a validation error naming the conflicting section instead of overwriting silently
- Fixed: `DashboardController`'s advisory query now excludes terms whose section no longer exists (`whereHas('section')`); `advisory.blade.php` additionally guards with `@continue(!$term->section)` as a second layer of defense against any future stale data
- Bad historical data (duplicate advisory assignment, orphaned term against a deleted section) corrected directly via tinker; not left for the next person to rediscover

### Dean Sections Page — Duplicate Error Banner + Uncaught Null Property Crash
- `$errors->any()` was rendering twice — once from the shared `layouts/app.blade.php` (correct, app-wide), once from a redundant local copy in `dean/sections/index.blade.php` — same recurring duplicate-banner pattern as several earlier sessions; local copy removed
- The adviser-conflict error message itself was crashing the page (`Attempt to read property "full_name" on null`) when the conflicting section had been soft-deleted, since eager-loading `->section` respects the soft-delete scope and returns null — fixed by fetching the conflicting section directly via `Section::find()` with a null-safe fallback label instead of relying on the eager-loaded relation

### SweetAlert2 Migration — Shared Layout Flash Messages
- All four flash/validation message blocks in `layouts/app.blade.php` (`success`, `warning`, `error`, `$errors`) converted from static colored `<div>` banners to `Swal.fire()` calls, applied app-wide in one pass
- SweetAlert2 CDN script tag added to the shared layout `<head>`

---

## QA FIXES & PATCHES — August 20, 2026

### Department/Program Data Isolation (MAJOR, client-flagged: "my dean and programhead is global not specific to any department or course")
- Root cause: `department_id` existed on `users` but was never enforced in any Dean or Program Head query — any Dean saw every section/teacher/student/subject system-wide, and Program Head had no scoping mechanism (`program_id` column didn't exist) at all
- Migrations added: `users.program_id` (nullable FK → programs, used only by program_head role), `subjects.program_id`, `students.program_id` (replacing the old free-text `students.program` string column entirely)
- New account-creation model: Admin creates Teacher/Program Head accounts and assigns a `department_id`; a Program Head's specific `program_id` is assigned separately by their Dean (new `dean.program-heads.*` routes/controller/view — assign, reassign, or remove with confirmation, scoped to programs within the Dean's own department)
- Real scoping filters + `abort_if` ownership guards added across every Dean controller (`SectionController`, `AssignmentController`, `DashboardController`, `EnrollmentController`, `StudentController`, `TeacherApprovalController`, `SubjectController`) and every Program Head controller (`ProgramHeadController`, plus new `SectionController`, `StudentController`, `SubjectController` mirroring Dean's, scoped to `program_id` instead of `department_id`)
- Self-registration (re-enabled August 5) closed again — `/register` routes disabled in `routes/auth.php`, link removed from login page. A self-registered account had no department/program tie by design, reopening the same isolation hole this whole fix addresses; `RegisteredUserController` left on disk, unused, for easy re-enable if needed. **Note:** the Admin "Pending Requests" review queue built August 5 is now dead UI with no way to receive new submissions — flagged for cleanup, not yet removed.
- Two silent pre-existing bugs surfaced and fixed during this work: `department_id` validation combined `nullable` with `required_if`, silently letting empty department submissions through (Laravel's `nullable` skips all other rules on an empty value, `required_if` included); `User::$fillable` was missing `department_id`/`program_id`, so Eloquent mass-assignment silently dropped both on every account creation regardless of what the form submitted
- Seeder gap fixed: `SuperAdminSeeder` created two accounts (`Program Head Sample`, `Pending Teacher`) with no `department_id` on every `migrate:fresh` run, making them permanently invisible to Dean-scoped screens — both now seeded with a real department
- Second department (CBA — Dean, Teacher, Program Head, Program, Section) added to seed data specifically to enable negative isolation testing, previously impossible with only one department seeded
- Verified via real HTTP requests (not just query review) in both directions and both roles: CCS Dean blocked from CBA section (403), CBA Dean blocked from CCS section (403), CBA Program Head blocked from a CCS section (403)

### Student Master List — Sort & Filter (Dean and Program Head)
- Both Dean's and Program Head's student list pages gained click-to-toggle sortable columns (Student No., Name, Date Added — ascending/descending, matching the Windows Explorer column-header pattern) via URL query params, preserved across pagination
- Dean's list gained a Program filter dropdown (scoped to their department's programs); Program Head's list gained Year Level and Section filters, including an "Unassigned" option for students not currently enrolled in any section term

---

## QA FIXES & PATCHES — August 21, 2026

### Academic Period Centralization — Final 3 Files Closed Out
- Completed the academic-period centralization effort flagged as open in the previous session: `resources/views/program-head/sections/index.blade.php`, `resources/views/teacher/classes/record.blade.php`, `resources/views/teacher/grades/final.blade.php`
- Program Head's "Change Adviser" modal — removed the free-typed Academic Year input and Semester dropdown (and their JS parameters); modal now reads `AcademicPeriod::getActive()` server-side and displays the active period read-only, mirroring the Dean's already-patched version, with the Adviser select and Save button disabled when no period is active
- Both Teacher views — removed the dead midterm-cutoff-setting UI (date input, Save button, "Not set" warning) that referenced the now-dropped `section_terms.midterm_cutoff_date` column and the removed `updateCutoff()` route; cutoff is now sourced exclusively from `AcademicPeriod::getActive()` per the prior session's controller changes
- Confirmed via `php artisan tinker`: zero Dean/Program Head/Teacher accounts with `department_id = null` — the "orphaned accounts" item flagged as an open risk in the previous session is not an active issue

### Academic Period Activation — Two-Step Flow Clarified (client-reported: newly created period not taking effect)
- Not a bug — `SuperAdmin\AcademicPeriodController@store` intentionally creates every new period with `is_active = false`; activation is a deliberate separate step via the existing "Set Active" button on the Academic Periods page, which correctly deactivates all other periods and activates the selected one
- No code change; confirmed the button and route (`admin.academic.setActive`) were already wired correctly end-to-end

### Dean Student Management — Cross-Department Data Isolation Gap (CRITICAL, client-reported: students visible/editable across departments)
- Root cause: `Dean\StudentController@create()` and `@edit()` queried `Program::where('status', 'approved')` with no department scoping, so both the Add and Edit forms' program dropdowns listed every department's programs, not just the Dean's own — a student could be saved into another department's program and would then silently disappear from the Dean's own (correctly-scoped) student list
- Compounding the same gap: `store()` and `update()` never validated that the submitted `program_id` actually belonged to the Dean's department, and `edit()`, `update()`, `destroy()` had no `abort_if` ownership guard at all — unlike every other Dean controller in the app, which follows this guard pattern consistently
- Fixed: `create()`/`edit()` program lists now scoped to `auth()->user()->department_id`; `store()`/`update()` verify the submitted `program_id`'s department against the Dean's own before saving; `edit()`, `update()`, `destroy()` now guard with the same `abort_if(... department_id !== ..., 403, ...)` pattern used elsewhere in the app
- Confirmed clean, no change needed: `Dean\DashboardController`'s `total_students` stat (already correctly scoped via `whereHas('program', ...department_id...)`) and the subject request/approval flow (`Dean\SubjectController`, `ProgramHead\SubjectController`, `dean/subjects/index.blade.php`) — already Program Head requests → Dean approves, not Dean → Admin as originally suspected

### Dean Students Route — Dead `show` Route (Same Pattern as March 4's SectionController Fix)
- `Route::resource('students', DeanStudent::class)` in `routes/web.php` auto-generated a `show` route with no corresponding controller method — confirmed via grep that no view links to `dean.students.show` — any direct hit would have thrown a fatal `BadMethodCallException` instead of a graceful 404
- Fixed: `->except(['show'])` added to the resource route declaration

### Dean Sections — Subjects & Teachers Panel Made View-Only (Design Decision)
- The inline "Subjects & Teachers" add/reassign form inside the Dean's section details modal (built August 5) and the standalone `Dean\AssignmentController` (built the same session) were both writing to the same `section_subject_teachers` pivot via two independent code paths with inconsistent duplicate-handling behavior — flagged as redundant
- Resolved per client decision: Dean's section modal is now read-only for subject/teacher pairings (plain list, no Add form, no per-row reassign), with a text link pointing to the Assignments tab for all actual add/reassign/remove actions; Program Head's equivalent section modal is unchanged and keeps its inline form, since Program Head has no separate Assignments tab
- `Dean\SectionController@attachSubject` and `@changeSubjectTeacher` (plus their routes) are now unreferenced by any view — left in place pending a decision on removal

### Known Gap Identified (not fixed — carried over for next QA pass)
- Broader QA sweep of Phases 1–8 for the two confirmed bug shapes (`nullable` silently defeating `required_if` on other validation rules; `$fillable` arrays missing columns their own controllers submit) — recommended in the prior session, not yet started systematically beyond the Student controller gap found and fixed above

## PHASE 9: REPORTING & ANALYTICS 📅 PLANNED

- Teacher: class performance summary, grade distribution, failing students alert, attendance trends
- Dean: department-wide stats, teacher performance, section comparison, consolidated reports
- Activity logs — grade changes, login history, export to Excel

---

## PHASE 10: UI/UX POLISH 📅 PLANNED

- Toast notifications and confirmation modals
- Breadcrumb navigation
- Mobile responsiveness audit
- Inline score editing, keyboard navigation (Tab/Enter/Arrow)
- Filter and sort on class record view

---

## PHASE 11: TESTING & DEPLOYMENT 📅 PLANNED

- Feature tests — core workflows, grade calculation accuracy, permission checks
- Production `.env` configuration, HTTPS, rate limiting on login
- Change default seeder passwords before production

---

**Last Updated:** August 21, 2026  
**Next Milestone:** Phase 9 — Reporting & Analytics  
**Maintained By:** Frances Igop
