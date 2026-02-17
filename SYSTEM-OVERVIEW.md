# SYSTEM OVERVIEW - Class Record System

## 📋 Project Summary

**System Name:** Faculty Class Record Management System  
**Purpose:** Streamline grade management and class record keeping for educational institutions  
**Client Requirements:** Philippine DepEd-style class record interface with Excel export  

---

## 🎯 Core Objectives

1. **Centralized Student Management** - Single source of truth for student data
2. **Flexible Grading System** - Support unlimited grade items per component type
3. **Automated Calculations** - Semester-based cumulative grade computation
4. **Excel-like Interface** - Familiar spreadsheet view for teachers
5. **Role-Based Access** - Three-tier permission system (Super Admin → Dean → Teacher)
6. **Audit Trail** - Track all grade changes for accountability

---

## 👥 User Roles & Permissions

### Super Admin
**Access Level:** Full system control

**Capabilities:**
- Manage all users (approve/reject/deactivate)
- Import student master list from Excel
- Create/edit/delete students manually
- Manage subject catalog
- View all system activities
- Access all reports
- Configure system settings

**Workflow:**
1. Approve new teacher registrations
2. Import/manage master student database
3. Create subject catalog
4. Monitor system-wide activities

---

### Dean
**Access Level:** Department/college management

**Capabilities:**
- Enroll students to sections
- Create and manage sections
- Assign teachers to sections
- Approve grade configurations
- View department-wide reports
- Reassign teachers if needed

**Workflow:**
1. Create sections for the semester (e.g., CS101-3A)
2. Enroll students from master list to sections
3. Assign teachers to sections
4. Review and approve grade component weights
5. Monitor class performance across department

---

### Teacher
**Access Level:** Assigned classes only

**Capabilities:**
- View enrolled students (read-only)
- Define grade component weights
- Create grade items (quizzes, exams, projects)
- Input student scores
- Mark attendance
- View auto-calculated final grades (live, no button needed)
- Save and lock final grades officially
- View DepEd-style class record spreadsheet
- Export class record to Excel (Phase 7)

**Workflow:**
1. Self-register account (pending approval)
2. Access assigned sections
3. Configure grade components (Quiz 5%, Exam 40%, etc.)
4. Create grade items (Quiz 1, Midterm Exam, etc.)
5. Input scores per grade item per student
6. Mark attendance daily
7. View Final Grades page — live calculated grades always visible
8. Click Save Grades to officially record to database
9. Lock grades when finalized
10. Export class record to Excel

---

## 📊 System Architecture

### Technology Stack

**Backend:**
- Laravel 10 LTS (PHP Framework)
- MySQL 8.0 (Database)
- PHP 8.4.11

**Frontend:**
- Blade Templates
- Tailwind CSS
- Alpine.js (planned for Phase 9 inline editing)
- Vite

**Key Packages:**
- Laravel Breeze (Authentication)
- Spatie Laravel Permission (Role/Permission management)
- Maatwebsite Excel (Excel import/export)
- Spatie Activity Log (Audit trail)
- Laravel Debugbar (Development tool)

---

## 🗄️ Database Schema

### Custom Application Tables (11)

```
students            — Master student list (soft deletes)
subjects            — Subject catalog (code unique)
sections            — Class sections with teacher assignment
enrollments         — Student-to-section pivot (soft deletes)
grade_configurations — Component weights per section (must sum to 100%)
grade_items         — Individual quizzes, exams, projects per section
student_grades      — Score per student per grade item (unique: enrollment+item)
attendance_records  — Daily attendance (unique: enrollment+date)
final_grades        — Saved computed grades with lock support
grade_change_logs   — Audit trail for every score change
```

---

## 🧮 Grading Calculation Logic

### Formula
```
Component Score = (Total Earned / Total Possible) × Component Weight

Final Grade = Quiz Score + Exam Score + Project Score + Assessment Score + Attendance Score
```

### Live vs Saved Grades
- **Live:** Calculated in-memory on every page load from `student_grades` and `attendance_records`. No DB write. Always up to date.
- **Saved:** Written to `final_grades` table when teacher clicks "Save Grades". Required for locking.
- **Locked:** `is_locked = true`, `locked_at` timestamp set. Cannot be overwritten by compute.

### Philippine Grade Scale (1.0–5.0)
```
97-100% = 1.00 (Excellent)
94-96%  = 1.25
91-93%  = 1.50
88-90%  = 1.75
85-87%  = 2.00 (Very Good)
82-84%  = 2.25
79-81%  = 2.50
76-78%  = 2.75
75%     = 3.00 (Passing)
Below 75% = 5.00 (Failed)
```

---

## 📑 Class Record Interface ✅ IMPLEMENTED (Phase 6)

### Layout (DepEd-Inspired Spreadsheet)

```
┌────┬──────────┬──────────────────┬─────────────────────┬──────────────────┬──────────┐
│ #  │ Stud. No │ Student Name     │ Quiz (5%)           │ Exam (40%)       │ Summary  │
│    │          │                  │ Q1/50 │ Q2/50 │ Wtd │ E1/100 │ Wtd    │ % │ Gr │ Rem │
├────┼──────────┼──────────────────┼───────┼───────┼─────┼────────┼────────┼───┼────┼─────┤
│ 1  │2021-0001 │ Juan M. Dela Cruz│ 45/50 │ 48/50 │4.50 │ 90/100 │ 36.00  │40.50%│5.00│Failed│
└────┴──────────┴──────────────────┴───────┴───────┴─────┴────────┴────────┴───┴────┴─────┘
│ Class Average │                  │ 43.4  │       │4.34 │  83.6  │ 33.44  │43.78%│5.00│0/5 Passed│
```

**Features Implemented:**
- ✅ Frozen columns (#, Student No., Name) — CSS sticky positioning
- ✅ Color-coded component groups (blue, purple, green, orange, teal)
- ✅ Two-row header (group + individual item)
- ✅ Scores in `45/50` format
- ✅ Weighted score column per component
- ✅ Attendance in `18/20` format
- ✅ Class averages footer row
- ✅ Pass/Fail count in footer
- ✅ Live data — always current
- ✅ Print button (full export in Phase 7)

**Deferred to Phase 9:**
- Inline editing (click cell to edit)
- Keyboard navigation (Tab/Enter/Arrow)
- Filter/sort/search
- Collapsible component groups

---

## 📤 Excel Export Specifications (Phase 7 — Next)

### Class Record Export Format

**Sheet Structure:**
```
Row 1: School Header
Row 2: Section Details (Subject, Section, Teacher, Semester, AY)
Row 3: Grade Configuration Weights
Row 4-5: Column Headers (group + individual)
Row 6+: Student Data
Last Row: Class Averages
```

**Formatting:**
- Bold headers
- Borders on all cells
- Percentage format for attendance and final grade
- Color-coded component columns matching web view
- Auto-width columns
- Freeze header rows and name column

**File Naming:**
```
{SubjectCode}_{Section}_{Semester}_{AY}.xlsx
Example: CS101_3A_1stSem_2024-2025.xlsx
```

---

## 🔮 Future Enhancements (Post-Phase 10)

### Real-Time Grade Updates
**Stack:** Laravel Echo + Redis + Soketi or Pusher  
**Scope:**
- Final Grades and Class Record pages update live when scores are saved
- No page refresh needed
- Dashboard stats update in real-time

**Why deferred:** Core system stable and functional. Redis + WebSockets adds significant infrastructure overhead best tackled post-deployment.

### Other Planned Features
- Mobile app (React Native)
- Parent portal
- SMS notifications for failing students
- Advanced analytics with Chart.js/ApexCharts
- Multi-language support
- Dark mode
- Automated report scheduling
- Grade trending analysis
- API for third-party integrations

---

## 🔐 Security Measures

- Laravel Breeze session-based authentication
- CSRF protection on all forms
- Spatie Permission middleware on all routes
- Role-based + owner-based access control (teachers see only their sections)
- SQL injection prevention (Eloquent ORM)
- XSS protection (Blade escaping)
- Mass assignment protection (`$fillable`)
- Full audit trail via `grade_change_logs`

---

## 📈 Development Roadmap

| Phase | Status | Description |
|-------|--------|-------------|
| Phase 1 | ✅ Complete | Foundation setup |
| Phase 2 | ✅ Complete | Database architecture |
| Phase 3 | ✅ Complete | Authentication & authorization |
| Phase 4 | ✅ Complete | Academic structure management |
| Phase 5 | ✅ Complete | Grading system |
| Phase 6 | ✅ Complete | DepEd-style class record interface |
| Phase 7 | 📅 Next | Excel export |
| Phase 8 | 📅 Planned | Reporting & analytics |
| Phase 9 | 📅 Planned | UI/UX polish + inline editing |
| Phase 10 | 📅 Planned | Testing & deployment |

**Overall Progress: 60%**

---

**Last Updated:** February 17, 2026  
**Version:** 1.0.0-alpha  
**Status:** In Development — Phase 7 Next
