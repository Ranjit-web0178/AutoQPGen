<?php
include 'config.php';
$msg = "";

$ad = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(CAST(SUBSTRING(Que_id, 2) AS UNSIGNED)) as nid FROM short_long_question"));
$next_num = $ad['nid'] ? $ad['nid'] + 1 : 1;
$auto_que_id = "Q" . sprintf("%02d", $next_num);

if(isset($_POST['save'])){
  $que_id = $_POST['Que_id']; 
  $sub_code = $_POST['Sub_code'];
  $que_marks = $_POST['Que_marks'];
  $question = mysqli_real_escape_string($conn, $_POST['Question']);
  $status = $_POST['Status'];
  
  $check = mysqli_query($conn, "SELECT * FROM short_long_question WHERE Que_id='$que_id'");
  if(mysqli_num_rows($check) == 0){
    $q = mysqli_query($conn, "INSERT INTO short_long_question (Que_id, Sub_code, Que_marks, Question, Status) VALUES ('$que_id','$sub_code','$que_marks','$question','$status')");
    if($q){ 
        $msg = "Question Saved successfully"; 
        $next_num = intval(substr($que_id,1)) + 1;
        $auto_que_id = "Q" . sprintf("%02d", $next_num);
    } else {
        $msg = "Error: " . mysqli_error($conn);
    }
  } else { $msg = "Already exists"; }
}
?>
<!DOCTYPE html>
<html>
<head>
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
input[type=text], input[type=number], select, textarea { width:100%; padding:12px; margin-top:5px; border:2px solid #b3e5fc; border-radius:8px; box-sizing:border-box; font-family:Arial; }
input[type=submit] { width:220px; padding:12px; margin:20px auto 0; display:block; background:#1565c0; color:white; border:none; border-radius:25px; font-size:16px; font-weight:bold; cursor:pointer; }
.msg { text-align:center; color:green; font-weight:bold; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th { background:#0a3d6b; color:white; padding:10px; font-size:13px; }
td { padding:8px; border:1px solid #b3e5fc; text-align:left; font-size:13px; }
@media(max-width:700px){ .main-split{ flex-direction:column; } .left-img,.right-form{ width:100%; } }
</style>
</head>
<body>
<div class="container">
<div class="header">
  <div class="logo-left"><img src="logo.jpg"><div class="logo-text">Vivekanand College<br><span>Short / Long Question Entry</span></div></div>
</div>
<h2>Short & Long Questions</h2>
<div class="nav"><a href="mcq_question.php">Back:MCQ</a> | <a href="generate_paper.php">Next:Generate Paper</a> | <a href="logout.php">Logout</a></div>

<div class="main-split">
  <div class="left-img"><img src="slq.png"></div>
  <div class="right-form">
    <div class="msg"><?php echo $msg; ?></div>
    <form method="post">
      <label>Question Id:</label>
      <input type="text" name="Que_id" value="<?php echo $auto_que_id; ?>" readonly style="background:#eee;">
      
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
      
      <label>Question Marks:</label>
      <select name="Que_marks" required>
        <option value="">-- Select Marks --</option>

        <option value="2">2 Marks (Short)</option>
                <option value="4">4 Marks (Short)</option>
        <
        <option value="5">5 Marks</option>
                <option value="8">8 Marks (Short)</option>

        <option value="10">10 Marks (Long)</option>
                <option value="15">15 Marks (Short)</option>

        <option value="16">16 Marks</option>
                <option value="20">20 Marks (Short)</option>

      </select>

      <label>Question:</label>
      <textarea name="Question" rows="4" required placeholder="Enter Short or Long question..."></textarea>
      
      <label>Status:</label>
      <select name="Status" required>
        <option value="Active">Active</option>
        <option value="Inactive">Inactive</option>
      </select>
      
      <input type="submit" name="save" value="Save Question">
    </form>
  </div>
</div>
<h3>📋 Saved Records</h3>
<table>
<tr><th>Que Id</th><th>Sub Code</th><th>Marks</th><th>Question</th><th>Status</th></tr>
<?php
$r = mysqli_query($conn, "SELECT * FROM short_long_question ORDER BY Que_id DESC");
while($row = mysqli_fetch_assoc($r)){
  echo "<tr><td>{$row['Que_id']}</td><td>{$row['Sub_code']}</td><td>{$row['Que_marks']}</td><td>{$row['Question']}</td><td>{$row['Status']}</td></tr>";
}
?>
</table>
</div>
</body>
</html>