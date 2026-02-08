# 📚 Class Record Management System

A comprehensive Faculty Class Record Management System built with Laravel 10, designed for educational institutions following the Philippine DepEd grading system.

![Status](https://img.shields.io/badge/Status-In%20Development-yellow)
![Laravel](https://img.shields.io/badge/Laravel-10.x-red)
![PHP](https://img.shields.io/badge/PHP-8.4-blue)
![License](https://img.shields.io/badge/License-MIT-green)

---

## 🎯 Project Overview

This system streamlines grade management and class record keeping with an Excel-like interface, automated grade calculations, and role-based access control for Super Admins, Deans, and Teachers.

### Key Features
- ✅ **Three-tier Role System** (Super Admin → Dean → Teacher)
- ✅ **Centralized Student Management** (No duplicate records)
- ✅ **Flexible Grading System** (Unlimited items per component)
- ✅ **Automated Grade Calculation** (Semester-based cumulative)
- ✅ **DepEd-Style Class Record** (Spreadsheet interface)
- ✅ **Excel Import/Export** (Batch operations & reports)
- ✅ **Complete Audit Trail** (Track all grade changes)

---

## 🚀 Current Status: Phase 3 Complete ✅

**Completed:**
- ✅ Phase 1: Foundation Setup (Laravel 10 + packages)
- ✅ Phase 2: Database Architecture (24 tables + models + seeders)
- ✅ Phase 3: Authentication & Authorization (role-based access control)

**Next Up: Phase 4 - Academic Structure Management**
- 📅 Student management (import from Excel)
- 📅 Subject & section management
- 📅 Teacher approval system
- 📅 Class assignment workflow

See [CHANGELOG.md](CHANGELOG.md) for detailed progress.

---

## 📋 Documentation

- **[CHANGELOG.md](CHANGELOG.md)** - Detailed development progress by phase
- **[SYSTEM-OVERVIEW.md](SYSTEM-OVERVIEW.md)** - Complete system architecture & specifications
- **[DATABASE-SETUP.md](DATABASE-SETUP.md)** - Database creation & configuration guide

---

## 🛠️ Tech Stack

### Backend
- **Laravel 10 LTS** - PHP Framework
- **MySQL 8.0** - Database
- **PHP 8.4.11** - Programming Language

### Frontend
- **Blade Templates** - Laravel's templating engine
- **Tailwind CSS** - Utility-first CSS framework
- **Alpine.js** - Minimal JavaScript framework
- **Vite** - Modern build tool

### Key Packages
| Package | Purpose |
|---------|---------|
| Laravel Breeze | Authentication scaffolding |
| Spatie Permission | Role & permission management |
| Maatwebsite Excel | Excel import/export |
| Spatie Activity Log | Audit trail logging |
| Laravel Debugbar | Development debugging |

---

## 📦 Installation

### Prerequisites
- PHP 8.1 or higher
- Composer
- Node.js 18+ & NPM
- MySQL 8.0+
- XAMPP/WAMP/Laragon (or equivalent)

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/class-record-system.git
   cd class-record-system
   ```

2. **Install PHP dependencies**
   ```bash
   composer install --ignore-platform-req=php
   ```

3. **Install NPM dependencies**
   ```bash
   npm install
   ```

4. **Environment configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database setup**
   - Create database: `class_record_system`
   - Update `.env` with your database credentials:
     ```env
     DB_DATABASE=class_record_system
     DB_USERNAME=root
     DB_PASSWORD=
     ```

6. **Run migrations**
   ```bash
   php artisan migrate
   ```

7. **Seed database**
   ```bash
   php artisan db:seed
   ```
   
   **Test Accounts Created:**
   - Super Admin: `admin@classrecord.test` / `password`
   - Dean: `dean@classrecord.test` / `password`
   - Teacher (Active): `teacher@classrecord.test` / `password`
   - Teacher (Pending): `pending@classrecord.test` / `password`

8. **Build assets**
   ```bash
   npm run build
   ```

9. **Start development server**
   ```bash
   php artisan serve
   ```

Visit: `http://localhost:8000`

---

## 👥 User Roles

### Super Admin
- Full system access
- Approve teacher registrations
- Import/manage student master list
- Create subject catalog
- View all system activities

### Dean
- Enroll students to sections
- Create and manage sections
- Assign teachers to sections
- Approve grade configurations
- View department reports

### Teacher
- View assigned sections
- Configure grade components
- Input scores and attendance
- View auto-calculated grades
- Export class records to Excel

---

## 🧮 Grading System

### Calculation Method: Semester-Based Cumulative

Each component is calculated as:
```
Component Score = (Total Earned / Total Possible) × Component Weight
```

**Example:**
- Quizzes: 3 quizzes (45/50, 48/50, 50/50) = 143/150 = 95.33%
- Quiz Weight: 20%
- Quiz Score: 95.33% × 20% = 19.07/20

**Philippine Grade Scale (1.0-5.0):**
- 1.00 = 97-100% (Excellent)
- 1.25 = 94-96%
- 1.50 = 91-93%
- 1.75 = 88-90%
- 2.00 = 85-87% (Very Good)
- 3.00 = 75% (Passing)
- 5.00 = Below 75% (Failed)

---

## 📊 Development Roadmap

| Phase | Status | Description |
|-------|--------|-------------|
| **Phase 1** | ✅ Complete | Foundation setup |
| **Phase 2** | ✅ Complete | Database architecture |
| **Phase 3** | ✅ Complete | Authentication & authorization |
| **Phase 4** | 📅 Next | Academic structure management |
| **Phase 5** | 📅 Planned | Grading system implementation |
| **Phase 6** | 📅 Planned | Class record interface (Excel-like) |
| **Phase 7** | 📅 Planned | Excel export functionality |
| **Phase 8** | 📅 Planned | Reporting & analytics |
| **Phase 9** | 📅 Planned | UI/UX polish |
| **Phase 10** | 📅 Planned | Testing & deployment |

**Overall Progress:** 30%

---

## 🎉 Recent Achievements

### Phase 3 Completed! ✅
- Role-based authentication system working
- Middleware protection on all routes
- Super Admin, Dean, and Teacher dashboards configured
- Teacher self-registration with approval workflow
- Login redirects based on user role
- Pending user login blocking implemented

**What's Working:**
- ✅ Login as Super Admin → redirects to `/admin/dashboard`
- ✅ Login as Dean → redirects to `/dean/dashboard`
- ✅ Login as Teacher → redirects to `/teacher/dashboard`
- ✅ Pending teachers cannot login (blocked with error message)
- ✅ Unauthorized users get 403 Forbidden error
- ✅ All routes protected with auth + status + role middleware

---

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/phase-X-description`)
3. Commit your changes (`git commit -m '[PHASE-X] Description'`)
4. Push to the branch (`git push origin feature/phase-X-description`)
5. Open a Pull Request

### Commit Message Format
```
[PHASE-X] Brief description

- Detail 1
- Detail 2
```

---

## 📈 Project Stats

- **Started:** February 8, 2026
- **Current Version:** 1.0.0-alpha
- **Current Phase:** Phase 4 (Academic Structure)
- **Contributors:** 1
- **Commits:** In Progress
- **Stars:** Coming Soon

---

**Last Updated:** February 8, 2026  
**Maintained By:** [Frances Igop]
