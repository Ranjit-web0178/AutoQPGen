-- ============================================================
-- AutoQPGen - Automatic Question Paper Generator Database Schema
-- Customized for Vivekanand College (Autonomous Exam Center)
-- Database: autoqpgenp_db
-- ============================================================

CREATE DATABASE IF NOT EXISTS `autoqpgenp_db`;
USE `autoqpgenp_db`;

-- 1. Admin Table
CREATE TABLE IF NOT EXISTS `admin` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `User_name` VARCHAR(50) NOT NULL UNIQUE,
  `Password` VARCHAR(255) NOT NULL
);

-- Default Admin Account: admin / admin123
INSERT INTO `admin` (`User_name`, `Password`) VALUES ('admin', 'admin123')
ON DUPLICATE KEY UPDATE `User_name`=`User_name`;

-- 2. Stream & Department Table
CREATE TABLE IF NOT EXISTS `stream` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `Str_id` VARCHAR(10) NOT NULL,
  `Str_name` VARCHAR(100) NOT NULL,
  `Dept_id` VARCHAR(10) NOT NULL,
  `Dept_name` VARCHAR(100) NOT NULL
);

INSERT INTO `stream` (`Str_id`, `Str_name`, `Dept_id`, `Dept_name`) VALUES 
('01', 'Computer Science', '0101', 'BCA'),
('01', 'Computer Science', '0102', 'B.Sc Computer Science'),
('02', 'Commerce', '0201', 'B.Com'),
('03', 'Science', '0301', 'B.Sc Physics')
ON DUPLICATE KEY UPDATE `Str_id`=`Str_id`;

-- 3. Subject Table
CREATE TABLE IF NOT EXISTS `subject` (
  `Sub_id` INT AUTO_INCREMENT PRIMARY KEY,
  `Str_id` VARCHAR(10) NOT NULL,
  `Dept_id` VARCHAR(10) NOT NULL,
  `Sub_code` VARCHAR(20) NOT NULL UNIQUE,
  `Sub_name` VARCHAR(100) NOT NULL,
  `Semester` VARCHAR(10) NOT NULL
);

INSERT INTO `subject` (`Str_id`, `Dept_id`, `Sub_code`, `Sub_name`, `Semester`) VALUES
('01', '0101', 'DSC06COM51', 'Database Management Systems', 'VI'),
('01', '0101', 'DSC06COM52', 'Web Technology & PHP', 'VI'),
('01', '0101', 'DSC06COM53', 'Software Engineering', 'VI'),
('01', '0101', 'DSC05COM41', 'Java Programming', 'V')
ON DUPLICATE KEY UPDATE `Sub_code`=`Sub_code`;

-- 4. Staff Master Table (Faculty)
CREATE TABLE IF NOT EXISTS `staff_master` (
  `Staff_id` VARCHAR(10) PRIMARY KEY,
  `Dept_id` VARCHAR(10) NOT NULL,
  `Str_id` VARCHAR(10) NOT NULL,
  `Name` VARCHAR(100) NOT NULL,
  `Contact` VARCHAR(20) NOT NULL,
  `Password` VARCHAR(255) DEFAULT 'staff123'
);

INSERT INTO `staff_master` (`Staff_id`, `Dept_id`, `Str_id`, `Name`, `Contact`, `Password`) VALUES
('01', '0101', '01', 'Prof. S. R. Patil', '9876543210', 'staff123'),
('02', '0101', '01', 'Prof. A. M. Kulkarni', '9876543211', 'staff123')
ON DUPLICATE KEY UPDATE `Staff_id`=`Staff_id`;

-- 5. Staff Transaction Table (Course Allotment)
CREATE TABLE IF NOT EXISTS `staff_transaction` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `Staff_id` VARCHAR(10) NOT NULL,
  `Sub_code` VARCHAR(20) NOT NULL
);

INSERT INTO `staff_transaction` (`Staff_id`, `Sub_code`) VALUES
('01', 'DSC06COM51'),
('02', 'DSC06COM52')
ON DUPLICATE KEY UPDATE `Staff_id`=`Staff_id`;

-- 6. Question Pattern Table
CREATE TABLE IF NOT EXISTS `question_pattern` (
  `Qp_id` INT PRIMARY KEY,
  `Total_marks` INT NOT NULL,
  `1_mark` INT DEFAULT 0,
  `2_mark` INT DEFAULT 0,
  `4_mark` INT DEFAULT 0,
  `5_mark` INT DEFAULT 0,
  `6_mark` INT DEFAULT 0,
  `7_mark` INT DEFAULT 0,
  `8_mark` INT DEFAULT 0,
  `10_mark` INT DEFAULT 0,
  `14_mark` INT DEFAULT 0,
  `16_mark` INT DEFAULT 0,
  `20_mark` INT DEFAULT 0
);

INSERT INTO `question_pattern` (`Qp_id`, `Total_marks`, `1_mark`, `2_mark`, `4_mark`, `5_mark`, `6_mark`, `7_mark`, `8_mark`, `10_mark`, `14_mark`, `16_mark`, `20_mark`) VALUES
(1, 80, 10, 5, 0, 4, 0, 0, 2, 2, 0, 0, 0),
(2, 40, 5, 5, 0, 3, 0, 0, 0, 1, 0, 0, 0)
ON DUPLICATE KEY UPDATE `Qp_id`=`Qp_id`;

-- 7. MCQ Questions Table (With Unit & Bloom's Taxonomy)
CREATE TABLE IF NOT EXISTS `mcq_question` (
  `Question_id` INT AUTO_INCREMENT PRIMARY KEY,
  `Sub_code` VARCHAR(20) NOT NULL,
  `Unit_no` VARCHAR(20) DEFAULT 'Unit 1',
  `Blooms_level` VARCHAR(50) DEFAULT 'L1 - Remember',
  `Question` TEXT NOT NULL,
  `Option1` TEXT NOT NULL,
  `Option2` TEXT NOT NULL,
  `Option3` TEXT NOT NULL,
  `Option4` TEXT NOT NULL,
  `Option5` VARCHAR(10) NOT NULL
);

INSERT INTO `mcq_question` (`Sub_code`, `Unit_no`, `Blooms_level`, `Question`, `Option1`, `Option2`, `Option3`, `Option4`, `Option5`) VALUES
('DSC06COM51', 'Unit 1', 'L1 - Remember', 'What does DBMS stand for?', 'Database Management System', 'Data Backup Management System', 'Database Memory System', 'Data Block Master Server', '1'),
('DSC06COM51', 'Unit 1', 'L2 - Understand', 'Which of the following is not a type of database?', 'Hierarchical', 'Relational', 'Network', 'Decentralized Vector Protocol', '4'),
('DSC06COM51', 'Unit 3', 'L3 - Apply', 'Which SQL command is used to retrieve data from a database?', 'GET', 'SELECT', 'EXTRACT', 'OPEN', '2'),
('DSC06COM51', 'Unit 2', 'L1 - Remember', 'Which of the following is a primary key property?', 'Must be unique and not null', 'Can be null', 'Can have duplicates', 'Must be numeric only', '1'),
('DSC06COM51', 'Unit 2', 'L2 - Understand', 'In an ER diagram, an entity is represented by which shape?', 'Diamond', 'Ellipse', 'Rectangle', 'Circle', '3')
ON DUPLICATE KEY UPDATE `Question_id`=`Question_id`;

-- 8. Short / Long Questions Table (With Unit & Bloom's Taxonomy)
CREATE TABLE IF NOT EXISTS `short_long_question` (
  `Que_id` VARCHAR(10) PRIMARY KEY,
  `Sub_code` VARCHAR(20) NOT NULL,
  `Unit_no` VARCHAR(20) DEFAULT 'Unit 1',
  `Blooms_level` VARCHAR(50) DEFAULT 'L2 - Understand',
  `Que_marks` INT NOT NULL,
  `Question` TEXT NOT NULL,
  `Status` VARCHAR(20) DEFAULT 'Active'
);

INSERT INTO `short_long_question` (`Que_id`, `Sub_code`, `Unit_no`, `Blooms_level`, `Que_marks`, `Question`, `Status`) VALUES
('Q01', 'DSC06COM51', 'Unit 1', 'L1 - Remember', 2, 'Define Database and DBMS.', 'Active'),
('Q02', 'DSC06COM51', 'Unit 1', 'L2 - Understand', 2, 'What is data independence in DBMS?', 'Active'),
('Q03', 'DSC06COM51', 'Unit 1', 'L1 - Remember', 2, 'List four advantages of using a DBMS.', 'Active'),
('Q04', 'DSC06COM51', 'Unit 2', 'L2 - Understand', 5, 'Explain the three-schema architecture of DBMS with a neat diagram.', 'Active'),
('Q05', 'DSC06COM51', 'Unit 1', 'L2 - Understand', 5, 'Differentiate between File Processing System and DBMS.', 'Active'),
('Q06', 'DSC06COM51', 'Unit 3', 'L3 - Apply', 8, 'Explain various types of database integrity constraints with examples.', 'Active'),
('Q07', 'DSC06COM51', 'Unit 4', 'L4 - Analyze', 10, 'Explain Normalization and explain 1NF, 2NF, 3NF and BCNF with suitable examples.', 'Active'),
('Q08', 'DSC06COM51', 'Unit 5', 'L3 - Apply', 10, 'What is a Transaction? Explain ACID properties in detail with real-world scenarios.', 'Active')
ON DUPLICATE KEY UPDATE `Que_id`=`Que_id`;

-- 9. Generated Papers Archive Table (Sets, Bloom's, Watermark & Marking Scheme)
CREATE TABLE IF NOT EXISTS `saved_papers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `sub_code` VARCHAR(50) NOT NULL,
  `sub_name` VARCHAR(150) NOT NULL,
  `semester` VARCHAR(20) NOT NULL,
  `exam_name` VARCHAR(150) NOT NULL,
  `academic_year` VARCHAR(50) NOT NULL,
  `total_marks` INT NOT NULL,
  `time_allowed` VARCHAR(50) NOT NULL,
  `paper_content` LONGTEXT NOT NULL,
  `marking_scheme` LONGTEXT DEFAULT NULL,
  `generation_mode` VARCHAR(50) DEFAULT 'Gemini AI',
  `set_name` VARCHAR(20) DEFAULT 'Set A',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
