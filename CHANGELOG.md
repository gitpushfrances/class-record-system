# CHANGELOG - Class Record System

## Project Information
**System Name:** Faculty Class Record Management System  
**Tech Stack:** Laravel 10 LTS + MySQL + Blade + Tailwind CSS  
**PHP Version:** 8.4.11  
**Node Version:** 18.20.8  
**Started:** February 8, 2026  
**Current Release:** v1.0-rc3 (October 20, 2026), client testing  
**Automated Tests:** 245 passing  

---

## SYSTEM FLOW — ROLES AND RULES (v1.0-rc3, October 20, 2026)

This replaces the original February plan. It describes what the system does today.

### Super Admin
- Dashboard with system-wide counts
- **View As**: open the system as the Dean of a department or the Program Head of a program, to see and act on their pages (banner shows "Viewing as"; Exit returns to Admin)
- **Faculty**: create accounts for any role, approve or reject sign-up requests, activate / deactivate, change role
- **Subjects**: all departments; approve or reject pending subject requests
- **Departments**: add / edit departments and programs, deactivate / activate or delete (delete only while unused); drill down to sections and students
- **Academic Period**: add a school year and semester with midterm and finals cutoff dates; set one period active
- **Backup & Restore**: run a backup, download, delete, restore (`.sql` / `.txt` only)
- **ROG Settings**: logos, form code, version, effectivity date, Registrar name

### Dean (own department only)
- **Faculty**: department teachers, activate / deactivate, approve or reject pending teacher sign-ups
- **Sections**: view sections, assign subjects with teacher and lecture / laboratory schedules (same panel as the Program Head), Set Term / Change Adviser, view the Report of Grades
- **Students**: department master list; add, edit, import from Excel
- **Subjects**: approve or reject Program Head subject requests
- **Assignments**: assign a teacher with lecture and laboratory schedules to a section's subject; Schedule column; Edit opens the section page
- **Enrollments**: enroll by search, drop, or remove a student enrolled by mistake; dropped list with Re-enroll
- **Promotion**: move a section's students to the next year level and a target section
- **Program Heads**: assign or remove the Program Head of each program

### Program Head (own program only)
- **Grade Verification**: review submitted subjects; Verify, Reject (with a reason, sends it back to the teacher), Undo Verification; View Report of Grades
- **Sections**: create / edit sections, assign subjects with teacher and lecture / laboratory schedules, Set Term / Change Adviser; **Manage Students** on the section card: enroll by search, drop, remove a student enrolled by mistake, re-enroll
- **Students**: program master list; add, edit, import from Excel
- **Promotion**: same as the Dean, own program only
- **Subjects**: request new subjects with year level, semester and subject type (lecture only, or lecture and laboratory), pending until the Dean approves; edit while pending

### Teacher
- Requests an account from the sign-in page; pending until the Dean or Admin approves
- **Dashboard**, **My Advisory** (advisory section roster), **My Subjects**, **Past Classes** (closed terms, read-only: Class Record, Attendance Summary, Report of Grades)
- Per subject: **Grade Config** (components in the teacher's order (▲ / ▼), weights = 100%; defaults Class Standing 60% and Exam 40% in each period; conversion: standard table, major formula 5 − 4(S/TS), minor formula 4 − 3(S/TS)), **Class Record** (+ Add items, inline scores, Midterm Grade and Finals Grade columns, Print, Export Excel), **Attendance** (present / absent; lecture and laboratory separately for lab subjects; Summary with one column per session, ✓ / ✗ per student, Midterm and Finals), **Students** (add irregular students, drop / restore per subject, remove one added by mistake), **Final Grades**, **Submit for Verification**, **Report of Grades**
- Back buttons lead to where the class was opened from (My Subjects, My Advisory or Dashboard)

### Key rules
- Every role sees only its own department / program; other roles' pages return 403 (`RoleAccessTest`)
- Each grade component belongs to Midterm or Finals (set in Grade Config). Attendance dated on or before the active period's midterm cutoff counts toward Midterm, later records toward Finals
- A weighted component with no scores makes that period INC for every student (approved rule)
- Submitting locks the subject: scores, attendance and the teacher's Class List can no longer change. The section class list (Program Head / Dean add or drop) is also locked while any subject of the term is pending or verified. To change anything, the Program Head rejects the submission (a verified one is first undone)
- Dropping a student keeps all records; the Report of Grades prints "Dr"
- Set Term in a new period closes the old term (records kept) and starts a new one; it warns first when subjects are not verified, because a closed term can no longer be submitted or verified
- Promotion cannot be undone from the UI
- A student belongs to one regular section per semester; irregular students are added per subject by the subject teacher
- Remove deletes an enrollment only while it has no scores, attendance or final grade and nothing is submitted; otherwise the student is dropped (records kept, "Dr" on the ROG)
- A subject with a laboratory has a lecture schedule and a laboratory schedule on the same assignment (same day allowed at different times); both are checked for conflicts and both print on the ROG
- Search boxes search while typing; each typed word must match the start of a name or of a student-number part, and an e-mail is searched only when the word contains "@"; the matched letters are highlighted
- Each term has its own grade items and grade configuration: a new term starts with an empty Class Record; a closed term keeps its records and opens read-only in Past Classes (nothing is copied or moved)
- Grades are rounded to one decimal; exactly half goes down, anything above half goes up (1.75 → 1.7, 1.751 → 1.8, Average 3.05 → 3.0); the same rule for period grades, averages and the Scores preview (`FinalGrade::roundGrade`)
- Each academic period needs its midterm cutoff date: attendance on or before it counts toward Midterm, later toward Finals; without it, attendance-graded classes show INC and `grades:recompute` skips them
- The Report of Grades prints "Nothing Follows" in the first empty row after the last student of each list

---

## RELEASE v1.0-rc3 — October 20, 2026 (client testing, round 3)

Tag `v1.0-rc3` on `main`. 245 automated tests passing. Server install: `DEPLOY.md`.

### What this release contains (Phase 10: the client's round-3 requests)
- **10a — New term, empty Class Record**: grade items and grade configurations belong to the term (`section_term_id`); before, a section that took the same subject again showed last term's items, kept numbering them and refused a new exam. Migration `2026_10_20_000100` assigns existing rows to their terms
- **10b — Past Classes (archive)**: teachers open their closed terms read-only: Class Record, Attendance Summary and Report of Grades. Requested as "move submitted records to another table and clear the class record"; done without copying, since a closed term already keeps its records and a new term starts empty (10a)
- **10c — Report of Grades**: "Nothing Follows" in the first empty row after the last student of each list
- **10d — Attendance Summary**: one column per session (date; Lec / Lab for laboratory subjects), ✓ present / ✗ absent / – no record, Midterm and Finals by the cutoff date, totals, absence streak and rate; printable; also in Past Classes
- **10e — Grade Config order**: ▲ / ▼ moves a component within its period; the saved order is the Class Record column order (Ma'am Mich)
- **10e-2 — Default components**: Quiz 20, Project 15, Assessment 15, Attendance 10, Exam 40 in each period (class standing 60% + exam 40%, the common split in Philippine state universities); "Security" and "Others" removed; still editable
- **10f — Rounding**: exactly half goes down, above half goes up (client's rule), for every grade, average and the Scores preview
- **10g — Background**: signed-in pages get an off-white background with a faint ESSU-green dot grid, a gold glow and the ESSU seal watermark (Sir Baldo); sign-in page, ROG and printouts unchanged

### Updating to this release (client)
1. Back up the database first: `mysqldump -h 127.0.0.1 -P 3306 -u root -p <database> > backup.sql` (use `127.0.0.1`, not `localhost`, on XAMPP)
2. `git fetch --tags && git checkout v1.0-rc3`, `composer install`, `npm ci && npm run build`, `php artisan migrate` (10a), `php artisan optimize:clear`
3. Admin → Academic Period: every period needs its midterm cutoff date
4. `php artisan grades:recompute` (dry run), read it, then `--apply` (the rounding rule changes values that sat exactly on a half)

### Waiting for the client
- **Sir Roger**: written yes to the rounding example (Midterm 3.0 + Finals 3.1 → Average 3.05 → 3.0 Passed; before: 3.1 Failed); D1 formula vs. printed legend; D6 component total ÷ total vs. average of item percentages
- **Ma'am Mich**: the default components (Exam 40% is in; the other weights follow the common 60/40 split until she sends her list)
- **Sir Baldo**: the background as built, or another direction
- Still open from rc2: D7 teachers from other departments, D8 programs with majors, D9 lecture / laboratory units, D10 absence warning at 3 or 4, D11 the exact Backup error

## RELEASE v1.0-rc2 — October 19, 2026 (client testing, round 2)

Tag `v1.0-rc2` on `main`. 214 automated tests passing. Client testing steps: the v1.0-rc2 Client Testing Guide (Word / PDF, sent to the client; not in the repository). Server install: `DEPLOY.md`.

### What this release contains (Phase 9: the client's round-2 issues)
- **9a — Lecture and laboratory**: subject type on Request Subject; one assignment holds a lecture and a laboratory schedule (same day allowed); conflicts checked on both; ROG prints both; one assignment service (`SubjectAssignment`) for the Program Head, the Dean's section page and Dean Assignments, with an active-teacher check; "Lec + Lab" on the subject lists
- **9b — Class Record**: Midterm Grade and Finals Grade columns (also in the Excel export); back buttons follow `?from=` (My Subjects, My Advisory, Dashboard)
- **9c — Enrollment**: one regular section per student per semester; enroll by search; Remove for a student enrolled by mistake (no records yet); Dean Enrollments with separate Drop / Remove and a Dropped list; "Manage Students" link on the Program Head section card
- **9e — Search and locks**: every search box searches while typing; names and student numbers match from the start of a word and are highlighted; a locked class list shows a notice instead of enrollment controls; ESSU-green dialog buttons
- **9f1 — Departments and programs**: errors shown once (inside the dialog); program examples; deactivate / activate and delete (delete only while unused, removed for good so the code can be reused); `mysqldump` folder from `.env` (`DB_DUMP_PATH`)

### Waiting for the client (guide Part 9)
- **D1 / D6 (Sir Roger)**: formula vs. printed legend, and component grade = total ÷ total (current) or average of item percentages. They decide every grade; get them in writing before real grades are entered
- **D7** teachers from other departments (any subject, or GE only) · **D8** programs with majors · **D9** lecture / laboratory units · **D10** absence warning at 3 or 4 · **D11** the exact Backup error, if it still fails

## RELEASE v1.0-rc1 — October 17, 2026 (client testing)

Tag `v1.0-rc1` on `main`. 176 automated tests passing. Client testing steps: `CLIENT-TESTING-GUIDE.txt`. Server install: `DEPLOY.md`.

The phase numbers below are the October 2026 work. The older "PHASE 1–8" headings further down are the original February build plan.

### What this release contains
- **Phase 2 — Grading engine**: one-decimal grades, half-up rounding, INC rules at submit, `grades:recompute`
- **Phase 3 — Class Record and attendance**: present / absent attendance with lecture / laboratory sessions, absence-streak warnings, inline score entry, "+ Add" items with server-assigned names
- **Phase 4 — Report of Grades**: printable ESSU-ACAD-712.b (Long), class schedule on assignments, ROG Settings, rank and credentials on signatures
- **Phase 5 — Roster and enrollment**: soft drop ("Dr" on the ROG), per-subject class list with irregular students, Program Head enrollment, Excel student import
- **Phase 6 — Roles**: Admin View As, Dean faculty management, departments / programs CRUD, subject year level and semester, subject assignment rule, term rollover (no more relabeling of last semester), batch promotion with audit log
- **Phase 7 — Polish**: ESSU green and gold with the ESSU seal, letterhead sign-in and request-account pages, light pages, 17 unused files removed, role-by-role access tests, `DEPLOY.md`
- **Phase 8 — Record integrity**: section class list locked while grades are submitted or verified (8a); Set Term warning for unverified subjects (8b)

### Required before go-live (client decisions — no code can settle these)
1. **Sir Roger's written approval of the grade formulas.** Grades are computed with major 5 − 4(S/TS) and minor 4 − 3(S/TS). The printed ROG legend says 55–69% = Failed, but with the formulas 3.0 (passing) falls at 50% (major) and 33.3% (minor). Either the formulas or the legend must change, in writing, before real grades are entered; if the formula changes after real grades exist, every subject must be recomputed and re-verified
2. **Registrar name** for the ROG signature block (Admin → ROG Settings; the sample form shows REGINA L. DOCENA, DBA)
3. **Default Finals components**: the default configuration still includes "Security" (20%, Finals); confirm or replace
4. **Teacher double-booking**: decide whether the same teacher in two sections at the same time must be blocked (today only subjects inside one section are checked)

### Recommendations (ranked by risk)
1. **8c — Server-side teacher check on assignment.** The teacher dropdown is filtered, but the server does not check that the teacher is an active teacher of the department; a hand-edited request can assign anyone
2. **Backup path.** `config/database.php` hardcodes `C:/xampp/mysql/bin/` for `mysqldump`; backups fail on a Linux server or a different XAMPP folder. Move it to `.env` (`DB_DUMP_PATH`) before deploying
3. **Backup restore.** Restore accepts only `.sql` / `.txt` and cannot restore the `.zip` files the system creates; scheduled backups are not set up (see `DEPLOY.md` §5)
4. **8d — Undo the last promotion**, using the existing promotion audit log
5. **Completed terms** are not browsable by teachers; add a term picker so old class records can be opened read-only
6. **Seeded passwords.** Every sample account uses `password`; never run the sample seeders on the server
7. **Performance.** Inline score save re-fetches the whole Class Record; fine for 40 students, slow for large classes
8. **Duplicate grade logic in JavaScript** (Scores page live preview) can drift from the PHP formula if the formula changes after Sir Roger's decision

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

---

## QA FIXES & PATCHES — August 23, 2026

### Grade Display — Decimal Precision Standardized to 1 Place (Philippine 1.00–5.00 Scale)
- Client decision: numerical grades on the 1.00–5.00 scale should display as `X.X` (e.g. `2.2`), not `X.XX` (e.g. `2.20`) — trailing zero was visual noise, not a data or calculation issue
- `letter_grade` was being pre-formatted to 2 decimals at write-time inside the controller, not at display-time in Blade — required patching both layers, not just views
- Fixed at write-time: `Teacher\ClassController` (`show()` live-grade array) and `Teacher\GradeController` (`finalGrades()`, `computeGrades()`, `submitForVerification()`) — `letter_grade` now stored as `number_format($numerical, 1)`
- Fixed at display-time: `teacher/classes/record.blade.php` (class-average cell, per-criterion `$cg` cells, class-average row), `teacher/grades/final.blade.php` (Midterm/Final/Average columns), `program-head/dashboard.blade.php` (Midterm/Final columns) — all changed from `number_format(..., 2)` to `number_format(..., 1)`
- **Known gap, not fixed:** pre-existing `FinalGrade` rows saved before this patch still hold the old 2-decimal string in `letter_grade`/`average_numerical` — inconsistent formatting will appear between old and newly-computed grades until those rows are recomputed or the table is reset. Percentage fields (`%`) were intentionally left untouched — only the 1.00–5.00 numerical scale was in scope.

### Score Entry Page — Percentage Column Replaced with Converted Grade
- `teacher/grades/scores.blade.php` previously showed a raw score percentage (`score / max_score × 100`) next to each student's input — client wanted the actual converted 1.00–5.00 grade shown instead, consistent with the rest of the system
- Server-side (Blade) column now calls `FinalGrade::convertToNumericalGrade()` instead of computing percentage inline; column header changed from "Percentage" to "Grade"
- Live JS preview (updates as a teacher types a score, before saving) previously used a separate raw percentage formula — `FinalGrade::convertToNumericalGrade()`'s full band table (flat zones + 8 interpolated bands + rounding) ported 1:1 into a JS function so the live preview matches the saved server-side value with no drift
- **Flagged for later:** this conversion table now lives in three places (PHP model + two JS ports across different views) — a shared JSON config or API endpoint would prevent future drift if the grading table changes

### Score Input Display — Trailing `.00` Removed from Whole-Number Scores
- Score fields (`decimal(8,2)` column, unchanged) were displaying whole-number scores as `32.00` instead of `32` — client noted scores are never entered with decimals in practice
- Fixed as display-only: `rtrim()` strips trailing zeros and the decimal point after `number_format($score, 2, '.', '')` in both the editable input's `value` and the locked/read-only `<span>` fallback — genuine decimal scores (e.g. `45.5`) still display correctly if ever entered; schema, `step="0.01"`, and validation were left untouched so partial-credit entry remains possible

### Logout — Stale Authenticated Pages via Browser Back Button
- Client-reported "page expired" sensation after logout traced to two separate causes investigated in the same session:
  - False alarm: an observed 419-looking error was actually a `PDOException` (MySQL not running locally at the time) — not a session/CSRF bug, no code change needed
  - Real issue: Breeze's default `AuthenticatedSessionController@destroy()` was already correctly invalidating the session and regenerating the CSRF token server-side, but the browser could still render a cached copy of a protected page via the Back button after logout
- New `App\Http\Middleware\PreventBackHistory` created — sets `Cache-Control: no-cache, no-store, max-age=0, must-revalidate`, `Pragma: no-cache`, and `Expires` headers on responses
- Registered as `no.cache` alias in `Kernel.php`, applied to all four role-based route groups (`admin`, `dean`, `teacher`, `program-head`) alongside existing `auth`/`status`/`role` middleware; profile routes left unchanged
- Verified via `route:list -v` that the middleware is correctly attached; browser back-button and direct-URL-after-logout behavior confirmed via manual test

## QA FIXES & PATCHES — August 26, 2026

### Session 419 (Page Expired) — Root Cause Identified, Not a Code Bug
- Investigated client-reported "Page Expired" on Logout — traced to normal Laravel behavior: a browser tab left open past the session's 120-minute lifetime carries a stale CSRF token baked into the already-loaded page, so any form submit (Logout included) fails the token check and shows 419
- Fixed: `App\Exceptions\Handler@register()` gained a `renderable()` handler for `TokenMismatchException` that redirects to `login` with a flash message, instead of showing the raw 419 error page — applies app-wide to any future stale-tab submission, not just Logout

### Super Admin Accounts — Role-Switching from Edit Form (NEW FEATURE)
- Root cause: `admin.deans.edit`/`update()` allowed changing department/program but never role itself — an account created as one role could not be reassigned to another without manual DB edits
- Added a `role` dropdown to `admin/deans/edit.blade.php`, with Department/Program fields shown or hidden client-side based on the selected role (Program field now also shows for Teacher, not just Program Head, since Teachers can be scoped to a program)
- `UserController@update()` now validates and applies the submitted `role`, with existing one-Dean-per-department and one-Program-Head-per-program checks re-run against the *new* role, not the account's prior one
- Real bug caught and fixed during this work: the app's route-protection middleware (`role:dean`, `role:teacher`, etc.) is Spatie-backed, but `update()` was only writing the plain `role` column — a switched account's actual route access wouldn't have matched what the edit form showed. Fixed by adding `$dean->syncRoles([$validated['role']])` alongside the column update, keeping both in sync. Confirmed via tinker that all four Spatie roles (`dean`, `program_head`, `super_admin`, `teacher`) already exist as seeded rows, so `syncRoles()` is safe to call without a `RoleDoesNotExist` risk
- Fixed a self-inflicted regression from mid-session patch drift: a duplicate `<script>` block (redeclaring `programsData`/`currentProgramId`, causing a JS crash) and a literal duplicate `</x-sidebar-layout>` closing tag (causing a PHP `ParseError: unexpected token "endif"`) were both introduced by an incremental find/replace against a stale copy of the file — resolved by reviewing the full file directly rather than continuing to patch blind

### Admin Accounts Table — Actions Column UI/UX Overhaul
- Text links (Edit / Deactivate / Activate / Approve / Reject) converted to icon buttons (inline SVG, no new package dependency), then iterated per feedback into colored icon+label pills (blue/red/green tint matching action type) for clearer at-a-glance scanning without needing hover tooltips
- Native browser `confirm()` on Deactivate and Reject replaced with a SweetAlert2 modal (`confirmAction()` helper), matching the confirmation pattern already used elsewhere in the app (Students, Enrollments, Assignments)

## QA FIXES & PATCHES — August 28, 2026

### Adviser Assignment — One-Teacher-One-Section Rule Relaxed to a Warn-and-Confirm Flow (Dean and Program Head)
- Client decision: the August 17 hard block preventing a teacher from advising more than one active section at a time was reversed in favor of a soft warning — a faculty member can legitimately advise multiple sections; the system should flag it, not forbid it
- `Dean\SectionController@changeAdviser()` and `ProgramHead\SectionController@changeAdviser()` — both rewritten so the existing-active-advisory check (`SectionTerm::where('adviser_id', ...)->where('status', 'active')`, excluding the section's own current term) no longer returns a validation error; instead it flashes an `adviser_conflict` payload (teacher name, section id, adviser id, list of conflicting sections with term labels) back to the same page and re-renders without saving
- Save now only proceeds once a `confirmed` flag is present on the request — first submission always runs the check; a second, explicit confirmation submission skips it and saves directly
- `dean/sections/index.blade.php` and `program-head/sections/index.blade.php` — both gained a `session('adviser_conflict')` listener that fires a SweetAlert2 modal listing every section the teacher already advises (with a native `confirm()` fallback if the CDN fails, consistent with the existing app-wide fallback pattern), plus a hidden `confirmAdviserForm` that resubmits with `confirmed=1` when the Dean/Program Head clicks Continue
- Conflict list UI iterated per feedback from a plain bulleted `<ul>` into individual row cards (section name bold left, term muted-gray right, scrollable past ~4 entries) with buttons recolored to match the app palette (`#c8a97e` Continue, gray Cancel) instead of SweetAlert2 defaults
- Re-saving the same adviser on their own section still does not trigger the modal (self-exclusion via `where('id', '!=', $currentTerm->id)` preserved from the original August 17 logic)
- No schema or route changes — same `POST .../change-adviser` endpoint in both role groups, same `SectionTerm` columns; verified via `route:list --name=change-adviser` that both routes remained correctly bound after the controller edit
- **Not addressed this

- Client requested a new Super Admin tab, separate from the existing Accounts and Departments pages, showing a 3-level visual drill-down: Departments (with assigned Dean) → click in → Programs within that department (with assigned Program Head) → click in → Sections within that program (with Teachers and Subjects assigned per class)
- Data relationships confirmed available for this: `Department hasMany Program`, `Department->dean()`, `Program belongsTo Department`, `Program hasMany Section`, Program Head assignment via `users.program_id`, Section → SectionTerm → `section_subject_teachers` pivot (teacher-subject-per-term)
- Not yet started — pending confirmation of exact `SectionTerm`/`AssignmentController`/`Subject` model structure (`section_subject_teachers` pivot shape, active-term scoping) before controller/view work begins
- Planned files: new `SuperAdmin\AssignmentsController` (or similar), 3 new routes/views for each drill-down level, no changes anticipated to existing Accounts or Departments features


## QA FIXES & PATCHES — August 30, 2026

### Super Admin — Departments → Programs → Sections Drill-Down (NEW FEATURE)
- New Admin tab: Departments index (card UI: program count, Dean, faculty count, student count) → drill into a Department for its Programs (Program Head, section count, faculty count, student count) → drill into a Program for its Sections (adviser, student count) → drill into a Section for Subjects & Teachers and a read-only Student roster (student no., name, year, section)
- Read-only reporting layer only — no reassignment actions added here; Dean/Program Head edit flows already own Program Head/Adviser assignment elsewhere, deliberately not duplicated
- New: `SuperAdmin\DepartmentController`, `SuperAdmin\ProgramController`; filled in previously-empty `SuperAdmin\SectionController` stub
- New model methods: `Program::programHead()`, `Program::activeFacultyIds()`, `Program::activeStudentCount()`, `Department::activeFacultyIds()`, `Department::activeStudentCount()`, `SectionTerm::scopeActive()`
- Routes added under existing `admin.` group: `departments.index`, `departments.show`, `programs.show`, `sections.show`, `sections.students`
- "Students enrolled" defined consistently as active `Enrollment` (`status = enrolled`) under the section's currently `active` `SectionTerm` — not master-list program assignment
- Bug caught during build, fixed same session: initial faculty-count queries used two different definitions per level — Department counted the raw `department_id` account field, Program counted only the subject-teacher pivot and missed advisers entirely. A teacher who was only a section adviser showed 0 faculty at Program level while counting toward Department level. Fixed by centralizing faculty counting into one shared method per model (`activeFacultyIds()`, covers both advisers and subject-teachers), called identically by both controllers — no more drift between levels
- No new migrations — built entirely on existing schema


## QA FIXES & PATCHES — September 9, 2026

### Student Number Format — HTML Pattern/Placeholder Mismatch (CRITICAL, client-reported: "student number di la gihap nakakaenter hin ungod nga student number hit student")
- Root cause traced through git history: `cc87b39` (Aug 22) introduced auto-generated student numbers in format `YY-####`; `30cede2` (Aug 26) re-tied generation to the active `AcademicPeriod`, reintroducing the school-year coupling the client had already rejected once. `dd6e1e2` (Sept 3, PHASE-13) correctly removed the generator and reverted to manual entry per client decision — but left the `pattern="[0-9]*"` HTML5 attribute untouched on all four student forms while the placeholder (`22-1251`) and the client's actual required format (`00-0000`) both use a dash. Browser-native validation silently blocked any manually-typed ID containing a dash before the form could submit.
- Confirmed via browser: typing `23-0106` triggered the native "Please match the requested format" tooltip on Program Head's create form; same defect confirmed present on Dean's create/edit and Program Head's edit forms via grep across `resources/views/`.
- Fixed: `pattern` changed to `[0-9]{2}-[0-9]{4}` with `maxlength="7"` on all four forms (`dean/students/create.blade.php`, `dean/students/edit.blade.php`, `program-head/students/create.blade.php`, `program-head/students/edit.blade.php`); backend regex in both `Dean\StudentController` and `ProgramHead\StudentController` (`store()` and `update()`) changed from `/^[0-9]+$/` to `/^[0-9]{2}-[0-9]{4}$/`; validation message updated from "Student ID must contain numbers only." to "Student ID must follow the format 00-0000."
- Verified end-to-end via `php artisan tinker` — direct model save/fetch round-tripped `23-0106` intact, confirming schema and unique constraint impose no additional format restriction; browser-level form tests still pending client confirmation on all four forms plus a negative-case test (wrong format, duplicate number).

### Known Gap Identified (not fixed — flagged for next session)
- `app/Services/StudentNumberGenerator.php`, `app/Exceptions/NoActiveAcademicPeriodException.php`, `app/Models/StudentNumberCounter.php`, and migration `2026_08_19_145019_create_student_number_counters_table.php` are fully orphaned dead code — zero callers remain anywhere in the codebase (confirmed via `git grep`) following the PHASE-13 revert to manual entry. Recommend deleting the three files and adding a migration to drop the `student_number_counters` table before capstone defense.
- `Dean\StudentController@store()`'s soft-duplicate name check (`whereRaw('LOWER(first_name)...')`) queries `Student` globally with no `department_id` scope, reopening a narrower version of the cross-department data leak addressed under the Aug 20 "Department/Program Data Isolation" fix. Not blocking, but inconsistent with every other query in this controller, which is correctly scoped.

## QA FIXES & PATCHES — October 7, 2026

### Faculty Self-Signup Re-Enabled with Super Admin Approval (client request)
- Closed Aug 20 because self-registered accounts had no department/program tie, reopening the isolation gap. Re-enabled with the department assigned by the Super Admin at approval, never chosen by the applicant
- Signup collects name, email, and password only; accounts are created as `role = null`, `status = pending_review`; `POST /register` throttled to 5 attempts per minute
- Employee ID was briefly added (`users.employee_id`) and then removed at client request — see "Employee ID Removed" below
- `User::isPending()` now includes `pending_review` — previously a pending signup logging in was told their account was "deactivated"
- Register page rebuilt to match the login design (header, placeholders, icons, show/hide password, submit spinner)

### Super Admin Accounts — Approval Modal
- Pending signups now appear under the "All" tab as well as "Pending Requests" (`index()` previously only queried accounts that already had a role, so a `role = null` request was invisible outside its own tab)
- Inline role dropdown replaced by a Review modal: role and department required for every role; program required for Program Head, optional for Teacher; SweetAlert confirmation (native `confirm()` fallback) before submit
- `approveRequest()` rewritten: validates active department / approved program, enforces one Dean per department and one Program Head per program, rejects a program outside the chosen department, sets `approved_by` / `approved_at`, and syncs the Spatie role — update and role sync run in one `DB::transaction`
- Success flash now names the approved user and role
- Review / Reject actions restyled as labelled pills

### Bug Fixes
- `UserController@update()` was nulling a Teacher's `program_id` on every edit even though the edit form shows the field; now preserved for Teacher and Program Head, with a same-department check for Teachers
- Stale Tailwind build — amber classes missing from the compiled CSS left the Pending Requests badge, selected tab text, and Pending Review badge unstyled; resolved with `npm run build`

### Deployment Notes
- Run `php artisan migrate` and `npm run build` on the server (`public/build` is gitignored)

### Employee ID Removed (client request)
- Dropped `users.employee_id` with a new forward migration `2026_10_07_100000_drop_employee_id_from_users_table` (the add migration was already pushed, so it was left in place instead of being edited)
- Removed from signup validation and form, `User::$fillable`, and the Super Admin Review modal / Review button

### Known Gaps (not fixed)
- Rejected applicants see the "deactivated" login message and cannot re-register with the same email (`unique:users,email`)
- `storeAccount()` calls `assignRole()` only for `program_head`, while `update()` and approval sync every role
- A Teacher's `program_id` is stored, but no query scoping teachers by program has been verified yet

## QA FIXES & PATCHES — October 9, 2026

### Manage Faculty Wording + Admin Dashboard Cards (client request)
- `admin/deans/index.blade.php` heading changed from "Accounts" to "Faculty"
- `admin/dashboard.blade.php` Quick Links replaced with a 5-card Font Awesome icon grid: Manage Faculty, Manage Subjects, Departments, Academic Period, Backup & Restore
- Route names stay `admin.deans.*` so nothing else breaks; the "Deans" stat card is unchanged since it counts `role = dean` only

### Subject Units Now Accept Decimals (client request)
- Root cause: `subjects.units` was `int(11)` and validation was `required|integer|min:1|max:10`
- `doctrine/dbal` is not installed, so `->change()` is unavailable; new migration `2026_10_09_000000_change_units_to_decimal_on_subjects_table` uses raw SQL (`DECIMAL(3,1) NOT NULL DEFAULT 3.0`, `down` restores `INT`)
- `ProgramHead\SubjectController` `store()` / `update()`: rule is now `required|numeric|decimal:0,1|min:0.5|max:10`
- `program-head/subjects/create` and `edit`: `step="0.1"`, `min="0.5"`
- `Subject` model: `'units' => 'float'` cast so `3.0` displays as `3` and `2.5` as `2.5`

### Invisible Exam Header on Class Record (client-reported)
- Root cause: the color `PALETTE` classes live only in `app/Models/GradeConfiguration.php`, which was missing from the `content` array in `tailwind.config.js`, so Tailwind never compiled classes that appear only there (`bg-purple-500`, `bg-orange-500`, `bg-teal-500`, `bg-rose-500`). Blue and green only worked because they also appear in other Blade views
- Fixed: `./app/Models/GradeConfiguration.php` added to `content`, then `npm run build`

### Student Gender Field + Roster Grouping (client request)
- New migration `2026_10_09_000100_add_gender_to_students_table`: nullable enum `gender` (`male`, `female`) after `middle_name`; `Student::$fillable` updated
- Shared partial `resources/views/partials/gender-field.blade.php` (required select, supports `old()` and `$student->gender`) included in all four student forms (Dean and Program Head, create and edit)
- Validation `'gender' => 'required|in:male,female'` in `store()` / `update()` of `Dean\StudentController` and `ProgramHead\StudentController`
- `SampleDataSeeder` students now carry gender
- New `Enrollment::sortRoster($enrollments)`: Male, then Female, then unset; last name then first name inside each group, case-insensitive
- Applied to Class Record and Excel export, Attendance (`index`, `summary`), Grades (`scores`, `finalGrades`), and the roster loops in `teacher/classes/show`, `dean/enrollments/show`, `dean/sections/show`, `program-head/sections/show`, `program-head/dashboard`
- Intentionally unchanged: student list pages (column-header sorting), the Dean enrollment dropdown, and grade computation loops

### Class Record — Gender Filter and Order Toggle (client request)
- `sortRoster($enrollments, $gender = null, $order = null)`: optional gender filter and `order=id` (Student No.) alongside the default A-Z; every other caller keeps its current behavior
- `Teacher\ClassController` `record()` and `export()` now take `Request` and pass `gender` / `order` into `sortRoster`; the Print route reuses `record()`, so it follows automatically
- Because filtering happens before the grade loops, Class Average, the Passed count, and header chips follow the students currently shown
- `teacher/classes/record.blade.php`: filter chips (All / Male / Female) and order chips (A-Z / Student No.) built with `request()->fullUrlWithQuery()`; Print and Export Excel links carry `gender` and `order` so screen, print and Excel match
- On All, plain divider rows (Male / Female / No gender set) with numbering restarting at 1 inside each group; on Male or Female, only that group, no divider; no-gender students appear only under All
- Search box also hides a divider when none of its rows match; empty state reads "No male students enrolled." when a filter has no results
- `ClassRecordExport`: new `$gender` constructor argument, divider rows merged and styled, numbering restarts per group, class average follows the rows passed in
- Verified via `php -l` on all changed PHP files and tinker (`all`, `male`, `female` lists sorted by Student No. returned the expected order); browser check of dividers, Print and Excel still pending

### Grade Configuration — Save Button Not Clickable (client-reported)
- Root cause: `updateTotals()` in `teacher/grades/config.blade.php` set `submitBtn.disabled` whenever a period did not total exactly 100%. The client's photo showed Final Period at 80%, so the button was disabled by design with no explanation
- Fixed: `disabled` removed from `#submitBtn`; each period total now shows "(N% remaining)" or "(N% over)" via `periodNote()`; totals stored in `window.configTotals`; the submit handler blocks with the existing red banner, for example "Final is 80%. Each period must total exactly 100%."
- No backend change: `storeConfig()` already enforces 100% server-side

### Approve Multiple Subject Requests (client request)
- Checkbox flow with Select all and one **Approve Selected (N)** button replaces the earlier "Approve All" idea; this also resolved the Admin question, since the Admin picks exactly which requests to approve
- New routes: `POST /admin/subjects/approve-selected` (`admin.subjects.approve-selected`) and `POST /dean/subjects/approve-selected` (`dean.subjects.approve-selected`)
- `approveSelected(Request)` in `SuperAdmin\SubjectController` and `Dean\SubjectController`: validates `ids` as a non-empty integer array, acts only on `pending` rows, sets `status`, `approved_by`, `approved_at`, and clears `rejected_reason`; Dean version is also restricted to the Dean's department and Program Head requests, so tampered IDs are ignored
- `admin/subjects/index` and `dean/subjects/index`: row checkboxes, select-all, button disabled until at least one box is ticked, live count, SweetAlert confirmation with native `confirm()` fallback
- Reject stays one by one because it needs a reason

### Blank Department Column on Admin Approved Subjects (client-reported)
- Root cause: `ProgramHead\SubjectController@store` sets `program_id` but never the legacy `subjects.department` string; new subjects had `department = null, program_id = 1`, while the five seeded subjects had the string but `program_id = null`
- Fixed: `SuperAdmin\SubjectController` eager-loads `program.department`; `admin/subjects/index` displays `program?->department?->name ?? department ?? 'N/A'`

### Deployment Notes
- After `git pull` run `php artisan migrate`, `npm run build`, and `php artisan view:clear` (`public/build` is gitignored)
- Existing students have no gender until edited (column is nullable, so the migration is safe); they list last under "No gender set" and the Male / Female filters stay empty until genders are set through Edit Student. A one-time `UPDATE` by student number on the client's server is the lowest-effort alternative

### Known Gaps (not fixed)
- Browser checks still open: gender selection on all four student forms, Class Record / Print / Excel matching with the filter and order toggle, Grade Config remaining/over text and Save banner, Approve Selected on Admin and Dean, Department column fill, Program Head accepting `2.5` units, Exam header after a hard refresh
- `admin/subjects/create.blade.php` and `edit.blade.php` still validate units as integer, but no route points to them
- `dean/subjects/index.blade.php` and `admin/subjects/index.blade.php` still use `@extends('layouts.app')` instead of `<x-sidebar-layout>` (pre-existing)
- SweetAlert2 CDN-fallback pattern is still patched per file; the reusable `<x-confirm-form>` component is still planned
- `php artisan migrate:fresh --seed` would give seeded students a gender but wipes all current data

## QA FIXES & PATCHES — October 9, 2026 (Session 2)

### Program Head Student Form — Middle Name Field
- Middle Name field added to the Program Head student create form (`program-head/students/create.blade.php`) to match the Dean's form

### Dean Sections — Year-Level Separators
- `dean/sections/index.blade.php` now groups sections under year-level separators so 1st to 4th Year sections no longer run together in one list

### Class Record — Header Fallback
- `teacher/classes/record.blade.php`: `bg-gray-700` added to the component header row as a safety net for the invisible header (Attendance (F))
- The original cause of that invisible header is still unconfirmed here; the Tailwind `content` fix recorded earlier today is the likely root cause, and this fallback is not a diagnosis

### My Advisory — Gender Filter, Order Toggle, and Search (client request)
- `teacher/classes/show.blade.php`: filter chips (All / Male / Female), order chips (A-Z / Student No.), and a client-side search box (student name or number, with a "Showing N of M" counter)
- Gender and order are server-side via the query string, using the existing `Enrollment::sortRoster($enrollments, $gender, $order)`; search is client-side, so behavior matches Class Record
- No controller, model, or CSS rebuild needed; only classes already in the compiled build were used
- Empty state reads "No male students enrolled." when a filter has no results

### Contrast Fixes — Sidebar and Native Dropdowns (client-reported: light text unreadable)
- Sidebar: inactive link color raised from 0.45 to 0.75 alpha (about 2.6:1 to about 5:1; WCAG AA needs 4.5:1), hover to 0.95, and the extra `opacity-60` on icons removed (`layouts/partials/sidebar-link.blade.php`)
- Sidebar email line 0.45 to 0.75 and Logout 0.65 to 0.85 (`layouts/partials/sidebar.blade.php`)
- `layouts/app.blade.php`: global rule `select option { color: #1c1814; background-color: #ffffff; }`. The browser option popup is always white, so cream text on it was unreadable. It lives in Blade, not `app.css`, so no build is required

### Light-Card Conversion — One Design Language (partial)
- Root cause: seven pages used dark `#211a12` cards with inline cream/tan text on a light app body (`bg-stone-100`); two design languages meant every contrast patch was a band-aid
- Converted to white cards with Tailwind classes: `dean/dashboard`, `dean/assignments/index`, `teacher/grades/items` (redundant `p-6` wrapper removed since the layout already pads the page)
- Not yet converted: `admin/backup/index`, `admin/users/create`, `program-head/dashboard`, `teacher/grades/config` (its JS builds rows with inline dark colors, so the JS strings change too)

### Duplicate Subject Codes Allowed, with Department-Scoped Confirmation (client request)
- Root cause: codes were blocked in two places, the DB unique index on `subjects.code` and `unique:subjects,code` in `ProgramHead\SubjectController` `store()` and `update()`. The rule and index also counted soft-deleted rows, so a cancelled request blocked its code forever
- New migration `2026_10_09_000200_allow_duplicate_subject_codes`: drops `subjects_code_unique`, adds a plain index on `code`. Rollback fails if duplicate codes exist, by design
- New `guardDuplicateCode()` in `ProgramHead\SubjectController`, called from `store()` and `update()`; it checks only the subject's own department and only `pending` and `approved` subjects
  - Same code in another department: saved silently, no names shown (preserves department isolation)
  - Same code in own department (other program, or same program with a different name): flashes `duplicate_subjects` and shows a Continue / Cancel dialog; the server saves only when resubmitted with `confirm_duplicate=1`
  - Same code, same name, same program: blocked with "This subject already exists in your program."
- New partial `program-head/subjects/partials/duplicate-dialog.blade.php` (SweetAlert listing code, name, program, department, status; every value escaped); `create` and `edit` views gained the hidden `confirm_duplicate` input, the include, and `id="subject-form"` on the edit form
- Approval by Dean or Super Admin does not re-check duplicates; the request already passed the check or was confirmed at creation

### Seeder — Re-Seeding No Longer Duplicates Sample Subjects
- `SampleDataSeeder.php` line 132: `Subject::create` changed to `Subject::firstOrCreate(['code', 'name'], [...])`; without the unique index, a second `db:seed` would otherwise silently duplicate the sample subjects
- Matches on code and name, so re-seeding will not update existing rows

### Verification
- Duplicate-code dialog confirmed in browser (own-department match shown with code, name, program, department, status; form values kept)
- Migration, controller guard, view wiring, and seeder patch confirmed via file checks, `php -l`, and `git diff --stat`
- Still pending browser checks: Cancel and Continue on the dialog, same code + name + program error, edit-flow behavior, other-department silent save, My Advisory filters and search, sidebar and dropdown contrast for all four roles, and the three converted light-card pages

### Deployment Notes
- Deploy must include `php artisan migrate` (otherwise the old DB unique index still blocks duplicate codes) and `npm run build` (`public/build` is gitignored)

### Known Gaps (not fixed)
- Light-card conversion is incomplete (four pages listed above)
- My Advisory and Class Record always list males before females, even when ordered by Student No. (inside `sortRoster`)
- `resources/views/components/sidebar-link.blade.php` appears unused; confirm with `grep -rn "x-sidebar-link" resources app` before deleting

---

## QA FIXES & PATCHES — October 10, 2026

### Formal Name Format — Students (client request)
- Client decision: all student names display as `Lastname, Firstname M.` across the system
- Root cause: `Student::full_name` returned `First Middle Last`, and several views built names by hand with inconsistent results (no middle initial, full middle name, or last name only)
- `Student::getFullNameAttribute()` rewritten: `Dela Cruz, Juan M.`, or `Reyes, Jose` when there is no middle name
- Replaced hand-built names with `full_name` in `dean/enrollments/show`, `dean/sections/show`, `program-head/sections/show`, `dean/students/index`, `program-head/students/index`, and `ClassRecordExport` (Excel)
- Intentionally unchanged: the duplicate-name check in `dean/students/create` (not a display), form inputs, and the column-header sort links

### My Advisory — Male/Female Separator Rows (client request)
- `teacher/classes/show.blade.php`: on All, MALE / FEMALE / "No gender set" divider rows with numbering restarting at 1 per group; on Male or Female, one group with no divider; matches the Class Record behavior
- Search box hides a divider when none of its rows match (client-side, `tr.roster-divider` toggle)
- Remove-confirmation call now passes the name through `addslashes()`, so a name with an apostrophe (e.g. `D'Souza`) no longer breaks the inline JavaScript
- No controller or model change; reuses `Enrollment::sortRoster()`

### Faculty / Dean / Program Head Names — Structured Fields (client request)
- Decision: lists, tables, dropdowns and labels show `Lastname, Firstname M.`; the logged-in user's own identity (sidebar, avatar initial, "Welcome back") shows natural order `Firstname M. Lastname`
- Root cause: `users.name` was a single free-text field, so a reliable last-name-first format could not be produced or enforced
- Migration `2026_10_10_000000_add_name_parts_to_users_table`: nullable `last_name`, `first_name`, `middle_name`; `users.name` is kept
- New trait `App\Models\Concerns\HasFormalName` (used by `User`): a `saving` hook rebuilds `name` as `Lastname, Firstname M.` whenever first and last name are present; `display_name` accessor returns natural order and falls back to `name` for legacy accounts; `User::nameRules()` is the single shared validation rule set (letters, spaces, `.`, `'`, `-`; middle name optional)
- Because `name` stays a real column, roughly 40 existing `->name` displays became formal with no view changes
- Super Admin keeps the single `name` field (no real last name): `ProfileUpdateRequest` and the profile form branch on `role === 'super_admin'`
- Updated to the three-field input and shared rules: Admin Create Account, Admin Edit Account, faculty signup (`RegisteredUserController`), Profile, and `UserController` (`store`, `storeAccount`, `update`)
- `User::$fillable` gained the three columns
- `SuperAdminSeeder`: seeded accounts now use structured names
- Existing accounts converted once via tinker (ids 2 to 8); output confirmed, e.g. `Sample, Teacher` (name) and `Teacher Sample` (display_name)
- `admin.deans.create` / `admin.deans.store` routes removed only if nothing referenced them (guarded `grep` before the `sed`)

### Incident During Rollout
- The `User.php` patch was applied twice, producing a duplicate `use App\Models\Concerns\HasFormalName;` and a fatal error ("name is already in use") on every request
- Fixed by de-duplicating the import and `$fillable` lines; confirmed one import, one trait use, one entry per column, `php -l` clean
- Lesson: the patch steps were not idempotent for `User.php`; later `grep -c` checks confirmed no other file was patched twice

### Verification
- Migration ran clean; tinker conversion output confirmed for all seven accounts
- Confirmed counts: `nameRules` x3 in `UserController`, `roster-divider` x2 in `teacher/classes/show.blade.php`, one `last_name` input each in the Admin Create and Edit forms
- Pending: `php artisan view:cache` full Blade compile, `php -l` sweep of the other changed files, and browser checks (My Advisory dividers, Class Record names, sidebar natural order, Admin Create / Edit forms, signup, profile)

### Known Gaps (not fixed)
- Account id 9 ("test faculty") has no structured name; it must be fixed through Admin > Faculty > Edit (the form shows empty required name fields for unconverted accounts)
- `tests/Feature/ProfileTest.php` posts a single `name` for a factory user and will fail now that profile requires the three name parts
- `UserController::create()` and `store()` are unreachable dead code after the route removal; delete in a cleanup commit
- Other labels that print another person's `->name` (e.g. "Adviser:", "Dean:", "Teacher:") now show the formal format; switch to `display_name` per label if natural order is preferred there
- Deploy: run `php artisan migrate`, then convert the real production accounts (ids differ from local)

---

## QA FIXES & PATCHES — October 16, 2026

### Batch 1 — UI (client request)
- Browser tab title: `APP_NAME` default and `.env.example` set to "Class Record System"
- Desktop sidebar fixed in place (`position: sticky`) so Logout stays visible
- New `<x-back-button>` component; blue "← Back" links converted to buttons; back buttons added to Grade Config, Class Record and the Admin department / program / section pages

### Batch 2 — Print, Submit, Backup (client request)
- Class Record Print now calls `window.print()` with a landscape `@media print` stylesheet; filters, buttons and sidebar hidden
- Submit for Verification falls back to native `confirm()` when `Swal` is undefined
- Backup: `backup:run` exit code is checked and the real error is shown; `SystemRoot` passed to `mysqldump` on Windows

### Batch 3 — Teacher Dashboard (client request)
- "My Advisory" and "Subjects I'm Teaching" cards replaced by one collapsible card per subject class: `CODE — SUBJECT NAME`, Year & Section, Semester, S.Y., student count, Male / Female tables (`Stud. ID`, `Names`)
- First card open, others collapsed (native `<details>`); classes sorted by program, year, section, subject code
- Students with no gender appear in a third "No gender set" table
- Eager-loads `enrollments.student`; reuses `Enrollment::sortRoster()`

### Grading engine (Phase 2)
- Characterization tests for grade conversion (`tests/Unit/GradeConversionTest.php`) and the INC rules (`tests/Unit/GradeIncTest.php`)
- Grades display to one decimal; formula A/B conversions round half up to one decimal; the average is the half-up average of the two grades (`FinalGrade::averageGrade`); the Scores page live preview uses the same rounding
- INC at submit: a weighted component with no scores, or a total of zero, makes that period incomplete (`GradeCalculator::incompletePeriods`, `applyInc`); the hard block in Submit for Verification is removed
- Final Grades shows a non-blocking review list of students who will be marked INC
- New `php artisan grades:recompute` — recomputes stored `final_grades` under the current rules; dry run by default, `--apply` writes, rows already verified are kept unless `--include-verified`; uses each row's own academic period for the cutoff date

### Attendance (Phase 3)
- Attendance is present / absent only; existing late and excused records converted to present (migration `2026_10_16_000000_attendance_present_absent_and_sessions`)
- New `subjects.has_laboratory` flag (Program Head subject create / edit). Lab subjects record Lecture and Laboratory attendance separately on the same date (`attendance_records.session_type`; the unique key now includes it)
- Attendance rate = present sessions / all recorded sessions
- Consecutive absences: `AttendanceRecord::ABSENCE_WARNING = 3`, `consecutiveAbsences()`, `streaksFor()`. Warning popup on save, badge on the Attendance page, icon on the Class Record, "Absent streak" column on Summary
- Attendance save rejects enrollments that are not in the class (403)
- Tests: `AttendanceTest`

### Inline score entry (Phase 3)
- Class Record score cells are editable inputs, saved on change through `teacher.grades.score.cell` (`GradeController@storeScore`); totals refresh in place
- A blank cell clears the score (soft delete). A cleared score is written to `grade_change_logs` with `new_score = NULL` (migration `2026_10_16_000100_allow_null_new_score_on_grade_change_logs`)
- The bulk `storeScores` form now shares `saveScore()`

### Class Record items (Phase 3c1)
- Header row "Midterm | Finals" above the components
- Every weighted component shows in the Class Record, even with no items yet (`buildComponentMatrix($items, true)`; the Excel export is unchanged)
- "+ Add" on a component header opens a dialog (date, highest possible score); "+ Take" on Attendance opens the attendance page. Hidden once grades are submitted
- Item names are assigned by the server (`GradeItem::autoName()`): "Quiz 1", "Quiz 2", ...; exam components (key starting with `exam`) hold one item per period, named "Midterm Examination" / "Final Examination"
- `storeItem()` takes the period from the grade configuration, not the form
- Tests: `GradeItemAutoNameTest`

### Test Safety
- `phpunit.xml` runs tests on the `class_record_test` database (create it once) with `BCRYPT_ROUNDS=10`. Before this, `php artisan test` ran `RefreshDatabase` against the `.env` database and wiped it
- `.phpunit.result.cache` ignored

### Known Gaps (not fixed)
- Dropped students still listed (no enrollment status filter yet)
- Backup restore still accepts only `.sql` / `.txt` and runs it through `DB::unprepared`
- `teacher.classes.record.print` route no longer linked from the UI
- Unenrolling a student hard-deletes the enrollment and cascades to scores and attendance (soft drop planned)
- Inline score save refetches the whole Class Record page for each cell (slow on large classes)
- 5 default Breeze auth tests still fail (under triage)
- Attendance date columns on the Class Record not built yet; the old Grade Items and Scores pages still exist
- Stored `final_grades` rows keep their old values until `php artisan grades:recompute --apply` is run (run it after the Phase 3 attendance change)

---

## QA FIXES & PATCHES — October 17, 2026

### Report of Grades (Phase 4)
- Printable Report of Grades replicating form ESSU-ACAD-712.b: 8.5 × 13 in (Long), logos, course block, grading legend, MALE and FEMALE pages (25 rows each, more pages when needed), signature block, form code / version / effectivity and "Page x of y" footer
- Built only from submitted grades (`ReportOfGrades::isSubmitted`, status pending or verified); INC periods print "INC"
- Teacher: "Report of Grades" button on Final Grades after submission (`teacher.rog.show`). The Program Head sets the Schedule of Classes when adding or updating a subject on the section (the Dean on Assignments): day checkboxes M, T, W, Th, F, S (Saturday), Su (Sunday) and a start-end time, saved as `schedule_days`, `schedule_start`, `schedule_end`; a time that overlaps another subject of the same section on a shared day is rejected with the conflicting subject named (`ClassSchedule::conflict`); the ROG prints it as "10:30-12:00PM (MW)" and shows it read-only
- Program Head: "View Report of Grades" on the dashboard (`program-head.rog.show`), own program only. Dean: "View ROG" on the section page (`dean.rog.show`), own department only
- Admin → ROG Settings: replace or reset the left and right logos (stored on the `public` disk, needs `php artisan storage:link`), form code, version, effectivity date, Registrar name (`settings` table, `Setting::rog()`)
- Profile: Academic Rank and Credentials for faculty, Program Heads and Deans, printed under their names (`users.academic_rank`, `users.credentials`)
- Default logos in `public/images/rog/` (taken from the official form)
- Tests: `RogSettingsTest`, `ReportOfGradesTest`, `ClassScheduleTest`
- One INC-aware result everywhere (`GradeCalculator::result()`): Final Grades, Class Record, Excel export, submit and the ROG now agree. Before this, Final Grades and the Class Record showed a grade (skipping empty components) while the submitted record and the ROG said INC; Remarks now shows INC instead of "Locked"

### Known Gaps (not fixed)
- The printed legend (55-69% = Failed) does not match the S/TS formulas used to compute grades
- "Dr" (dropped) is not printed yet: dropping a student still deletes the enrollment (Phase 5)

---

### Roster and soft drop (Phase 5a)
- New `subject_enrollments` table and `enrollments.type` (`regular` / `irregular`), migration `2026_10_17_000200_create_subject_enrollments_and_enrollment_type`
- `SectionTerm::enrollments()` now means section members only (regular, not dropped); section pages, counts and advisory rosters follow automatically
- `SectionTerm::rosterFor($subjectId)` = section members not dropped from the subject + irregular students added to it; used by the Class Record, Scores, Attendance, Final Grades, Submit, Excel export, ROG, teacher dashboard and Program Head verification
- Removing a student (Dean Enrollments, teacher class page) marks the enrollment `dropped` instead of deleting it; scores, attendance and final grades are kept; enrolling the same student again restores the record
- The ROG prints dropped students with "Dr" in Remarks and blank grades
- Score and attendance saves reject students who are not on the subject roster
- Tests: `RosterTest`


### Teacher class list (Phase 5b)
- New Class List page per subject (`teacher.classes.students`), opened from the **Students** button on the Class Record
- Search all active students by number or name (min 3 characters, first 20 matches, any program) and add them to this subject only; new students are enrolled as `irregular` with a `subject_enrollments` row
- Drop a student from this subject (records kept, "Dr" on the ROG) and restore them; students dropped from the whole section can only be re-enrolled by the Program Head or Dean
- Only the assigned subject teacher can change the list; it is locked once grades are submitted (pending or verified)
- New `Teacher\SubjectRosterController`; tests: `SubjectRosterTest`


### Program Head enrollment (Phase 5c)
- Program Head section page: **Enroll** (active students of the program not yet in the section), **Drop** per student, and a **Dropped** list with **Re-enroll**
- New routes `program-head.sections.enroll` and `program-head.sections.drop`; the Dean keeps the Enrollments page
- Enroll and drop logic moved to `App\Services\SectionEnrollment`, shared by the Program Head and the Dean (program check, duplicate check, restore on re-enroll, soft drop)
- Retired the teacher section-level enroll/unenroll (`teacher.classes.enroll` / `teacher.classes.unenroll`, the Add Student and Remove modals); teachers manage their own subject list on the Class List page (Phase 5b)
- Tests: `SectionEnrollmentTest`


### Student import (Phase 5d)
- **Import** button on the Student Master List (Program Head: own program; Dean: chooses a program of their department) with a header-only CSV template download
- Accepts .xlsx or .csv (max 2 MB, 1,000 rows); columns `student_number, last_name, first_name, gender, year_level` required, `middle_name, student_type, email` optional; headers matched case-insensitively
- Normalizes M/F, Male/Female and year levels 1–5 / "1st" / "1st Year"; student type defaults to regular
- All or nothing: any invalid row (format, gender, year level, duplicate email, number repeated in the file) imports nothing and lists every problem by row number
- Student numbers that already exist are skipped and reported, never overwritten
- New `App\Services\StudentImport`, `partials/student-import.blade.php`; routes `program-head.students.import/template`, `dean.students.import/template`; tests: `StudentImportTest`


### Scope layer and Admin View As (Phase 6a)
- New `App\Services\Scope` (`departmentId()`, `programId()`, `role()`): Deans and Program Heads use their own assignment; an Admin uses the department or program picked in **View As**
- All 68 hard-coded `auth()->user()->department_id` / `program_id` reads in the Dean, Program Head and ROG controllers now go through `Scope`
- Admin → **View As**: work in any department as its Dean or any approved program as its Program Head; sidebar shows that role's menu with a "Viewing as" banner and **Back to Admin**; actions are recorded under the Admin account
- `CheckRole`: an Admin passes `role:dean` / `role:program_head` only while viewing as that role; otherwise redirected to View As
- Fixed: Program Head subjects were filtered by `requested_by` (a new Program Head could not see or edit the previous one's requests); now scoped by program
- Tests: `ViewAsTest`


### Dean faculty management (Phase 6b)
- New Dean → **Faculty** page: active / inactive teachers of the department with search, this term's load (subjects taught, sections advised) and a link to pending approvals
- **Deactivate** (status `inactive`, cannot sign in, records kept) and **Reactivate**; never deleted. Deactivation is refused while the teacher still has subjects or advisory sections in an active term
- Dean sections are view-only (`index`, `show`): create / edit / delete routes, methods and views removed; the Program Head creates and edits sections. The Dean keeps subject / teacher / adviser assignment
- New `Dean\FacultyController`; tests: `DeanFacultyTest`


### Admin departments and programs (Phase 6c)
- Admin → Departments: **Add Department**; department page: **Edit** and **Add Program**; program page: **Edit** (code + name, code stored uppercase, unique)
- Programs created by the Admin are `approved` immediately and appear in every program dropdown (students, import, users, View As)
- No delete: users, sections, students and subjects reference departments and programs
- Every department / program field on forms is already a dropdown fed by these records, so S1 item 10 ("department field and program field") is covered
- Shared `admin/partials/code-name-dialog.blade.php` reopens with the errors after a failed save; tests: `AdminDepartmentTest`


### Subject year level and semester (Phase 6d1)
- `subjects.year_level` (1st–5th Year) and `subjects.semester` (1st Semester / 2nd Semester / Summer), nullable; migration `2026_10_17_000300_add_year_level_and_semester_to_subjects`
- Program Head: both required on Request / Edit; a Year / Semester selector on every row of the Subjects page (also for approved subjects) to fix existing data
- Program Head Subjects lists every subject of the program (no pagination) grouped "1st Year · 1st Semester" … in curriculum order; unset subjects last with a warning
- Dean Subjects: grouped by program, then year / semester; pending requests show the year / semester. Admin approved subjects: grouped by department, then year / semester, with a Program column
- `Subject::byPlacement()`, `$subject->placement`; tests: `SubjectPlacementTest`


### Subject assignment rule (Phase 6d2)
- New `App\Services\SubjectOffering` (`forTerm()`, `refusal()`), used by the Program Head section page and modal, Dean section page and Dean Assignments
- A subject can be added to a term only if it is approved, belongs to the section's program, and matches the term's semester; Summer terms accept any semester; subjects with no semester yet stay assignable
- Dropdowns list same-semester and same-year-level subjects first and show "(3rd Year · 1st Semester)"
- Fixed: Dean Assignments and Dean Sections listed and accepted subjects from every department
- Fixed: Dean section page "Add subject" saved no schedule and ran no conflict check; it now has the day / time fields and the same conflict check as the Program Head
- Tests: `SubjectOfferingTest`


### Term rollover (Phase 6e1)
- Fixed: "Set Term" (Program Head and Dean) overwrote the active term's school year and semester with the new academic period, so last semester's enrollments, scores, final grades and ROG were relabeled as the new semester
- New `App\Services\TermRollover::setTerm()`: same period changes only the adviser; a new period closes the old term (`completed`, records kept) and starts a new active term, in one transaction
- Tests: `TermRolloverTest`


### Batch promotion (Phase 6e2)
- Dean and Program Head → **Promotion**: program cards (Dean) → year → section cards → the students of the section's last closed term
- Every checkbox starts unchecked; students with failed or INC final grades in that term are highlighted with the subject codes
- Target: any active section of the program with a term in the active academic period; the same section carries students into the next semester, a higher-year section promotes them (student year level becomes the target section's)
- One transaction through `SectionEnrollment::enroll()`; students already in the target are skipped and named; every run is logged in the new `promotions` table (who, from, to, year level, students, skipped) and listed on the section page
- Migration `2026_10_17_000400_create_promotions_table`; `App\Services\BatchPromotion`, `App\Http\Controllers\PromotionController`, `promotions/*` views; tests: `BatchPromotionTest`


### ESSU palette and seal (Phase 7a)
- Colours sampled from `public/images/rog/essu-seal.png`: ESSU green `#0a5b05` (dark `#0b3d0b`), gold `#d4af37` for text and borders (the seal's bright gold `#f3eb21` is kept as `--essu-gold-bright`, too light for text on white)
- Every view: brown / tan tones mapped to green / gold, the indigo buttons from Phases 4–6 to ESSU green; the ROG form (`rog/*`) is untouched
- ESSU seal replaces the book icon in the sidebar and on the sign-in page, and is the browser favicon
- New `layouts/partials/brand.blade.php` (CSS variables, favicon, Tailwind `indigo-*` classes mapped to ESSU green without an `npm run build`), included in the app, guest and teacher layouts; tests: `BrandTest`


### Sign-in and request-account pages (Phase 7b)
- `layouts/guest.blade.php` rewritten (773 → ~150 lines): ESSU brand panel (seal, university, campus, system name, three feature lines) beside a light form panel; below 960px a compact seal header above the form
- Removed the Lottie animation (and its CDN script), orbs, corner ornaments, background gradebook and card watermark, which rendered as stray boxes
- Sign in: sentence-case labels, white inputs with green focus ring, readable placeholders, show / hide password with an accessible label, green primary button with loading spinner
- Request an account: grouped "Your name" (last / first / middle in one row) and "Account" (email; password and confirm side by side), optional field marked, `autocomplete` name hints; one column on phones
- Page scripts moved inside the layout (`@stack('scripts')`, `auth/partials/form-scripts.blade.php`); they were printed after `</html>` before; tests: `AuthPagesTest`


### Light pages and test config (Phase 7c)
- Backup & Restore, Create Account, Program Head Grade Verification and Grade Config converted from dark cards to light cards: white cards with `#e5e7eb` borders, `#1f2937` / `#6b7280` text, dark-green headings; red / green / amber status colours darkened for white backgrounds; gold buttons kept
- `phpunit.xml`: `<coverage><include>` replaced by `<source><include>` (PHPUnit 10), which removes the "deprecated schema" warning
- Checked: `@extends('layouts.app')` pages and `<x-sidebar-layout>` pages render the same layout (it prints both `$slot` and `@yield('content')`), so no conversion is needed; tests: `LightPagesTest`


### Dead code removed (Phase 7d)
- Removed (no references left): `StudentNumberGenerator`, `NoActiveAcademicPeriodException`, `StudentNumberCounter` (+ migration `2026_10_17_000500_drop_student_number_counters_table`), `SidebarLink` component class and view, `layouts/navigation`, `layouts/teacher`, `welcome`, `dashboard`, Breeze `dropdown` / `dropdown-link` / `nav-link` / `responsive-nav-link` / `application-logo` components, unrouted `admin/subjects/create` and `edit` views
- Removed the unlinked duplicate account flow: `admin.deans.create` / `admin.deans.store` routes, `UserController::create()` / `store()`, `admin/deans/create` view (accounts are created on `admin.users.create`)
- Removed route `teacher.classes.record.print` (nothing linked to it; Print is a button on the Class Record)
- Fixed: `RouteServiceProvider::HOME` was `/dashboard`, which does not exist (email verification and password confirmation ended on a 404); now `/`, which redirects each role to its dashboard
- The patch checks every file for references before deleting it; tests: `DeadCodeTest`


### Role access tests and deploy guide (Phase 7e)
- `RoleAccessTest`: every main page of every role opened by every role: the owner gets 200, other roles 403, the Admin is sent to View As for Dean / Program Head pages, guests are sent to sign in; every role can open Profile
- New `DEPLOY.md`: server requirements, first install (roles seeder only, real Admin account, never the sample seeders), update steps, grade recompute, backup routine (backups are not scheduled), smoke test by role, rollback


### Class list locked after submission (Phase 8a)
- Fixed: a Program Head or Dean could drop (or re-enroll) a student from the section after a subject was submitted, which changed that subject's ROG to "Dr"
- `SectionEnrollment::enroll` / `drop` refuse while any subject of the term is pending or verified, and name the subjects; the Program Head rejects the submission first (unverify, then reject, for a verified one). Same rule as the teacher Class List
- `ClassListLockTest`


### Set Term warning (Phase 8b)
- Set Term in a new period now lists the current term's subjects that are not verified (not submitted, awaiting verification, returned to the teacher) and closes the term only after "Close Term Anyway"; a closed term is no longer listed, so those subjects could not be finished afterwards
- `TermRollover::unfinished`; Program Head and Dean Sections pages; `TermCloseWarningTest`


## QA FIXES & PATCHES — October 18–19, 2026 (client round 2)

### Lecture and laboratory schedules (Phase 9a)
- Client: a subject with lecture and laboratory on different days and times, same teacher, could not be assigned ("A teacher is already assigned to that subject"); one assignment had one schedule only
- `section_subject_teachers.lab_days / lab_start / lab_end` (migration `2026_10_18_000100`); `ClassSchedule` checks the lecture against its laboratory and both against every other subject's schedules; `ClassSchedule::summary` prints "Lec …; Lab …"
- `SubjectAssignment` service used by the Program Head section page, the Dean section page and Dean Assignments (before: three copies; the Dean section page could not change the schedule); refuses an inactive teacher; "already assigned … Use Edit"
- Subject type radio (lecture only / lecture and laboratory) on Request / Edit Subject; "Lec + Lab" badge on Program Head, Dean and Admin subject lists
- Tests: `LabScheduleTest`

### Class Record grades and back buttons (Phase 9b)
- Midterm Grade and Finals Grade columns after each period's components, live-updated, INC per period (`GradeCalculator::applyInc` adds `incomplete_periods` when a period is INC); same columns in the Excel export
- `TeacherNav`: `?from=teaching|advisory|dashboard` carried through the class pages and redirects; Grade Config, Attendance, Students, Final Grades and Items lead back to the Class Record; Class Record back to its origin; unknown values fall back to My Subjects
- Tests: `ClassRecordNavigationTest`

### Enrollment (Phase 9c)
- One regular section per student per semester (`SectionEnrollment::otherSection`); the enroll list offers only students without a section this semester (`availableStudents`)
- Enroll by search (`partials/student-picker`); Remove for an enrollment made by mistake (`SectionEnrollment::remove`, `removableIds`; teacher: irregular students of that subject only), refused once there are scores, attendance or final grades
- Found: the Dean's "Remove" icon actually dropped the student; now separate Drop and Remove, plus a Dropped list with Re-enroll
- Found: no link led to the Program Head section page (enrollment); "Manage Students" added to the section card
- Tests: `EnrollmentRulesTest`, `SectionEnrollmentTest`

### Search and locked class list (Phase 9e)
- Search while typing (`layouts/partials/live-search`, debounce 400 ms; minlength respected); Search buttons hidden
- `NameSearch` (server) and `EssuSearch` (browser), one rule: every typed word must start a word of the last / first name or a student-number part; e-mail only with "@"; matches highlighted. Before, "de" matched every "student.test" e-mail
- Locked class list: notice instead of enroll / drop / remove / re-enroll while a subject is submitted or verified
- SweetAlert2 buttons in ESSU green by default
- Tests: `LiveSearchAndLockNoticeTest`, `SearchRuleTest`, `NameSearchTest`

### Departments and programs (Phase 9f1)
- Errors of the code / name dialogs shown once, inside the dialog (the layout popup is skipped for dialog forms)
- Program dialogs show program examples (BSIT / Bachelor of Science in Information Technology)
- Deactivate / Activate (programs.status gains `inactive`, migration `2026_10_19_000100`) and Delete (only while unused; `forceDelete`, since a soft-deleted row kept the code taken)
- `DB_DUMP_PATH` in `.env` for the `mysqldump` folder (default `C:/xampp/mysql/bin/`)
- Tests: `AdminDepartmentStatusTest`

### Test safety
- If tests fail with database errors and 0 assertions, recreate the test database: `DROP DATABASE class_record_test; CREATE DATABASE class_record_test …` (an interrupted run left foreign-key leftovers in it, October 18)

## QA FIXES & PATCHES — October 20, 2026 (client round 3)

### New term starts with an empty Class Record (Phase 10a)
- Found while checking the client's "archive" request: `grade_items` and `grade_configurations` were keyed by section and subject only. A section taking the same subject in a later term showed the old items, continued their numbering ("Quiz 6"), refused a new "Midterm Examination" and reused the old weights
- `section_term_id` on both tables; unique key `(section_term_id, subject_id)` on configurations; `SectionTerm::gradeItemsFor()` / `gradeConfigurationFor()` replace the section-level methods (removed); every caller moved (Grade Config, Items, Scores, inline scores, Final Grades, Submit, Class Record, Excel export, `grades:recompute`, Program Head dashboard and Reject)
- A grade item is scored, deleted or opened only in its own term (403 otherwise)
- Migration backfill: an item goes to the term of the students scored on it (split into one copy per term when scored in several); an unscored item to the term current when it was created; a configuration is copied to every term that used it. The old unique key had to be replaced by an explicit `section_id` index first (MySQL had dropped the foreign key's own index; checked on MariaDB 10.11)
- Tests: `TermGradeRecordTest`

### Past Classes (Phase 10b)
- `Teacher\PastClassController`: closed terms (`completed`) of the signed-in teacher, grouped by semester; read-only Class Record (same view, `readOnly`), Attendance Summary and Report of Grades; another teacher gets 403, an active term 404
- `App\Services\ClassRecordData` builds the Class Record data for the current and the past view (one computation); a closed term uses its own academic period's midterm cutoff
- Tests: `PastClassesTest`

### Report of Grades: Nothing Follows (Phase 10c)
- First empty row after the last student of each list (MALE, FEMALE, GENDER NOT SET); on row 1 of an empty list; not printed on a full page of 25
- Tests: `ReportOfGradesTest`

### Attendance Summary per session (Phase 10d)
- `App\Services\AttendanceSheet`: sessions in date order (lecture before laboratory), Midterm / Finals by the cutoff (one "Sessions" group without a cutoff), marks per student, totals, streak, rate; same sheet in Past Classes
- Tests: `AttendanceSheetTest`

### Grade Config order and defaults (Phase 10e, 10e-2)
- ▲ / ▼ per component within its period; the controller already saved the submitted order and the Class Record already followed it
- `GradeConfiguration::DEFAULT_COMPONENTS`: Quiz 20, Project 15, Assessment 15, Attendance 10, Exam 40 per period; applies only to classes without a saved configuration
- Tests: `GradeConfigOrderTest`

### Rounding (Phase 10f)
- `FinalGrade::roundGrade()` = `ceil(grade × 10 − 0.5 − 0.000001) / 10`: exactly half goes down, above half up; used by both formulas, the standard table, `averageGrade()` and `GradeCalculator::formatGrade()`; the Scores page preview uses the same rule (checked equal to PHP on all 30,003 percentage × method values)
- Changed expectations in `GradeConversionTest` are the values exactly on a half (95.99 → 1.2, 90.99 → 1.7, 85.99 → 2.2, 79.99 → 2.7; averages 1.55 → 1.5, 1.35 → 1.3, 1.15 → 1.1); new rounding cases added
- Run `grades:recompute` after deploying

### ESSU background (Phase 10g)
- `body.app-shell` (signed-in layout) in `brand.blade.php`: off-white, ESSU-green dot grid, gold glow, seal watermark at 6% opacity; plain white when printing
- Tests: `BrandTest`

### Backup command (development note)
- `mysqldump` with `localhost` failed on Windows (error 2002, 0-byte file): `localhost` can resolve to IPv6 while XAMPP's MariaDB listens on IPv4. Use `-h 127.0.0.1 -P 3306`, the same host Laravel uses

## OPEN KNOWN GAPS — CONSOLIDATED CHECKLIST (as of October 20, 2026)

Every open gap from Phases 1–4 and earlier sessions in one place. Tick an item (`[x]`) and add the date and commit when it is fixed or decided. The dated sections above keep the history.

### Decisions and sign-offs (client)
- [ ] Written approval from Sir Roger that grades use the S/TS formulas (major 5 − 4(S/TS), minor 4 − 3(S/TS)) while the printed ROG legend says 55-69% = Failed; with the formulas, 3.0 (passing) falls at 50% (major) and 33.3% (minor)
- [ ] Confirm the Registrar name on Admin → ROG Settings (sample form: REGINA L. DOCENA, DBA)
- [x] Default grade components set: Quiz 20, Project 15, Assessment 15, Attendance 10, Exam 40 per period; "Security" removed — October 20, 2026 (Phase 10e-2); Ma'am Mich may still send her own list
- [ ] Written yes from Sir Roger to the rounding rule as built (Midterm 3.0 + Finals 3.1 → Average 3.05 → 3.0 Passed)
- [ ] Teacher double-booking (same teacher, two sections, same time) is not checked; only subjects inside the same section are (`ClassSchedule::conflict`)
- [ ] Component grade: total ÷ total (current, DepEd style) or average of item percentages (client's wording) — Sir Roger (guide D6); blocks 9d
- [ ] Teachers from other departments (e.g. GE subjects): any subject or GE only (guide D7)
- [ ] Programs with majors (BSED English / Filipino): Major field or duplicate codes (guide D8); blocks 9f2
- [ ] Lecture and laboratory units shown separately? (guide D9)
- [ ] Consecutive-absence warning at 3 or 4 (guide D10; currently 3)

### Grading (Phase 2)
- [ ] Run `php artisan grades:recompute` then `--apply` on the client server after deploy; stored `final_grades` keep old values until then
- [ ] The grade conversion is still ported to JavaScript for the live preview on the Scores page (`teacher/grades/scores.blade.php`); it uses the same rule as `FinalGrade::roundGrade` and matched PHP on every value (October 20), but it must be updated together with any future formula change
- [ ] A weighted component with no items, attendance with no records, or no midterm cutoff on the active Academic Period makes the period INC for every student (rule as approved; teachers must be told)

### Class Record and attendance (Phase 3)
- [x] Attendance with a column per date (✓ / ✗) — done October 20, 2026 on the Attendance Summary (Phase 10d); the Class Record keeps "present / total"
- [ ] The old Grade Items and Scores pages still exist alongside "+ Add" and inline entry in the Class Record
- [ ] Inline score save re-fetches the whole Class Record page for every cell (slow on large classes)
- [ ] Attendance migration `down()` fails when a student has lecture and laboratory records on the same date; converted late / excused marks cannot be restored
- [x] The Dean's subject approval pages show the subject type — done October 18, 2026 (Phase 9a)

### Report of Grades (Phase 4)
- [x] "Dr" (dropped) does not print — fixed October 17, 2026 (Phase 5a soft drop)
- [x] Dean → Assignments schedule can be changed — done October 18, 2026 (Phase 9a: Edit opens the Dean's section page, same panel as the Program Head)
- [ ] Faculty without structured names (last / first name) print their old single `name` on the ROG signature block
- [ ] Students with no gender set print on an extra "GENDER NOT SET" page

### Enrollment and students
- [x] Unenrolling a student hard-deletes the enrollment — fixed October 17, 2026 (Phase 5a: marked dropped, records kept)
- [x] Dropped students listed on the teacher dashboard — fixed October 17, 2026 (Phase 5a roster)
- [ ] No UI to change a student's status
- [ ] `Dean\StudentController@store` soft-duplicate name check is not department-scoped
- [x] Global student search, per-subject class roster and student import — done October 17, 2026 (Phase 5b–5d)
- [x] Section-level drop after a subject was submitted — fixed October 17, 2026 (Phase 8a: class list locked while any subject is pending or verified)

### Roles and accounts
- [x] Admin all-access, Dean faculty management, departments / programs CRUD, subject year level and semester — done October 17, 2026 (Phase 6a–6d)
- [ ] Subjects with no semester set are still assignable in any term; once every subject has a year level and semester, make `SubjectOffering` refuse unset ones
- [ ] General-education subjects owned by one program cannot be assigned to another program's section (assignment is program-only)
- [ ] Assigned teacher's department is not checked on the server — active-teacher check done October 18, 2026 (Phase 9a, `SubjectAssignment`); the department rule waits for D7 (teachers from other departments)
- [ ] Seeded sample subjects (CS101 etc.) have no program; they appear only under Admin "No department" and cannot be assigned
- [x] Completed terms are browsable by teachers — Past Classes, October 20, 2026 (Phase 10b)
- [x] Set Term warning for unverified subjects — done October 17, 2026 (Phase 8b)
- [ ] Promotion cannot be undone from the UI (drop the students from the target section; the year level must be corrected by editing the student)
- [ ] Admin View As covers Dean and Program Head pages only; Admin cannot open a teacher's Class Record (ROGs are visible through View As Dean / Program Head)
- [ ] Rejected sign-ups see the "deactivated" message and cannot re-register with the same email (`unique:users,email`)
- [ ] `UserController@storeAccount` assigns the Spatie role only for `program_head`; `update()` and approval sync every role
- [ ] Teacher `program_id` is stored but no query scoping by it has been verified
- [x] Dean section page and Assignments duplicated the assignment rules — both use `SubjectAssignment` since October 18, 2026 (Phase 9a)
- [ ] Sweep Phases 1–8 for `nullable` combined with `required_if` and for `$fillable` gaps (not started)

### Backup
- [ ] Restore accepts only `.sql` / `.txt` and runs the upload through `DB::unprepared`; it cannot restore the `.zip` backups the system creates
- [x] `mysqldump` path from `.env` (`DB_DUMP_PATH`) — done October 19, 2026 (Phase 9f1)
- [ ] The client reported "Run Backup not working"; waiting for the exact error message (guide D11)
- [ ] Backups are not scheduled (see `DEPLOY.md` §5)

### UI and dead code
- [x] Light-card conversion — done October 17, 2026 (Phase 7c)
- [x] `@extends('layouts.app')` vs `<x-sidebar-layout>` — no change needed: both render the same layout (Phase 7c)
- [ ] Reusable `<x-confirm-form>` not built; the SweetAlert2 CDN fallback is still patched per file
- [ ] My Advisory and Class Record always list males before females, even when ordered by Student No.
- [x] ESSU palette and the ESSU seal in place of the book icon — done October 17, 2026 (Phase 7a)
- [x] Dead code removed — done October 17, 2026 (Phase 7d)

### Client testing (v1.0-rc3)
- [ ] Client tests the round-3 changes (Phase 10) and returns bug reports
- [ ] Client runs the v1.0-rc2 Client Testing Guide (Word / PDF) and returns the step marks and bug reports
- [ ] Answers to decisions D1–D11 (guide Part 9) recorded under "Decisions and sign-offs"
- [ ] INC rule confirmed with the client: a zero total in any weighted component (for example absent at every session) makes the period INC, not failed

### Before production
- [ ] Change the default seeder passwords (on the server: never run the sample seeders; see DEPLOY.md)
- [ ] Server: `php artisan migrate`, `php artisan storage:link` (once), `npm run build`, `php artisan view:clear`, then `grades:recompute --apply`; back up the database first
- [ ] Production `.env`: `APP_NAME`, `APP_ENV=production`, `APP_DEBUG=false`, HTTPS

---

## ROADMAP (after client testing)

### Next — v1.0-rc4: client decisions and round-4 reports
- 9d component grade computation (after D6), 9f2 programs with majors (after D8), teachers from other departments (after D7), lecture / laboratory units (after D9)
- Bugs reported by the client, sorted into bugs, client decisions and misunderstandings before any code changes
- 8d undo last promotion

### Later: Reporting & Analytics (planned)
- Teacher: class performance summary, grade distribution, failing students alert, attendance trends
- Dean: department-wide stats, teacher performance, section comparison, consolidated reports
- Activity logs — grade changes, login history, export to Excel

### UI/UX (partly done)
- Done: SweetAlert2 confirmations with native fallbacks, inline score entry on the Class Record (Phase 3), gender filter and order toggle on the Class Record and My Advisory, ESSU palette and light pages (Phase 7)
- Open: breadcrumbs, mobile audit, keyboard navigation (Tab / Enter / arrows) between score cells, reusable `<x-confirm-form>`

### Testing & Deployment (partly done)
- Done: 245 automated tests (role access, grading, rounding and INC, Report of Grades, enrollment rules and class list lock, lecture and laboratory schedules, search, term rollover and term-scoped records, past classes, attendance sheet, promotion), separate `class_record_test` database, login rate limiting (5 attempts), `DEPLOY.md`
- Open: production server with HTTPS, scheduled backups, real Admin account and no sample seeders on the server, `grades:recompute --apply` after deploy

---

**Last Updated:** October 20, 2026  
**Current Release:** v1.0-rc3 (client testing)  
**Next Milestone:** v1.0-rc4 — client decisions (rounding sign-off, D1–D11) and round-4 reports  
**Maintained By:** Frances Igop
