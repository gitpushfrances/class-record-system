# DATABASE SETUP GUIDE

## 🗄️ Quick Database Creation

### Option 1: Auto-Create via SQL Script (Recommended)

Copy this entire SQL block and run it in phpMyAdmin SQL tab or MySQL command line:

```sql
-- ============================================
-- CLASS RECORD SYSTEM - DATABASE SETUP
-- ============================================
-- This script will:
-- 1. Create the database
-- 2. Set proper character encoding
-- 3. Create a dedicated database user (optional)
-- ============================================

-- Create database
CREATE DATABASE IF NOT EXISTS class_record_system 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Use the database
USE class_record_system;

-- Optional: Create dedicated user with permissions
-- Uncomment the lines below if you want a separate database user
-- Replace 'your_password' with a strong password

-- CREATE USER IF NOT EXISTS 'class_record_user'@'localhost' IDENTIFIED BY 'your_password';
-- GRANT ALL PRIVILEGES ON class_record_system.* TO 'class_record_user'@'localhost';
-- FLUSH PRIVILEGES;

-- Verify database creation
SELECT 'Database created successfully!' AS Status;
SHOW DATABASES LIKE 'class_record_system';
```

---

## 📝 Laravel .env Configuration

After creating the database, update your `.env` file:

### For Default MySQL Root User:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=class_record_system
DB_USERNAME=root
DB_PASSWORD=
```

### For Custom Database User (if you created one above):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=class_record_system
DB_USERNAME=class_record_user
DB_PASSWORD=your_password
```

---

## ✅ Verify Connection

After updating `.env`, test the database connection:

```bash
php artisan db:show
```

You should see:
```
MySQL 8.x ......................................................... [database]
class_record_system ............................................... [database]
```

If you see errors, check:
1. MySQL service is running (XAMPP)
2. Database name is correct
3. Username/password match
4. Port 3306 is correct

---

## 🎯 Next Steps After Database Creation

1. ✅ Create migration files
2. ✅ Run migrations: `php artisan migrate`
3. ✅ Create seeder files
4. ✅ Run seeders: `php artisan db:seed`

---

## 📊 Expected Tables After Migration (24 Total)

### Laravel Default Tables (10)
- `failed_jobs`
- `migrations`
- `password_reset_tokens`
- `personal_access_tokens`
- `sessions`
- `users`

### Spatie Permission Tables (5)
- `permissions`
- `roles`
- `model_has_permissions`
- `model_has_roles`
- `role_has_permissions`

### Spatie Activity Log Tables (2)
- `activity_log`

### Custom Application Tables (11)
- `students`
- `subjects`
- `sections`
- `class_assignments`
- `enrollments`
- `grade_configurations`
- `grade_items`
- `student_grades`
- `attendance_records`
- `final_grades`

**Total: 24 tables**

---

## 🔍 Troubleshooting

### Error: "Access denied for user 'root'@'localhost'"
**Solution:** Your MySQL root user has a password set.

Find your password in XAMPP:
1. Open XAMPP Control Panel
2. Click "Shell"
3. Type: `mysql -u root -p`
4. Enter the password
5. Update `.env` with that password

---

### Error: "SQLSTATE[HY000] [2002] Connection refused"
**Solution:** MySQL service is not running.

1. Open XAMPP Control Panel
2. Start "MySQL" module
3. Wait for it to turn green
4. Try again

---

### Error: "Unknown database 'class_record_system'"
**Solution:** Database was not created.

Run the SQL script above in phpMyAdmin.

---

## 📦 Quick Setup Commands (All-in-One)

After database is created, run these Laravel commands:

```bash
# Test database connection
php artisan db:show

# Create migration files (you'll create these in Phase 2)
php artisan make:migration create_students_table
php artisan make:migration create_subjects_table
# ... (you'll get all migration commands in next phase)

# Run migrations (after creating migration files)
php artisan migrate

# Create seeder files
php artisan make:seeder RolePermissionSeeder
php artisan make:seeder SuperAdminSeeder
php artisan make:seeder SampleDataSeeder

# Run seeders (after creating seeder files)
php artisan db:seed
```

---

## 🎨 phpMyAdmin Method (Visual)

If you prefer a GUI:

1. Open browser: `http://localhost/phpmyadmin`
2. Click **"New"** in left sidebar
3. Enter database name: `class_record_system`
4. Select Collation: `utf8mb4_unicode_ci`
5. Click **"Create"**

Then update `.env` as shown above.

---

## 🔐 Security Best Practices

### Production Deployment:
1. **Never use root user** - Create dedicated database user
2. **Use strong passwords** - Minimum 16 characters
3. **Restrict user privileges** - Only grant necessary permissions
4. **Enable SSL/TLS** - Encrypt database connections
5. **Regular backups** - Automated daily backups

### Example Production User Creation:
```sql
CREATE USER 'class_record_prod'@'localhost' 
IDENTIFIED BY 'Str0ng!P@ssw0rd2026#';

GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, INDEX, ALTER 
ON class_record_system.* 
TO 'class_record_prod'@'localhost';

FLUSH PRIVILEGES;
```

---

**Last Updated:** February 8, 2026  
**Status:** Ready for Phase 2 - Migration Creation
