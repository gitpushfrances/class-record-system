# 📚 Class Record Management System

A comprehensive Faculty Class Record Management System built with Laravel 10, designed for educational institutions following the Philippine DepEd grading system.

![Status](https://img.shields.io/badge/Status-In%20Development-yellow)
![Laravel](https://img.shields.io/badge/Laravel-10.x-red)
![PHP](https://img.shields.io/badge/PHP-8.4-blue)
![License](https://img.shields.io/badge/License-MIT-green)

---

## 🎯 Project Overview

This system streamlines grade management and class record keeping with a DepEd-style spreadsheet interface, automated Philippine grade scale calculations, and role-based access control for Super Admins, Deans, and Teachers.

### Key Features
- ✅ Three-tier Role System (Super Admin → Dean → Teacher)
- ✅ Centralized Student Management (Dean owns master list, no duplicates)
- ✅ Flexible Grading System (unlimited items per component)
- ✅ Automated Grade Calculation (semester-based cumulative, live display)
- ✅ DepEd-Style Class Record (spreadsheet with frozen columns, color-coded components)
- ✅ Philippine Grading Scale (1.00–5.00 auto-conversion)
- ✅ Complete Audit Trail (all grade changes logged)
- ✅ Academic Period Management (school year + semester config)
- ✅ Excel Export — DepEd-format `.xlsx` with color-coded headers, averages, weighted scores
- ✅ Sidebar Navigation — unified dark sidebar layout across all roles, matches login design system
- ✅ Subject Approval Workflow — Dean requests subjects per department, Super Admin approves with timestamp
- ✅ Two-layer Section Model — persistent sections + per-semester section terms + term-scoped enrollments
- ✅ Grade Rescaling — live grades calculated only from components with actual data entered
- ✅ Teacher-Side Student Enrollment — Add Student modal with master list search, remove button per student
- ✅ Cascading Student Delete — deleting a student from master list also removes all their enrollments
- ✅ Font Awesome Icons — all hardcoded emojis replaced with FA 6.5.1 icons across all views
- ✅ Styled Confirmation Modals — Dean student delete uses a proper modal instead of browser confirm()

---

## 🚀 Current Status: Phase 8 Complete + QA Fixes ✅

**Completed:**
- ✅ Phase 1: Foundation Setup
- ✅ Phase 2: Database Architecture (11 custom tables, 24 total)
- ✅ Phase 3: Authentication & Authorization
- ✅ Phase 4: Academic Structure (Students, Subjects, Sections, Enrollments, Dean Management, Academic Periods, Teacher Approval)
- ✅ Phase 4 Revised: Subject Approval Workflow (Dean requests, Super Admin approves)
- ✅ Phase 5: Grading System (Config, Items, Scores, Attendance, Final Grades, Lock)
- ✅ Phase 6: DepEd-Style Class Record Interface (Spreadsheet, Frozen Columns, Averages)
- ✅ Phase 7: Excel Export (DepEd format, color-coded, auto-filename)
- ✅ Phase 8: Sidebar Navigation (unified layout, role-aware, mobile-responsive)
- ✅ QA Fixes (March 15): Schema migration cleanup, enrollment rewrite, grade calculation fix, academic period view, attendance auth fix
- ✅ QA Fixes (March 23): Teacher enrollment UI, emoji replacement, student delete modal, cascading delete fix

**Next: Phase 9 — Reporting & Analytics**

See [CHANGELOG.md](CHANGELOG.md) for detailed progress and patch notes.

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 10 LTS |
| Language | PHP 8.4.11 |
| Database | MySQL 8.0 |
| Frontend | Blade + Tailwind CSS |
| Auth | Laravel Breeze |
| Roles | Spatie Permission v5.11 |
| Excel | Maatwebsite Excel v3.1+ |
| Audit | Spatie Activity Log v4.8+ |
| Icons | Font Awesome 6.5.1 |
| Fonts | Fraunces + DM Sans (Google Fonts) |
| Build | Vite |

---

## 📦 Installation
```bash
# 1. Clone
git clone https://github.com/yourusername/class-record-system.git
cd class-record-system

# 2. Install dependencies
composer install --ignore-platform-req=php
npm install

# 3. Environment
cp .env.example .env
php artisan key:generate

# 4. Configure .env
# DB_DATABASE=class_record_system
# DB_USERNAME=root
# DB_PASSWORD=

# 5. Migrate and seed
php artisan migrate:fresh --seed

# 6. Build assets
npm run build

# 7. Serve
php artisan serve
```

Visit: `http://localhost:8000`

> ⚠️ If deploying on a new machine, always run `php artisan migrate` first. The `section_terms` and related tables must exist before the app can function.

---

## 🔑 Test Accounts

| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@classrecord.test | password |
| Dean | dean@classrecord.test | password |
| Teacher (Active) | teacher@classrecord.test | password |
| Teacher (Pending) | pending@classrecord.test | password |

> ⚠️ Change these passwords before any production deployment.

---

## 👥 Role Summary

### 👑 Super Admin
- Manage Dean accounts (create, edit, activate/deactivate)
- Review & approve / reject Subject requests from Deans (with timestamp and reason)
- Configure Academic Year & Semester (set active period, delete inactive)
- View system-wide stats

### 🎓 Dean
- Approve / reject Teacher registrations (pending teachers from self-registration)
- Manage Faculty (view, deactivate)
- Create Sections (tied to program, year number, section letter — free text)
- Assign Teachers as section advisers per term
- Own the Student master list (add, edit, tag Regular/Irregular)
- Manage enrollments (assign students to section terms)
- Request new Subjects (pending until Super Admin approves)
- Approve Teacher grade config change requests

### 📝 Teacher
- Self-register (pending until Dean approves)
- View assigned classes only (sections where they are the active term adviser)
- Enroll students from master list into their section term via searchable modal
- Remove students from their class
- Configure grade weights (must = 100%)
- Enter grades and attendance
- View live auto-calculated final grades (rescaled to active components only)
- Save and lock grades officially
- View DepEd-style class record spreadsheet
- Export class record to Excel (.xlsx)

---

## 👩‍🏫 Teacher Registration Flow

1. Teacher goes to `/register` and fills in name, email, password
2. Account created with `role = teacher`, `status = pending`
3. Teacher is redirected to login with a "pending approval" message — **cannot log in yet**
4. Dean goes to `dean/teachers/pending` and approves or rejects
5. On approval — `status` becomes `active`, teacher can now log in
6. Teacher logs in and sees sections where they are assigned as adviser in an active term

> Dean and Super Admin accounts are **not** created via self-registration. Deans are created directly by the Super Admin via the admin panel.

---

## 🎓 Teacher Student Enrollment Flow

```
Teacher opens class → Enrolled Students section →
Click "+ Add Student" →
Modal opens with searchable master list (active students only) →
Type to filter by name or student number →
Click student → Enrolled instantly →
Student appears in class list with Pending status

To remove: Click "Remove" on any enrolled student row →
Student unenrolled immediately
```

- Only active students from the master list appear in the modal
- Already-enrolled students are excluded from the modal automatically
- Removing a student from the class does not delete them from the master list

---

## 📋 Subject Workflow
```
Dean → Request Subject (Code, Name, Department, Units) →
SweetAlert preview → Confirm → Status: Pending

Super Admin → Subject Management → Pending tab →
Approve (with confirm) OR Reject (reason required) →
Timestamp logged → Dean notified via flash message
```

- Dean can edit or cancel requests while status is `pending`
- Once approved, subject is locked — Dean cannot edit
- Rejected requests show reason inline in Dean's subject list

---

## 🏗️ Section Architecture

Sections use a two-layer model to support persistence across semesters:

```
sections          — permanent group (e.g. BSCS 3-A)
  └── section_terms   — per-semester instance (holds adviser_id, academic_year, semester, status)
        └── enrollments   — students enrolled in that specific term
```

- A section exists permanently tied to a `program`
- Each semester, a `section_term` is created for that section with an assigned adviser (teacher)
- Students are enrolled per term, not permanently per section
- Grade items, attendance, and final grades are all scoped to the active `section_term`

---

## 🗑️ Student Deletion Behavior

When a Dean removes a student from the master list:
- A styled confirmation modal displays the student's full name before proceeding
- All enrollment records for that student are deleted first (cascading delete)
- The student record is then soft-deleted from the master list
- The student disappears from all teacher class views immediately
- This prevents orphaned enrollments that would show as N/A on the teacher side

---

## 🧮 Grading System

### Formula
```
Component Score = (Total Earned / Total Possible) × Component Weight
Final Grade     = Sum of all component scores
```

### Mid-Semester Rescaling
If not all components have data yet (e.g. only quizzes and attendance entered), the system rescales grades to reflect only the active components:
```
Active Weight   = sum of weights for components with data
Rescale Factor  = 100 / Active Weight
Adjusted Score  = Raw Score × Rescale Factor
```
This prevents students from showing as Failed just because exams or projects haven't been entered yet.

### Grade Components
Quiz, Exam, Project, Assessment, Attendance — weights configurable per section, must total 100%

### Philippine Grade Scale
| Percentage | Grade |
|-----------|-------|
| 97–100% | 1.00 |
| 94–96% | 1.25 |
| 91–93% | 1.50 |
| 88–90% | 1.75 |
| 85–87% | 2.00 |
| 82–84% | 2.25 |
| 79–81% | 2.50 |
| 76–78% | 2.75 |
| 75% | 3.00 (Passing) |
| Below 75% | 5.00 (Failed) |

> Passing threshold: numerical grade ≤ 3.00. Remarks are based on the numerical grade, not the raw percentage.

---

## 📥 Excel Export

Teacher can export any assigned class record as a `.xlsx` file directly from the Class Record view.

**Filename format:** `{ProgramCode}_{YearNumber}-{SectionLetter}_{Semester}_{AY}.xlsx`

**File contents:**
- Rows 1–4: School info, subject, teacher, section, AY, semester
- Row 6: Component group headers with configured weights
- Row 7: Individual grade item names with max scores
- Rows 8+: Student data — scores per item, weighted component scores, attendance, final %, numerical grade, remarks
- Last row: Class averages per column

---

## 🗂️ Layout System

All roles share a single sidebar layout (`resources/views/layouts/partials/sidebar.blade.php`).

- **Fonts:** Fraunces (serif brand) + DM Sans (body) — matches login page
- **Palette:** Warm dark brown sidebar (`#1c1814`), sand accent (`#c8a97e`), cream text
- **Icons:** Font Awesome 6.5.1 — used across all views, no hardcoded emojis
- **Role badges:** Yellow = Super Admin, Green = Dean, Sand = Teacher
- **Mobile:** Off-canvas sidebar with overlay, hamburger trigger in top bar
- **No Alpine.js dependency** — pure CSS sticky + vanilla JS toggle

---

## 📈 Roadmap

| Phase | Status | Description |
|-------|--------|-------------|
| Phase 1 | ✅ Complete | Foundation setup |
| Phase 2 | ✅ Complete | Database architecture |
| Phase 3 | ✅ Complete | Authentication & authorization |
| Phase 4 | ✅ Complete | Academic structure & role management |
| Phase 4 (revised) | ✅ Complete | Subject approval workflow — Dean requests, Admin approves |
| Phase 5 | ✅ Complete | Grading system |
| Phase 6 | ✅ Complete | DepEd class record interface |
| Phase 7 | ✅ Complete | Excel export |
| Phase 8 | ✅ Complete | Sidebar navigation |
| QA Fixes (Mar 15) | ✅ Complete | Schema migration cleanup, enrollment rewrite, grade calculation fix, academic period view, attendance auth, subject request flow |
| QA Fixes (Mar 23) | ✅ Complete | Teacher enrollment UI, emoji → FA icons, student delete modal, cascading delete fix |
| **Phase 9** | 📅 **Next** | **Reporting & analytics** |
| Phase 10 | 📅 Planned | UI/UX polish + inline editing |
| Phase 11 | 📅 Planned | Testing & deployment |

---

**Last Updated:** March 23, 2026  
**Version:** 1.0.0-alpha  
**Maintained By:** Frances Igop
