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
- Define grade component weights (pending Dean approval)
- Create grade items (quizzes, exams, projects)
- Input student scores
- Mark attendance
- View auto-calculated final grades
- Export class record to Excel
- View assigned section details

**Workflow:**
1. Self-register account (pending Super Admin approval)
2. Access assigned sections
3. Configure grade components (Quiz 20%, Exam 30%, etc.)
4. Create grade items (Quiz 1, Quiz 2, Midterm Exam, etc.)
5. Input scores and attendance
6. Monitor auto-calculated grades
7. Lock and submit final grades
8. Export class record to Excel

---

## 📊 System Architecture

### Technology Stack

**Backend:**
- Laravel 10 LTS (PHP Framework)
- MySQL 8.0 (Database)
- PHP 8.4.11

**Frontend:**
- Blade Templates (Laravel's templating engine)
- Tailwind CSS (Utility-first CSS framework)
- Alpine.js (Minimal JavaScript framework)
- Vite (Build tool)

**Key Packages:**
- Laravel Breeze (Authentication)
- Spatie Laravel Permission (Role/Permission management)
- Maatwebsite Excel (Excel import/export)
- Spatie Activity Log (Audit trail)
- Laravel Debugbar (Development tool)

---

## 🗄️ Database Schema

### User Management
```
users
├── id
├── name
├── email
├── password
├── role (via Spatie)
├── status (pending/active/inactive)
└── timestamps

Relationships:
- hasMany(ClassAssignment)
- belongsToMany(Role) via Spatie
```

### Academic Structure
```
students
├── id
├── student_number (unique)
├── first_name
├── last_name
├── year_level
└── timestamps

subjects
├── id
├── code (unique, e.g., CS101)
├── name
├── description
├── units
└── timestamps

sections
├── id
├── subject_id (FK)
├── name (e.g., 3A)
├── year_level
├── semester (1/2/Summer)
├── academic_year (2024-2025)
└── timestamps

Relationships:
- subjects: belongsTo(Subject)
- sections: hasMany(ClassAssignment), hasMany(Enrollment)
```

### Class Management
```
class_assignments
├── id
├── section_id (FK)
├── teacher_id (FK)
├── status (active/inactive)
└── timestamps

enrollments
├── id
├── student_id (FK)
├── section_id (FK)
└── timestamps

Relationships:
- Linking table between teachers/students and sections
```

### Grading System
```
grade_configurations
├── id
├── section_id (FK)
├── quiz_weight (decimal)
├── exam_weight (decimal)
├── project_weight (decimal)
├── assessment_weight (decimal)
├── attendance_weight (decimal)
├── status (pending/approved)
├── approved_by (FK to users)
└── timestamps

Validation: All weights must sum to 100%

grade_items
├── id
├── section_id (FK)
├── component_type (quiz/exam/project/assessment)
├── title (Quiz 1, Midterm Exam, etc.)
├── max_score
├── date
└── timestamps

student_grades
├── id
├── enrollment_id (FK)
├── grade_item_id (FK)
├── score
├── recorded_by (FK to users)
└── timestamps

attendance_records
├── id
├── enrollment_id (FK)
├── date
├── status (present/absent/late/excused)
└── timestamps

final_grades
├── id
├── enrollment_id (FK)
├── computed_grade (decimal)
├── letter_grade (1.0-5.0)
├── locked (boolean)
├── locked_at
└── timestamps
```

---

## 🧮 Grading Calculation Logic

### Semester-Based Cumulative System

**Principle:** Each component is calculated as a percentage of total possible points, then multiplied by its weight.

### Formula
```
Component Score = (Total Earned / Total Possible) × Component Weight

Final Grade = Quiz Score + Exam Score + Project Score + Assessment Score + Attendance Score
```

### Example Calculation

**Grade Configuration:**
- Quizzes: 20%
- Exams: 30%
- Projects: 25%
- Assessments: 15%
- Attendance: 10%

**Student Performance:**

**Quizzes:**
- Quiz 1: 45/50
- Quiz 2: 48/50
- Quiz 3: 50/50
- Total: 143/150
- Percentage: 143/150 = 95.33%
- Weighted: 95.33% × 20% = **19.07/20**

**Exams:**
- Midterm: 85/100
- Final: 90/100
- Total: 175/200
- Percentage: 175/200 = 87.5%
- Weighted: 87.5% × 30% = **26.25/30**

**Projects:**
- Project 1: 90/100
- Project 2: 95/100
- Total: 185/200
- Percentage: 185/200 = 92.5%
- Weighted: 92.5% × 25% = **23.13/25**

**Assessments:**
- Assessment 1: 48/50
- Total: 48/50
- Percentage: 48/50 = 96%
- Weighted: 96% × 15% = **14.4/15**

**Attendance:**
- Present: 18 days
- Total sessions: 20 days
- Percentage: 18/20 = 90%
- Weighted: 90% × 10% = **9/10**

**Final Grade:**
```
19.07 + 26.25 + 23.13 + 14.4 + 9 = 91.85/100
```

**Letter Grade (Philippine 1.0-5.0 Scale):**
- 91.85% = **1.25** (Excellent)

### Grade Scale (Philippine System)
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

## 📑 Class Record Interface (Phase 6 - High Priority)

### Layout Design (DepEd-Inspired)

**Horizontal Spreadsheet View:**
```
┌────────────┬──────────┬──────────┬──────────┬──────────┬──────────┬────────┬─────────┐
│ Student    │ Quiz 1   │ Quiz 2   │ Quiz 3   │ Exam 1   │ Project  │ Attend │ Final   │
│            │ (50)     │ (50)     │ (50)     │ (100)    │ (100)    │ (%)    │ Grade   │
├────────────┼──────────┼──────────┼──────────┼──────────┼──────────┼────────┼─────────┤
│ John Doe   │ 45/50    │ 48/50    │ 50/50    │ 85/100   │ 90/100   │ 18/20  │ 92.5    │
│            │          │          │          │          │          │ 90%    │ (1.25)  │
├────────────┼──────────┼──────────┼──────────┼──────────┼──────────┼────────┼─────────┤
│ Jane Smith │ 42/50    │ 45/50    │ 47/50    │ 88/100   │ 95/100   │ 20/20  │ 94.3    │
│            │          │          │          │          │          │ 100%   │ (1.25)  │
└────────────┴──────────┴──────────┴──────────┴──────────┴──────────┴────────┴─────────┘
```

### Features:
1. **Inline Editing** - Click any score cell to edit
2. **Keyboard Navigation** - Tab/Enter/Arrow keys
3. **Auto-Save** - Changes saved immediately
4. **Visual Indicators:**
   - Red: Failed scores (below 75%)
   - Yellow: Warning (75-79%)
   - Green: Passing (80%+)
5. **Collapsible Sections** - Expand/collapse component groups
6. **Horizontal Scroll** - Handle many grade items

---

## 📤 Excel Export Specifications

### Class Record Export Format

**Sheet Structure:**
```
Row 1: School Header (Name, Logo, School Year)
Row 2: Section Details (Subject, Section, Teacher)
Row 3: Grade Configuration Weights
Row 4: Column Headers
Row 5+: Student Data
Last Row: Class Averages
```

**Formatting:**
- Bold headers
- Borders on all cells
- Percentage format for attendance
- Decimal format for grades
- Auto-width columns
- Freeze header rows

**File Naming:**
```
{Subject_Code}_{Section}_{Semester}_{AY}.xlsx

Example: CS101_3A_1stSem_2024-2025.xlsx
```

---

## 🔐 Security Measures

### Authentication
- Laravel Breeze session-based authentication
- Password hashing (bcrypt)
- Email verification (optional)
- CSRF protection on all forms

### Authorization
- Spatie Permission middleware on all routes
- Role-based access control
- Owner-based access (teachers see only their sections)

### Data Protection
- SQL injection prevention (Eloquent ORM)
- XSS protection (Blade escaping)
- Mass assignment protection
- File upload validation

### Audit Trail
- All grade changes logged (who, when, old value, new value)
- Login/logout tracking
- Critical actions logged (approve users, lock grades)

---

## 🚀 Deployment Considerations

### Server Requirements
- PHP 8.1+ (currently using 8.4)
- MySQL 8.0+
- Composer
- Node.js 18+
- Apache/Nginx

### Environment Configuration
- Production `.env` with secure credentials
- Debug mode disabled
- Error logging enabled
- Queue workers for background jobs

### Performance Optimization
- Database indexing on foreign keys
- Eager loading for relationships
- Query caching for grade calculations
- Asset minification

---

## 🎓 User Workflows

### Teacher Daily Workflow
1. Login → View dashboard
2. Select section from "My Classes"
3. Navigate to "Class Record" tab
4. Input/update scores in spreadsheet view
5. Mark today's attendance
6. View auto-updated final grades
7. Export to Excel if needed

### Dean Weekly Workflow
1. Login → View dashboard
2. Review pending grade configurations → Approve/reject
3. Check department performance reports
4. Enroll new students to sections
5. Assign new teachers if needed

### Super Admin Setup Workflow
1. Approve new teacher registrations
2. Import student master list from Excel
3. Create subject catalog for new semester
4. Monitor system activity logs
5. Run semester-end reports

---

## 📈 Future Enhancements (Post-MVP)

### Potential Features
- [ ] Mobile app (React Native)
- [ ] Real-time notifications (Laravel Echo + Pusher)
- [ ] Advanced analytics dashboard
- [ ] Parent portal (view child's grades)
- [ ] SMS notifications for failing grades
- [ ] Multi-language support
- [ ] Dark mode
- [ ] API for third-party integrations
- [ ] Automated report scheduling
- [ ] Grade trending analysis

---

## 📞 Support & Maintenance

### Issue Reporting
- GitHub Issues for bug reports
- Feature requests via discussions
- Security vulnerabilities: private disclosure

### Maintenance Schedule
- Database backups: Daily (automated)
- Security updates: As needed
- Feature releases: Quarterly
- Bug fixes: As needed

---

**Last Updated:** February 8, 2026  
**Version:** 1.0.0-alpha  
**Status:** In Development (Phase 1 Complete)
