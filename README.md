# 📚 Class Record Management System

A comprehensive Faculty Class Record Management System built with Laravel 10, designed for educational institutions following the Philippine DepEd grading system.

![Status](https://img.shields.io/badge/Status-In%20Development-yellow)
![Laravel](https://img.shields.io/badge/Laravel-10.x-red)
![PHP](https://img.shields.io/badge/PHP-8.4-blue)
![Progress](https://img.shields.io/badge/Progress-60%25-orange)
![License](https://img.shields.io/badge/License-MIT-green)

---

## 🎯 Project Overview

This system streamlines grade management and class record keeping with a DepEd-style spreadsheet interface, automated Philippine grade scale calculations, and role-based access control for Super Admins, Deans, and Teachers.

### Key Features
- ✅ **Three-tier Role System** (Super Admin → Dean → Teacher)
- ✅ **Centralized Student Management** (No duplicate records)
- ✅ **Flexible Grading System** (Unlimited items per component)
- ✅ **Automated Grade Calculation** (Semester-based cumulative, live display)
- ✅ **DepEd-Style Class Record** (Spreadsheet with frozen columns, color-coded components)
- ✅ **Philippine Grading Scale** (1.00–5.00 auto-conversion)
- ✅ **Complete Audit Trail** (Track all grade changes)
- 📅 **Excel Import/Export** (Phase 7 — next)

---

## 🚀 Current Status: Phase 6 Complete ✅

**Completed:**
- ✅ Phase 1: Foundation Setup
- ✅ Phase 2: Database Architecture (24 tables)
- ✅ Phase 3: Authentication & Authorization
- ✅ Phase 4: Academic Structure (Students, Subjects, Sections, Enrollments, Teacher Approval)
- ✅ Phase 5: Grading System (Config, Items, Scores, Attendance, Final Grades, Lock)
- ✅ Phase 6: DepEd-Style Class Record Interface (Spreadsheet, Frozen Columns, Averages)

**Next Up: Phase 7 — Excel Export**
- 📅 Export class record to `.xlsx` matching DepEd format
- 📅 Color-coded headers, borders, auto-width columns
- 📅 Filename: `{SubjectCode}_{Section}_{Semester}_{AY}.xlsx`

See [CHANGELOG.md](CHANGELOG.md) for detailed progress.

---

## 📋 Documentation

- **[CHANGELOG.md](CHANGELOG.md)** - Detailed development progress by phase
- **[SYSTEM-OVERVIEW.md](SYSTEM-OVERVIEW.md)** - Complete system architecture & specifications

---

## 🛠️ Tech Stack

### Backend
- **Laravel 10 LTS** — PHP Framework
- **MySQL 8.0** — Database
- **PHP 8.4.11** — Programming Language

### Frontend
- **Blade Templates** — Templating engine
- **Tailwind CSS** — Utility-first CSS
- **Alpine.js** — Planned for Phase 9 inline editing
- **Vite** — Build tool

### Key Packages
| Package | Purpose |
|---------|---------|
| Laravel Breeze | Authentication scaffolding |
| Spatie Permission | Role & permission management |
| Maatwebsite Excel | Excel import/export (Phase 7) |
| Spatie Activity Log | Audit trail logging |
| Laravel Debugbar | Development debugging |

---

## 📦 Installation

### Prerequisites
- PHP 8.1+
- Composer
- Node.js 18+ & NPM
- MySQL 8.0+
- XAMPP/WAMP/Laragon

### Setup Steps

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

### Test Accounts
| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@classrecord.test | password |
| Dean | dean@classrecord.test | password |
| Teacher (Active) | teacher@classrecord.test | password |
| Teacher (Pending) | pending@classrecord.test | password |

---

## 👥 User Roles

### Super Admin
- Full system access
- Approve teacher registrations
- Manage student master list and subject catalog

### Dean
- Create and manage sections
- Enroll students to sections
- Assign teachers to sections
- Approve grade configurations

### Teacher
- View assigned sections
- Configure grade components (weights must sum to 100%)
- Create grade items (Quiz 1, Midterm Exam, etc.)
- Enter scores per student per item
- Mark daily attendance
- View live auto-calculated final grades
- Save and lock grades officially
- View DepEd-style class record spreadsheet

---

## 🧮 Grading System

### Formula
```
Component Score = (Total Earned / Total Possible) × Component Weight
Final Grade     = Sum of all component scores
```

### Grade Components
- Quiz, Exam, Project, Assessment, Attendance
- Weights configurable per section (must total 100%)

### Philippine Grade Scale
| Percentage | Grade |
|-----------|-------|
| 97–100% | 1.00 (Excellent) |
| 94–96% | 1.25 |
| 91–93% | 1.50 |
| 88–90% | 1.75 |
| 85–87% | 2.00 |
| 82–84% | 2.25 |
| 79–81% | 2.50 |
| 76–78% | 2.75 |
| 75% | 3.00 (Passing) |
| Below 75% | 5.00 (Failed) |

### Live vs Saved Grades
- **Live display:** Final Grades and Class Record pages always show current calculated values — no button needed
- **Save Grades:** Writes to `final_grades` table for official record
- **Lock All:** Permanently freezes grades — cannot be overwritten

---

## 📊 Class Record Interface (Phase 6 ✅)

DepEd-style spreadsheet view with:
- Frozen student name columns (CSS sticky)
- Color-coded component groups
- Scores in `45/50` format
- Weighted score per component
- Attendance in `18/20` format
- Class averages footer
- Pass/Fail count

---

## 📈 Development Roadmap

| Phase | Status | Description |
|-------|--------|-------------|
| Phase 1 | ✅ Complete | Foundation setup |
| Phase 2 | ✅ Complete | Database architecture |
| Phase 3 | ✅ Complete | Authentication & authorization |
| Phase 4 | ✅ Complete | Academic structure |
| Phase 5 | ✅ Complete | Grading system |
| Phase 6 | ✅ Complete | Class record interface |
| **Phase 7** | 📅 **Next** | **Excel export** |
| Phase 8 | 📅 Planned | Reporting & analytics |
| Phase 9 | 📅 Planned | UI/UX polish + inline editing |
| Phase 10 | 📅 Planned | Testing & deployment |

**Overall Progress: 60%**

---

## 🔮 Future Enhancements (Post-MVP)

- Real-time grade updates (Laravel Echo + Redis + WebSockets)
- Mobile app (React Native)
- Parent portal
- SMS notifications for failing students
- Advanced analytics dashboard
- Multi-language support
- Dark mode
- Automated report scheduling

---

## 🤝 Contributing

```bash
git checkout -b feature/phase-X-description
git commit -m '[PHASE-X] Description'
git push origin feature/phase-X-description
```

---

**Last Updated:** February 17, 2026  
**Version:** 1.0.0-alpha  
**Current Phase:** Phase 7 — Excel Export  
**Maintained By:** Frances Igop
