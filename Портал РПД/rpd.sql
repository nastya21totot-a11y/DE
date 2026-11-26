CREATE DATABASE IF NOT EXISTS rpd_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rpd_portal;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(64) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    email VARCHAR(254) NOT NULL,
    role ENUM('teacher', 'admin') NOT NULL DEFAULT 'teacher'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS disciplines (
    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS teacher_disciplines (
    teacher_id INT UNSIGNED NOT NULL,
    discipline_id SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (teacher_id, discipline_id),
    FOREIGN KEY (teacher_id) REFERENCES users(id),
    FOREIGN KEY (discipline_id) REFERENCES disciplines(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT UNSIGNED NOT NULL,
    discipline_id SMALLINT UNSIGNED NOT NULL,
    version_number VARCHAR(24) NOT NULL,
    stored_file VARCHAR(64) NOT NULL UNIQUE,
    original_file VARCHAR(255) NOT NULL,
    version_comment TEXT NOT NULL,
    teacher_comment TEXT NULL,
    chair_comment TEXT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY applications_by_date (submitted_at, id),
    KEY applications_by_status (status, submitted_at),
    KEY applications_by_discipline (discipline_id, submitted_at),
    FOREIGN KEY (teacher_id, discipline_id) REFERENCES teacher_disciplines(teacher_id, discipline_id)
) ENGINE=InnoDB;

INSERT IGNORE INTO disciplines (id, name) VALUES
    (1, 'Информатика'), (2, 'Дискретная математика'), (3, 'Программирование'),
    (4, 'Базы данных'), (5, 'Веб-дизайн');

INSERT IGNORE INTO users (login, password_hash, full_name, phone, email, role) VALUES
    ('Admin', '$2y$10$X82Me1A9sl5q21VFLeo0Tu.YYHNTbHCDeXcz22ZKmo.6q94VSEhHS',
     'Председатель МЦК', '8(999)123-45-67', 'admin@example.test', 'admin');
