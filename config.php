<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "autoqpgenp_db";

// Connect to MySQL database
$conn = @mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    // Attempt automatic database creation if server is up
    $temp_conn = @mysqli_connect($host, $user, $pass);
    if ($temp_conn) {
        mysqli_query($temp_conn, "CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        mysqli_close($temp_conn);
        $conn = @mysqli_connect($host, $user, $pass, $db);
    }
}

if (!defined("GEMINI_API_KEY")) {
    define("GEMINI_API_KEY", getenv("GEMINI_API_KEY") ?: "");
}

if (!$conn) {
    die("Database Connection failed: " . mysqli_connect_error() . "<br>Please verify your MySQL / WAMP server is running.");
}

// Ensure saved_papers table exists for paper archive and multiple sets
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `saved_papers` (
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
)");

// Add Unit and Blooms columns to questions if not exist (non-destructive)
$check_col1 = @mysqli_query($conn, "SHOW COLUMNS FROM `mcq_question` LIKE 'Unit_no'");
if ($check_col1 && mysqli_num_rows($check_col1) == 0) {
    @mysqli_query($conn, "ALTER TABLE `mcq_question` ADD COLUMN `Unit_no` VARCHAR(20) DEFAULT 'Unit 1', ADD COLUMN `Blooms_level` VARCHAR(50) DEFAULT 'L1 - Remember'");
}

$check_col2 = @mysqli_query($conn, "SHOW COLUMNS FROM `short_long_question` LIKE 'Unit_no'");
if ($check_col2 && mysqli_num_rows($check_col2) == 0) {
    @mysqli_query($conn, "ALTER TABLE `short_long_question` ADD COLUMN `Unit_no` VARCHAR(20) DEFAULT 'Unit 1', ADD COLUMN `Blooms_level` VARCHAR(50) DEFAULT 'L2 - Understand'");
}

// Add Password column to staff_master if not exist (for Teacher login)
$check_col3 = @mysqli_query($conn, "SHOW COLUMNS FROM `staff_master` LIKE 'Password'");
if ($check_col3 && mysqli_num_rows($check_col3) == 0) {
    @mysqli_query($conn, "ALTER TABLE `staff_master` ADD COLUMN `Password` VARCHAR(255) DEFAULT 'staff123'");
}
?>
