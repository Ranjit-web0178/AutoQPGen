<?php
include 'config.php';
$msg = "";
$msg_type = "green";

// Auto Qp_id
$ad = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(Qp_id) as nid FROM question_pattern"));
$next_num = $ad['nid'] ? $ad['nid'] + 1 : 1;
$auto_qp_id = $next_num;

if(isset($_POST['save'])){
  $qp_id = intval($_POST['Qp_id'] ?? 0); 
  $total = intval($_POST['Total_marks'] ?? 0);
  $m1 = max(0, intval($_POST['1_mark'] ?? 0));
  $m2 = max(0, intval($_POST['2_mark'] ?? 0));
  $m4 = max(0, intval($_POST['4_mark'] ?? 0));
  $m5 = max(0, intval($_POST['5_mark'] ?? 0));
  $m6 = max(0, intval($_POST['6_mark'] ?? 0));
  $m7 = max(0, intval($_POST['7_mark'] ?? 0));
  $m8 = max(0, intval($_POST['8_mark'] ?? 0));
  $m10 = max(0, intval($_POST['10_mark'] ?? 0));
  $m14 = max(0, intval($_POST['14_mark'] ?? 0));
  $m16 = max(0, intval($_POST['16_mark'] ?? 0));
  $m20 = max(0, intval($_POST['20_mark'] ?? 0));

  if($total <= 0){
    $msg = "Error: Total Marks must be greater than 0.";
    $msg_type = "red";
  } else {
    // Server-side sum verification
    $calculated_sum = ($m1*1) + ($m2*2) + ($m4*4) + ($m5*5) + ($m6*6) + ($m7*7) + ($m8*8) + ($m10*10) + ($m14*14) + ($m16*16) + ($m20*20);
    $total_questions = $m1 + $m2 + $m4 + $m5 + $m6 + $m7 + $m8 + $m10 + $m14 + $m16 + $m20;

    if($total_questions == 0){
      $msg = "Error: You must define at least one question in the pattern.";
      $msg_type = "red";
    } elseif($calculated_sum != $total){
      $msg = "Warning: Blueprint sum ($calculated_sum marks) does not equal Total Marks ($total). Pattern saved anyway — please verify.";
      $msg_type = "red";
      // Still save but warn
      $check = mysqli_query($conn, "SELECT * FROM question_pattern WHERE Qp_id='$qp_id'");
      if(mysqli_num_rows($check) == 0){
        $q = mysqli_query($conn, "INSERT INTO question_pattern (Qp_id, Total_marks, 1_mark, 2_mark, 4_mark, 5_mark, 6_mark, 7_mark, 8_mark, 10_mark, 14_mark, 16_mark, 20_mark) VALUES ('$qp_id','$total','$m1','$m2','$m4','$m5','$m6','$m7','$m8','$m10','$m14','$m16','$m20')");
        if($q){ 
            $auto_qp_id = intval($qp_id) + 1;
        } else {
            $msg = "Database Error: " . mysqli_error($conn);
        }
      } else { $msg = "Error: Pattern ID $qp_id already exists."; $msg_type = "red"; }
    } else {
      $check = mysqli_query($conn, "SELECT * FROM question_pattern WHERE Qp_id='$qp_id'");
      if(mysqli_num_rows($check) == 0){
        $q = mysqli_query($conn, "INSERT INTO question_pattern (Qp_id, Total_marks, 1_mark, 2_mark, 4_mark, 5_mark, 6_mark, 7_mark, 8_mark, 10_mark, 14_mark, 16_mark, 20_mark) VALUES ('$qp_id','$total','$m1','$m2','$m4','$m5','$m6','$m7','$m8','$m10','$m14','$m16','$m20')");
        if($q){ 
            $msg = "Pattern saved successfully! ($total_questions questions = $total marks)"; 
            $msg_type = "green";
            $auto_qp_id = intval($qp_id) + 1;
        } else {
            $msg = "Database Error: " . mysqli_error($conn);
            $msg_type = "red";
        }
      } else { $msg = "Error: Pattern ID $qp_id already exists."; $msg_type = "red"; }
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Question Pattern - Vivekanand College</title>
<style>
body { font-family: Arial; background: #e1f5fe; margin:0; padding:20px; font-size:14px; }
.container { background:white; max-width:1200px; margin:auto; padding:30px 40px; border-radius:20px; box-shadow:0 4px 15px rgba(0,0,0,0.1); }
.header { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.logo-left { display:flex; align-items:center; gap:15px; }
.logo-left img { width:90px; height:90px; object-fit:contain; }
.logo-text { font-weight:bold; color:#0a3d6b; font-size:23px; }
.logo-text span { font-size:14px; color:#555; font-weight:normal; }
h2 { color:#0a2a5a; text-align:center; font-size:32px; margin:10px 0 5px; }
.nav { text-align:center; margin-bottom:20px; }
.nav a { color:#0288d1; text-decoration:none; font-weight:bold; margin:0 10px; }
.main-split { display:flex; gap:30px; align-items:flex-start; }
.left-img { width:35%; position:sticky; top:20px; }
.left-img img { width:100%; height:500px; object-fit:cover; border-radius:15px; }
.right-form { width:65%; background:#f7fbff; padding:20px; border-radius:15px; border:1px solid #b3e5fc; }
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px 15px; }
label { display:block; margin-top:8px; font-weight:bold; font-size:13px; }
input[type=text], input[type=number] { width:100%; padding:10px; margin-top:3px; border:2px solid #b3e5fc; border-radius:8px; box-sizing:border-box; }
.full { grid-column: 1 / span 2; }
input[type=submit] { width:220px; padding:12px; margin:20px auto 0; display:block; background:#1565c0; color:white; border:none; border-radius:25px; font-size:16px; font-weight:bold; cursor:pointer; }
.msg { text-align:center; font-weight:bold; margin-bottom:12px; padding:8px; border-radius:6px; }
.msg.green { color:#2e7d32; background:#e8f5e9; }
.msg.red { color:#c62828; background:#ffebee; }
.sum-preview { text-align:center; background:#e3f2fd; padding:10px; border-radius:8px; font-weight:bold; color:#0a3d6b; margin-top:12px; font-size:14px; }
table { width:100%; border-collapse:collapse; margin-top:20px; font-size:12px; }
th { background:#0a3d6b; color:white; padding:8px; }
td { padding:6px; border:1px solid #b3e5fc; text-align:center; }
@media(max-width:700px){ .main-split{ flex-direction:column; } .left-img,.right-form{ width:100%; } .form-grid{ grid-template-columns:1fr; } .full{ grid-column:1; } }
</style>
</head>
<body>
<div class="container">
<div class="header">
  <div class="logo-left"><img src="logo.jpg"><div class="logo-text">Vivekanand College<br><span>Question Pattern Entry</span></div></div>
</div>
<h2>Question Pattern</h2>
<div class="nav"><a href="dashboard.php">Dashboard</a> | <a href="staff_transaction.php">Back: Staff Trans</a> | <a href="mcq_question.php">Next: MCQ Que</a> | <a href="logout.php">Logout</a></div>

<div class="main-split">
  <div class="left-img"><img src="qp.png"></div>
  <div class="right-form">
    <?php if($msg!=""){ echo "<div class='msg $msg_type'>$msg</div>"; } ?>
    <form method="post" id="patternForm">
      <div class="form-grid">
        <div><label>Pattern Id:</label><input type="text" name="Qp_id" value="<?php echo $auto_qp_id; ?>" readonly style="background:#eee;"></div>
        <div><label>Total Marks:</label><input type="number" name="Total_marks" id="Total_marks" min="1" required placeholder="e.g. 80" oninput="calcSum()"></div>
        
        <div><label>No. of 1 Mark Q:</label><input type="number" name="1_mark" id="m1" value="0" min="0" required oninput="calcSum()"></div>
        <div><label>No. of 2 Mark Q:</label><input type="number" name="2_mark" id="m2" value="0" min="0" required oninput="calcSum()"></div>
        
        <div><label>No. of 4 Mark Q:</label><input type="number" name="4_mark" id="m4" value="0" min="0" required oninput="calcSum()"></div>
        <div><label>No. of 5 Mark Q:</label><input type="number" name="5_mark" id="m5" value="0" min="0" required oninput="calcSum()"></div>

        <div><label>No. of 6 Mark Q:</label><input type="number" name="6_mark" id="m6" value="0" min="0" required oninput="calcSum()"></div>
        <div><label>No. of 7 Mark Q:</label><input type="number" name="7_mark" id="m7" value="0" min="0" required oninput="calcSum()"></div>

        <div><label>No. of 8 Mark Q:</label><input type="number" name="8_mark" id="m8" value="0" min="0" required oninput="calcSum()"></div>
        <div><label>No. of 10 Mark Q:</label><input type="number" name="10_mark" id="m10" value="0" min="0" required oninput="calcSum()"></div>

        <div><label>No. of 14 Mark Q:</label><input type="number" name="14_mark" id="m14" value="0" min="0" required oninput="calcSum()"></div>
        <div><label>No. of 16 Mark Q:</label><input type="number" name="16_mark" id="m16" value="0" min="0" required oninput="calcSum()"></div>

        <div class="full"><label>No. of 20 Mark Q:</label><input type="number" name="20_mark" id="m20" value="0" min="0" required oninput="calcSum()"></div>
      </div>

      <div class="sum-preview" id="sumPreview">Blueprint: 0 questions = 0 marks (Enter Total Marks above)</div>

      <input type="submit" name="save" value="Save Pattern">
    </form>
  </div>
</div>
<h3>📋 Saved Patterns</h3>
<table>
<tr><th>Qp_id</th><th>Total</th><th>1M</th><th>2M</th><th>4M</th><th>5M</th><th>6M</th><th>7M</th><th>8M</th><th>10M</th><th>14M</th><th>16M</th><th>20M</th></tr>
<?php
$r = mysqli_query($conn, "SELECT * FROM question_pattern ORDER BY Qp_id DESC");
while($row = mysqli_fetch_assoc($r)){
  echo "<tr><td>{$row['Qp_id']}</td><td><b>{$row['Total_marks']}</b></td><td>{$row['1_mark']}</td><td>{$row['2_mark']}</td><td>{$row['4_mark']}</td><td>{$row['5_mark']}</td><td>{$row['6_mark']}</td><td>{$row['7_mark']}</td><td>{$row['8_mark']}</td><td>{$row['10_mark']}</td><td>{$row['14_mark']}</td><td>{$row['16_mark']}</td><td>{$row['20_mark']}</td></tr>";
}
?>
</table>
</div>

<script>
function calcSum(){
  let weights = {m1:1, m2:2, m4:4, m5:5, m6:6, m7:7, m8:8, m10:10, m14:14, m16:16, m20:20};
  let totalQ = 0, totalM = 0;
  for(let id in weights){
    let v = parseInt(document.getElementById(id).value) || 0;
    if(v < 0) { document.getElementById(id).value = 0; v = 0; }
    totalQ += v;
    totalM += v * weights[id];
  }
  let target = parseInt(document.getElementById('Total_marks').value) || 0;
  let preview = document.getElementById('sumPreview');
  if(target > 0 && totalM === target){
    preview.innerText = `✅ Blueprint: ${totalQ} questions = ${totalM} marks (Matches Total!)`;
    preview.style.background = '#e8f5e9'; preview.style.color = '#2e7d32';
  } else if(target > 0){
    preview.innerText = `⚠️ Blueprint: ${totalQ} questions = ${totalM} marks (Target: ${target} marks — Difference: ${totalM - target})`;
    preview.style.background = '#fff3e0'; preview.style.color = '#e65100';
  } else {
    preview.innerText = `Blueprint: ${totalQ} questions = ${totalM} marks (Enter Total Marks above)`;
    preview.style.background = '#e3f2fd'; preview.style.color = '#0a3d6b';
  }
}
</script>
</body>
</html>