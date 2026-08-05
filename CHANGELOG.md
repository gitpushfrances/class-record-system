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

**Last Updated:** August 5, 2026  
**Next Milestone:** Phase 9 — Reporting & Analytics  
**Maintained By:** Frances Igop
