-- ============================================================
-- SIWES Tracker — Database Schema
-- Import this in phpMyAdmin (XAMPP) or:
--   mysql -u root < database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS siwes_tracker
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE siwes_tracker;

-- ------------------------------------------------------------
-- Students (users)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS students (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  matric_no     VARCHAR(40)  NOT NULL UNIQUE,
  full_name     VARCHAR(120) NOT NULL,
  email         VARCHAR(160) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  institution   VARCHAR(160) NULL,
  course_of_study VARCHAR(120) NULL,
  placement_org VARCHAR(160) NULL,
  industry_supervisor VARCHAR(160) NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Daily entries — a student logs one working day
-- (date, hours, what was done)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS log_entries (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id    INT UNSIGNED NOT NULL,
  entry_date    DATE NOT NULL,                -- the day worked
  hours_worked  DECIMAL(4,1)  NOT NULL DEFAULT 8.0,
  work_done     TEXT NOT NULL,                -- what was done that day
  challenges    TEXT NULL,                    -- optional field
  remarks       TEXT NULL,                    -- supervisor's remark (optional)
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  UNIQUE KEY uq_student_day (student_id, entry_date)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- (Optional) Seed a demo account:
--   email:    demo@siwes.test
--   password: demo1234
-- (Add hashes via the register page instead — safer.)
-- ------------------------------------------------------------
