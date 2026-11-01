<?php
include 'config.php';
session_start();

$msg = "";

$ad = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(CAST(SUBSTRING(Que_id, 2) AS UNSIGNED)) as nid FROM short_long_question"));
$next_num = $ad['nid'] ? $ad['nid'] + 1 : 1;
$auto_que_id = "Q" . sprintf("%02d", $next_num);

if(isset($_POST['save'])){
  $que_id = trim($_POST['Que_id'] ?? ''); 
  $sub_code = strtoupper(trim($_POST['Sub_code'] ?? ''));
  $unit_no = trim($_POST['Unit_no'] ?? 'Unit 1');
  $blooms_level = trim($_POST['Blooms_level'] ?? 'L2 - Understand');
  $que_marks = intval($_POST['Que_marks'] ?? 0);
  $question = trim($_POST['Question'] ?? '');
  $status = trim($_POST['Status'] ?? 'Active');
  
  if(empty($que_id) || empty($sub_code) || empty($question) || $que_marks <= 0){
    $msg = "Error: All fields are required and Question Marks must be selected.";
  } elseif(!in_array($status, ['Active', 'Inactive'])){
    $msg = "Error: Status must be either Active or Inactive.";
  } else {
    $question_escaped = mysqli_real_escape_string($conn, $question);
    $check = mysqli_query($conn, "SELECT * FROM short_long_question WHERE Que_id='$que_id'");
    if(mysqli_num_rows($check) == 0){
      $q = mysqli_query($conn, "INSERT INTO short_long_question (Que_id, Sub_code, Unit_no, Blooms_level, Que_marks, Question, Status) VALUES ('$que_id','$sub_code','$unit_no','$blooms_level','$que_marks','$question_escaped','$status')");
      if($q){ 
          $msg = "Question Saved successfully with Bloom's level & Unit!"; 
          $next_num = intval(substr($que_id,1)) + 1;
          $auto_que_id = "Q" . sprintf("%02d", $next_num);
      } else {
          $msg = "Database Error: " . mysqli_error($conn);
      }
    } else { $msg = "Error: Question ID $que_id already exists."; }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Short & Long Questions - Vivekanand College</title>
<style>
body { font-family: Arial; background: #e1f5fe; margin:0; padding:20px; font-size:14px; }
.container { background:white; max-width:1150px; margin:auto; padding:30px 40px; border-radius:20px; box-shadow:0 4px 15px rgba(0,0,0,0.1); }
.header { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.logo-left { display:flex; align-items:center; gap:15px; }
.logo-left img { width:90px; height:90px; object-fit:contain; }
.logo-text { font-weight:bold; color:#0a3d6b; font-size:23px; }
.logo-text span { font-size:14px; color:#555; font-weight:normal; }
h2 { color:#0a2a5a; text-align:center; font-size:32px; margin:10px 0 5px; }
.nav { text-align:center; margin-bottom:20px; }
.nav a { color:#0288d1; text-decoration:none; font-weight:bold; margin:0 10px; }
.main-split { display:flex; gap:30px; align-items:flex-start; }
.left-img { width:40%; position:sticky; top:20px; }
.left-img img { width:100%; height:470px; object-fit:cover; border-radius:15px; }
.right-form { width:60%; background:#f7fbff; padding:20px; border-radius:15px; border:1px solid #b3e5fc; }
label { display:block; margin-top:12px; font-weight:bold; }
input[type=text], input[type=number], select, textarea { width:100%; padding:10px; margin-top:4px; border:2px solid #b3e5fc; border-radius:8px; box-sizing:border-box; font-family:Arial; }
input[type=submit] { width:220px; padding:12px; margin:20px auto 0; display:block; background:#1565c0; color:white; border:none; border-radius:25px; font-size:16px; font-weight:bold; cursor:pointer; }
.msg { text-align:center; color:green; font-weight:bold; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th { background:#0a3d6b; color:white; padding:10px; font-size:13px; }
td { padding:8px; border:1px solid #b3e5fc; text-align:left; font-size:13px; }
.badge-tag { background: #e3f2fd; color: #0a3d6b; padding: 2px 6px; border-radius: 8px; font-size: 11px; font-weight: bold; }
@media(max-width:700px){ .main-split{ flex-direction:column; } .left-img,.right-form{ width:100%; } }
</style>
</head>
<body>
<div class="container">
<div class="header">
  <div class="logo-left"><img src="logo.jpg"><div class="logo-text">Vivekanand College<br><span>Descriptive Question Bank</span></div></div>
</div>
<h2>Short & Long Questions</h2>
<div class="nav">
  <a href="dashboard.php">Dashboard</a> |
  <a href="mcq_question.php">MCQ Bank</a> |
  <a href="generate_paper.php">Generate Paper (AI)</a> |
  <a href="saved_papers.php">Saved Papers Archive</a> |
  <a href="logout.php">Logout</a>
</div>

<div class="main-split">
  <div class="left-img"><img src="slq.png"></div>
  <div class="right-form">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
      <h3 style="margin:0; color:#0a2a5a;">Add Descriptive Question</h3>
      <span style="font-size:12px; color:#555; background:#e3f2fd; padding:4px 10px; border-radius:12px; font-weight:bold;">Unit & Bloom's Mapped</span>
    </div>

    <div class="msg"><?php echo $msg; ?></div>
    <form method="post">
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
        <div>
          <label>Question Id:</label>
          <input type="text" name="Que_id" value="<?php echo $auto_que_id; ?>" readonly style="background:#eee;">
        </div>
        <div>
          <label>Subject Code:</label>
          <select name="Sub_code" required>
            <option value="">-- Select Subject --</option>
            <?php
            $sub_r = mysqli_query($conn, "SELECT * FROM subject");
            while($sub = mysqli_fetch_assoc($sub_r)){
               $code = $sub['Sub_code'] ?? $sub['Sub_id'] ?? '';
               $name = $sub['Sub_name'] ?? $sub['Subject_name'] ?? $code;
               echo "<option value='$code'>$code - $name</option>";
            }
            ?>
          </select>
        </div>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
        <div>
          <label>Unit / Module:</label>
          <select name="Unit_no" required>
            <option value="Unit 1">Unit 1</option>
            <option value="Unit 2">Unit 2</option>
            <option value="Unit 3">Unit 3</option>
            <option value="Unit 4">Unit 4</option>
            <option value="Unit 5">Unit 5</option>
          </select>
        </div>
        <div>
          <label>Bloom's Taxonomy Level:</label>
          <select name="Blooms_level" required>
            <option value="L1 - Remember">L1 - Remember (Define / State)</option>
            <option value="L2 - Understand" selected>L2 - Understand (Explain / Describe)</option>
            <option value="L3 - Apply">L3 - Apply (Calculate / Solve)</option>
            <option value="L4 - Analyze">L4 - Analyze (Compare / Distinguish)</option>
            <option value="L5 - Evaluate">L5 - Evaluate (Justify / Criticize)</option>
            <option value="L6 - Create">L6 - Create (Design / Construct)</option>
          </select>
        </div>
      </div>
      
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
        <div>
          <label>Question Marks:</label>
          <select name="Que_marks" required>
            <option value="">-- Select Marks --</option>
            <option value="2">2 Marks (Short)</option>
            <option value="4">4 Marks (Short)</option>
            <option value="5" selected>5 Marks (Medium)</option>
            <option value="8">8 Marks (Medium)</option>
            <option value="10">10 Marks (Long / Essay)</option>
            <option value="14">14 Marks</option>
            <option value="16">16 Marks</option>
            <option value="20">20 Marks (Comprehensive)</option>
          </select>
        </div>
        <div>
          <label>Status:</label>
          <select name="Status" required>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
      </div>

      <label>Question Text:</label>
      <textarea name="Question" rows="4" required placeholder="Enter Short or Long question text..."></textarea>
      
      <input type="submit" name="save" value="Save Question">
    </form>
  </div>
</div>
<h3>📋 Saved Records</h3>
<table>
<tr><th>Que Id</th><th>Sub Code</th><th>Unit & Bloom's</th><th>Marks</th><th>Question</th><th>Status</th></tr>
<?php
$r = mysqli_query($conn, "SELECT * FROM short_long_question ORDER BY Que_id DESC");
while($row = mysqli_fetch_assoc($r)){
  $unit = $row['Unit_no'] ?? 'Unit 1';
  $blooms = $row['Blooms_level'] ?? 'L2';
  echo "<tr>";
  echo "<td>{$row['Que_id']}</td>";
  echo "<td>{$row['Sub_code']}</td>";
  echo "<td><span class='badge-tag'>" . htmlspecialchars($unit) . "</span><br><small style='color:#555;'>" . htmlspecialchars($blooms) . "</small></td>";
  echo "<td><b>{$row['Que_marks']} M</b></td>";
  echo "<td>" . htmlspecialchars($row['Question']) . "</td>";
  echo "<td><span style='color:green; font-weight:bold;'>{$row['Status']}</span></td>";
  echo "</tr>";
}
?>
</table>
</div>
</body>
</html>
