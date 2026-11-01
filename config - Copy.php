<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "autoqpgenp_db";

$conn = mysqli_connect($host, $user, $pass, $db);


if (!defined("GEMINI_API_KEY")) {
    define("GEMINI_API_KEY", getenv("GEMINI_API_KEY") ?: "");
}


if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>