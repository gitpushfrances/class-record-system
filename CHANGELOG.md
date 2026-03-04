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
- Create & approve Subjects
- Configure Academic Year & Semester
- Archive old data
- View system-wide stats (Deans, Active Teachers, Students, Subjects, Sections)

### 🎓 Dean
- Approve / reject Teacher registrations (individual or bulk)
- Manage all Faculty — view, deactivate
- Create Sections & assign Teachers
- Add & manage Students (master list owner)
- Tag students as Regular or Irregular
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
- Subjects — created and approved by Super Admin only

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
- `subjects` — catalog with units and status
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
- Subject CRUD
- Dean Management CRUD (create, edit, activate/deactivate)
- Academic Period — add school year + semester, set active, delete inactive
- Dashboard stats — Deans, Active Teachers, Students, Subjects, Sections
- Navigation: Dashboard → Deans → Subjects → Academic Period

### Dean ✅
- Teacher Approval — approve/reject (reject sets `status = rejected`, does NOT delete)
- Section CRUD
- Student Master List CRUD — add, edit, tag Regular/Irregular
- Enrollment Management — assign students to sections
- Navigation: Dashboard → Pending Teachers → Sections → Enrollments → Students

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
- Locked grades show 🔒 icon

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
- All section creates and updates were failing silently or throwing DB errors
- Fixed: validation rules updated to match enum values exactly

### SectionController — Missing `show()` Method
- Route `dean.sections.show` existed but `show()` method was never implemented
- Hitting the route threw a 500 error
- Fixed: `show()` added, loads subject/teacher/enrollments, returns `dean.sections.show` view

### Section Create Form — Wrong Field Name
- Form was posting `name` but DB column and validation expect `section_name`
- Fixed: input `name` attribute and `old()` key updated to `section_name`

### Dean Navigation — Missing Students Link
- `dean.students.*` routes and `StudentController` existed but no nav link in either desktop or mobile nav
- Students feature was completely invisible to Dean
- Fixed: Students link added to both desktop and mobile blocks in `layouts/navigation.blade.php`

### Student Views — Entirely Missing
- `resources/views/dean/students/` folder did not exist
- All three views created from scratch: `index.blade.php`, `create.blade.php`, `edit.blade.php`
- Matches existing section view style — same table structure, same form patterns

### Section Show View — Missing
- `resources/views/dean/sections/show.blade.php` did not exist
- Created: shows section details (subject, teacher, schedule, room, status) + enrolled students table with link to manage enrollments

### Student Model — Missing `student_type` in `$fillable`
- `student_type` column exists in migration and is validated in controller
- Was not in `$fillable` — silently not saving on create/update
- Fixed: `student_type` added to `Student::$fillable`

---

## PHASE 8: SIDEBAR NAVIGATION 📅 NEXT
**Target:** Replace top navbar with a persistent sidebar for all roles

- Sidebar replaces `layouts/navigation.blade.php` top nav
- Role-specific menu items per sidebar
- Active state highlighting per route
- Collapsible on mobile
- Avoids constant "Back" button navigation — all sections accessible from sidebar at all times

---

## PHASE 9: REPORTING & ANALYTICS 📅 PLANNED

- Teacher: class performance summary, grade distribution, failing students alert, attendance trends
- Dean: department-wide stats, teacher performance, section comparison, consolidated reports
- Activity logs — grade changes, login history, export to Excel

---

## PHASE 10: UI/UX POLISH 📅 PLANNED

- Toast notifications and confirmation modals
- Breadcrumb navigation
- Mobile responsiveness
- Inline score editing, keyboard navigation (Tab/Enter/Arrow)
- Filter and sort on class record view

---

## PHASE 11: TESTING & DEPLOYMENT 📅 PLANNED

- Feature tests — core workflows, grade calculation accuracy, permission checks
- Production `.env` configuration, HTTPS, rate limiting on login
- Change default seeder passwords before production

---

**Last Updated:** March 4, 2026  
**Next Milestone:** Phase 8 — Sidebar Navigation  
**Maintained By:** Frances Igop
