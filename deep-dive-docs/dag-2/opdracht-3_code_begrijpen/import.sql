-- Database schema for the Library REST API
-- Target: MariaDB / MySQL

-- Create database
CREATE DATABASE IF NOT EXISTS `library_api`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `library_api`;

-- Libraries (matches LIBRARY from ERD)
CREATE TABLE IF NOT EXISTS `libraries` (
  `library_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `address` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) NULL,
  `email` VARCHAR(255) NULL,
  PRIMARY KEY (`library_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Members / Users (matches MEMBER from ERD and User from class diagram)
CREATE TABLE IF NOT EXISTS `users` (
  `member_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) NULL,
  `membership_date` DATE NOT NULL,
  `membership_status` ENUM('ACTIVE', 'SUSPENDED', 'BANNED') NOT NULL DEFAULT 'ACTIVE',
  `library_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`member_id`),
  UNIQUE KEY `uniq_users_email` (`email`),
  KEY `idx_users_library_id` (`library_id`),
  CONSTRAINT `fk_users_library`
    FOREIGN KEY (`library_id`) REFERENCES `libraries` (`library_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Authors (matches AUTHOR from ERD)
CREATE TABLE IF NOT EXISTS `authors` (
  `author_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `biography` TEXT NULL,
  PRIMARY KEY (`author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories (matches CATEGORY from ERD)
CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Books (matches BOOK from ERD and Book from class diagram)
CREATE TABLE IF NOT EXISTS `books` (
  `book_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `isbn` VARCHAR(32) NOT NULL,
  `author_id` INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `library_id` INT UNSIGNED NOT NULL,
  `description` TEXT NULL,
  `copies_available` INT UNSIGNED NOT NULL DEFAULT 0,
  `total_copies` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`book_id`),
  UNIQUE KEY `uniq_books_isbn` (`isbn`),
  KEY `idx_books_author_id` (`author_id`),
  KEY `idx_books_category_id` (`category_id`),
  KEY `idx_books_library_id` (`library_id`),
  CONSTRAINT `fk_books_author`
    FOREIGN KEY (`author_id`) REFERENCES `authors` (`author_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_books_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_books_library`
    FOREIGN KEY (`library_id`) REFERENCES `libraries` (`library_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Loans (matches LOAN from ERD and Loan from class diagram)
CREATE TABLE IF NOT EXISTS `loans` (
  `loan_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id` INT UNSIGNED NOT NULL,
  `book_id` INT UNSIGNED NOT NULL,
  `loan_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `return_date` DATE NULL,
  `status` ENUM('BORROWED', 'RETURNED', 'OVERDUE') NOT NULL DEFAULT 'BORROWED',
  PRIMARY KEY (`loan_id`),
  KEY `idx_loans_member_id` (`member_id`),
  KEY `idx_loans_book_id` (`book_id`),
  CONSTRAINT `fk_loans_member`
    FOREIGN KEY (`member_id`) REFERENCES `users` (`member_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_loans_book`
    FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reservations (matches RESERVATION from ERD and Reservation from class diagram)
CREATE TABLE IF NOT EXISTS `reservations` (
  `reservation_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id` INT UNSIGNED NOT NULL,
  `book_id` INT UNSIGNED NOT NULL,
  `reservation_date` DATE NOT NULL,
  `status` ENUM('ACTIVE', 'CANCELLED', 'EXPIRED') NOT NULL DEFAULT 'ACTIVE',
  PRIMARY KEY (`reservation_id`),
  KEY `idx_reservations_member_id` (`member_id`),
  KEY `idx_reservations_book_id` (`book_id`),
  CONSTRAINT `fk_reservations_member`
    FOREIGN KEY (`member_id`) REFERENCES `users` (`member_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_reservations_book`
    FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

