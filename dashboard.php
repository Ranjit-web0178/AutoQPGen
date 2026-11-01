<?php
include 'config.php';
session_start();

// Ensure user session
if (!isset($_SESSION['admin'])) {
    header("Location: admin.php");
    exit();
}

$user_role = $_SESSION['role'] ?? 'admin';
$user_name = $_SESSION['name'] ?? $_SESSION['admin'];
$staff_id = $_SESSION['staff_id'] ?? null;

// Counts for Reports
$staff_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM staff_master"))['c'] ?? 0;
$subject_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM subject"))['c'] ?? 0;
$stream_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM stream"))['c'] ?? 0;

$mcq_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM mcq_question"))['c'] ?? 0;

$q1 = mysqli_query($conn, "SHOW TABLES LIKE 'short_long_question'");
if(mysqli_num_rows($q1) > 0){
    $short_long_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM short_long_question"))['c'] ?? 0;
} else {
    $q2 = mysqli_query($conn, "SHOW TABLES LIKE 'short_and_long_q'");
    if(mysqli_num_rows($q2) > 0) $short_long_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM short_and_long_q"))['c'] ?? 0;
    else $short_long_count = 0;
}

$total_que = $mcq_count + $short_long_count;
$pattern_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM question_pattern"))['c'] ?? 0;
$saved_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM saved_papers"))['c'] ?? 0;

// Recent Staff
$recent_staff = mysqli_query($conn, "SELECT * FROM staff_master ORDER BY Staff_id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html>
<head>
<title>Dashboard - AutoQPGen</title>
<style>
body{margin:0; font-family:Arial, sans-serif; display:flex; background:#eef2f7;}
.sidebar{width:250px; background:#0a2a5a; min-height:100vh; padding:20px 15px; color:white; position:fixed;}
.sidebar h3{margin:0; font-size:18px;} .sidebar small{color:#a0b9d6; font-size:12px;}
.user-badge{background:#123a75; border-radius:8px; padding:10px; margin-top:15px; font-size:13px; border-left:4px solid #00bcd4;}
.menu{margin-top:20px;}
.menu a{display:block; background:#123a75; color:white; text-decoration:none; padding:11px 14px; margin-bottom:8px; border-radius:6px; font-size:13.5px;}
.menu a:hover{background:#1a4fa0;}
.menu a.active{background:#1565c0;}
.logout{background:#b71c1c !important; margin-top:15px;}
.main{margin-left:280px; flex:1; padding:20px 25px;}
.top-box{background:white; padding:20px; border-radius:10px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.1);}
.top-box h2{margin:0; color:#0a2a5a;}
.cards{display:grid; grid-template-columns:repeat(auto-fit, minmax(190px, 1fr)); gap:15px; margin-top:20px;}
.card{background:white; padding:20px; border-radius:12px; box-shadow:0 3px 10px rgba(0,0,0,0.08); border-left:5px solid #1565c0; text-align:center;}
.card h4{margin:0 0 5px; color:#555; font-size:13px;}
.card h1{margin:5px 0; color:#0a2a5a; font-size:30px;}
.card span{font-size:12px; color:green;}
.report-grid{display:grid; grid-template-columns:2fr 1fr; gap:20px; margin-top:20px;}
.report-box{background:white; padding:20px; border-radius:12px; box-shadow:0 3px 10px rgba(0,0,0,0.08);}
.report-box h3{margin:0 0 15px; color:#0a2a5a; border-bottom:2px solid #e3f2fd; padding-bottom:8px;}
table{width:100%; border-collapse:collapse;}
th{background:#0a2a5a; color:white; padding:8px; font-size:13px;}
td{padding:8px; border-bottom:1px solid #eee; text-align:center; font-size:13px;}
.badge{background:#e3f2fd; color:#0a2a5a; padding:4px 10px; border-radius:15px; font-size:12px; font-weight:bold;}
@media(max-width:900px){ .cards{grid-template-columns:repeat(2, 1fr);} .report-grid{grid-template-columns:1fr;} .main{margin-left:0; margin-top:60px;} .sidebar{position:relative; width:100%; min-height:auto;} body{flex-direction:column;} }
</style>
</head>
<body>

<div class="sidebar">
  <h3>Vivekanand College</h3>
  <small>Auto QP Generator</small>

  <div class="user-badge">
    <b><?= $user_role === 'teacher' ? '👨‍🏫 Faculty Portal' : '🏛️ Exam Cell (Admin)' ?></b><br>
    <small><?= htmlspecialchars($user_name) ?></small>
  </div>

  <div class="menu">
    <?php if ($user_role === 'admin'): ?>
      <a href="stream.php">Stream & Dept</a>
      <a href="subject.php">Subjects</a>
      <a href="staff_master.php">Staff Master</a>
      <a href="staff_transaction.php">Staff Allotment</a>
      <a href="question_pattern.php">Question Pattern</a>
    <?php endif; ?>
    <a href="mcq_question.php">MCQ Question Bank</a>
    <a href="short_long_question.php">Short & Long Bank</a>
    <a href="generate_paper.php" style="background:#1565c0; font-weight:bold;">✨ Generate Paper (AI)</a>
    <a href="saved_papers.php">📁 Saved Paper Archive</a>
    <a href="logout.php" class="logout">Logout</a>
  </div>
</div>

<div class="main">
  <div class="top-box">
    <h2>Dashboard</h2>
    <p>Welcome, <b><?= htmlspecialchars($user_name) ?></b> to Automatic Question Paper Generator</p>
  </div>

  <!-- Top Reports Cards -->
  <div class="cards">
    <div class="card"><h4>Staff Registered</h4><h1><?php echo $staff_count; ?></h1><span>✔ Faculty</span></div>
    <div class="card" style="border-left-color:#2e7d32;"><h4>Subjects</h4><h1><?php echo $subject_count; ?></h1><span>✔ Active Courses</span></div>
    <div class="card" style="border-left-color:#ef6c00;"><h4>Question Bank</h4><h1><?php echo $total_que; ?></h1><span><?php echo $mcq_count; ?> MCQ + <?php echo $short_long_count; ?> Q&A</span></div>
    <div class="card" style="border-left-color:#6a1b9a;"><h4>Question Patterns</h4><h1><?php echo $pattern_count; ?></h1><span>✔ Blueprints</span></div>
    <div class="card" style="border-left-color:#00838f;"><h4>Generated Papers</h4><h1><?php echo $saved_count; ?></h1><span>✔ Archived Sets</span></div>
  </div>

  <div class="report-grid">
    <!-- Staff Registration Report -->
    <div class="report-box">
      <h3>📋 Staff Allotment & Quick Actions</h3>
      <table>
        <tr><th>Staff Id</th><th>Name</th><th>Role</th></tr>
        <?php
        if($recent_staff && mysqli_num_rows($recent_staff) > 0){
            while($r=mysqli_fetch_assoc($recent_staff)){
                $name = $r['Name'] ?? $r['Staff_name'] ?? '-';
                echo "<tr><td>{$r['Staff_id']}</td><td>$name</td><td><span class='badge'>Faculty</span></td></tr>";
            }
        } else {
            echo "<tr><td colspan='3'>No Staff Registered Yet</td></tr>";
        }
        ?>
      </table>
      <br>
      <div style="display:flex; gap:10px;">
        <a href="generate_paper.php" style="flex:1; background:#1565c0; color:white; text-align:center; padding:10px; border-radius:6px; text-decoration:none; font-weight:bold;">✨ Generate Multiple Sets</a>
        <a href="saved_papers.php" style="flex:1; background:#00838f; color:white; text-align:center; padding:10px; border-radius:6px; text-decoration:none; font-weight:bold;">📁 View Sealed Archive</a>
      </div>
    </div>

    <!-- Questions Generated Report -->
    <div class="report-box">
      <h3>📊 Question Bank Status</h3>
      <p><b>MCQ Questions:</b> <?php echo $mcq_count; ?></p>
      <p><b>Short & Long Questions:</b> <?php echo $short_long_count; ?></p>
      <hr>
      <p style="font-size:15px;"><b>Total Questions:</b> <span style="color:#0a2a5a; font-size:20px; font-weight:bold;"><?php echo $total_que; ?></span></p>
      <p><b>Streams:</b> <?php echo $stream_count; ?> | <b>Subjects:</b> <?php echo $subject_count; ?></p>
      <hr>
      <a href="saved_papers.php" style="color:#00838f; font-weight:bold; text-decoration:none;">📁 View Sealed Archive (<?php echo $saved_count; ?> Papers) →</a>
    </div>
  </div>

</div>

</body>
</html>
