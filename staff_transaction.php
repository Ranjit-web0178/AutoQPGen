<?php
include 'config.php';
$msg = "";
$msg_type = "green";

if(isset($_POST['save'])){
  $Staff_id = trim($_POST['Staff_id'] ?? ''); 
  $Sub_code = strtoupper(trim($_POST['Sub_code'] ?? ''));
  
  if(empty($Staff_id) || empty($Sub_code)){
    $msg = "Error: Please select both Staff and Subject.";
    $msg_type = "red";
  } else {
    $Staff_id_safe = mysqli_real_escape_string($conn, $Staff_id);
    $Sub_code_safe = mysqli_real_escape_string($conn, $Sub_code);

    $check = mysqli_query($conn, "SELECT * FROM staff_transaction WHERE Staff_id='$Staff_id_safe'");
    if(mysqli_num_rows($check) == 0){
      $q = mysqli_query($conn, "INSERT INTO staff_transaction (Staff_id, Sub_code) VALUES ('$Staff_id_safe','$Sub_code_safe')");
      if($q){ 
        $msg = "Staff allotment saved successfully ($Staff_id -> $Sub_code)!"; 
        $msg_type = "green";
      } else {
        $msg = "Database Error: " . mysqli_error($conn);
        $msg_type = "red";
      }
    } else { 
      $q = mysqli_query($conn, "UPDATE staff_transaction SET Sub_code='$Sub_code_safe' WHERE Staff_id='$Staff_id_safe'");
      if($q) {
        $msg = "Staff allotment updated successfully ($Staff_id -> $Sub_code)!"; 
        $msg_type = "green";
      } else {
        $msg = "Database Error: " . mysqli_error($conn);
        $msg_type = "red";
      }
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Staff Allotment - Vivekanand College</title>
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
.left-img { width:40%; }
.left-img img { width:100%; height:370px; object-fit:cover; border-radius:15px; }
.right-form { width:60%; background:#f7fbff; padding:20px; border-radius:15px; border:1px solid #b3e5fc; }
label { display:block; margin-top:12px; font-weight:bold; }
select { width:100%; padding:12px; margin-top:5px; border:2px solid #b3e5fc; border-radius:8px; box-sizing:border-box; }
input[type=submit] { width:220px; padding:12px; margin:20px auto 0; display:block; background:#1565c0; color:white; border:none; border-radius:25px; font-size:16px; font-weight:bold; cursor:pointer; }
.msg { text-align:center; font-weight:bold; margin-bottom:12px; padding:8px; border-radius:6px; }
.msg.green { color:#2e7d32; background:#e8f5e9; }
.msg.red { color:#c62828; background:#ffebee; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th { background:#0a3d6b; color:white; padding:10px; }
td { padding:8px; border:1px solid #b3e5fc; text-align:center; }
@media(max-width:700px){ .main-split{ flex-direction:column; } .left-img,.right-form{ width:100%; } }
</style>
</head>
<body>
<div class="container">
<div class="header">
  <div class="logo-left"><img src="logo.jpg"><div class="logo-text">Vivekanand College<br><span>Staff Allotment Entry</span></div></div>
</div>
<h2>Staff Transaction</h2>
<div class="nav"><a href="dashboard.php">Dashboard</a> | <a href="staff_master.php">Back: Staff Master</a> | <a href="question_pattern.php">Next: Question Pattern</a> | <a href="logout.php">Logout</a></div>

<div class="main-split">
  <div class="left-img"><img src="staff.png"></div>
  <div class="right-form">
    <?php if($msg!=""){ echo "<div class='msg $msg_type'>$msg</div>"; } ?>
    <form method="post">
      <label>Staff Id:</label>
      <select name="Staff_id" required>
        <option value="">-- Select Staff --</option>
        <?php
        $staff_r = mysqli_query($conn, "SELECT Staff_id, Name FROM staff_master ORDER BY Staff_id ASC");
        if($staff_r){
          while($s = mysqli_fetch_assoc($staff_r)){
            echo "<option value='{$s['Staff_id']}'>{$s['Staff_id']} - {$s['Name']}</option>";
          }
        }
        ?>
      </select>
      
      <label>Subject Code:</label>
      <select name="Sub_code" required>
        <option value="">-- Select Subject --</option>
        <?php
        $sub_r = mysqli_query($conn, "SELECT Sub_code, Sub_name FROM subject ORDER BY Sub_code ASC");
        if($sub_r){
          while($sub = mysqli_fetch_assoc($sub_r)){
             echo "<option value='{$sub['Sub_code']}'>{$sub['Sub_code']} - {$sub['Sub_name']}</option>";
          }
        }
        ?>
      </select>
      
      <input type="submit" name="save" value="Save Allotment">
    </form>
  </div>
</div>

<h3>📋 Connected Allotment Records</h3>
<table>
<tr><th>Staff Id</th><th>Staff Name</th><th>Sub Code</th><th>Sub Name</th></tr>
<?php
$r = mysqli_query($conn, "SELECT t.Staff_id, t.Sub_code, m.Name, s.Sub_name 
                          FROM staff_transaction t
                          JOIN staff_master m ON t.Staff_id = m.Staff_id
                          JOIN subject s ON t.Sub_code = s.Sub_code
                          ORDER BY t.Staff_id ASC");

if(!$r || mysqli_num_rows($r) == 0){
  echo "<tr><td colspan='4' style='color:#777;'>No allotment records found yet.</td></tr>";
} else {
  while($row = mysqli_fetch_assoc($r)){
    echo "<tr><td>{$row['Staff_id']}</td><td>{$row['Name']}</td><td>{$row['Sub_code']}</td><td>{$row['Sub_name']}</td></tr>";
  }
}
?>
</table>

</div>
</body>
</html>