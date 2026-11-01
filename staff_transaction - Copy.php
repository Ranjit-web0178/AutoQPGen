<?php
include 'config.php';
$msg = "";

if(isset($_POST['save'])){
  $Staff_id = $_POST['Staff_id']; 
  $Sub_code = $_POST['Sub_code'];
  
  $check = mysqli_query($conn, "SELECT * FROM staff_transaction WHERE Staff_id='$Staff_id'");
  if(mysqli_num_rows($check) == 0){
    $q = mysqli_query($conn, "INSERT INTO staff_transaction (Staff_id, Sub_code) VALUES ('$Staff_id','$Sub_code')");
    if($q){ 
        $msg = "Saved successfully"; 
    } else {
        $msg = "Error: ".mysqli_error($conn);
    }
  } else { 
    $q = mysqli_query($conn, "UPDATE staff_transaction SET Sub_code='$Sub_code' WHERE Staff_id='$Staff_id'");
    if($q) $msg = "Updated successfully"; 
    else $msg = "Error: ".mysqli_error($conn);
  }
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
.left-img { width:40%; }
.left-img img { width:100%; height:370px; object-fit:cover; border-radius:15px; }
.right-form { width:60%; background:#f7fbff; padding:20px; border-radius:15px; border:1px solid #b3e5fc; }
label { display:block; margin-top:12px; font-weight:bold; }
select { width:100%; padding:12px; margin-top:5px; border:2px solid #b3e5fc; border-radius:8px; box-sizing:border-box; }
input[type=submit] { width:220px; padding:12px; margin:20px auto 0; display:block; background:#1565c0; color:white; border:none; border-radius:25px; font-size:16px; font-weight:bold; cursor:pointer; }
.msg { text-align:center; color:green; font-weight:bold; margin-bottom:10px; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th { background:#0a3d6b; color:white; padding:10px; }
td { padding:8px; border:1px solid #b3e5fc; text-align:center; }
@media(max-width:700px){ .main-split{ flex-direction:column; } .left-img,.right-form{ width:100%; } }
</style>
</head>
<body>
<div class="container">
<div class="header">
  <div class="logo-left"><img src="logo.jpg"><div class="logo-text">Vivekanand College<br><span>Staff Transaction Entry</span></div></div>
</div>
<h2>Staff Transaction</h2>
<div class="nav"><a href="staff_master.php">Back:Staff Master</a> | <a href="question_pattern.php">Next:Question Pattern</a> | <a href="logout.php">Logout</a></div>

<div class="main-split">
  <div class="left-img"><img src="staff.png"></div>
  <div class="right-form">
    <div class="msg"><?php echo $msg; ?></div>
    <form method="post">
      <label>Staff Id:</label>
      <select name="Staff_id" required>
        <option value="">-- Select Staff --</option>
        <?php
        $staff_r = mysqli_query($conn, "SELECT Staff_id, Name FROM staff_master");
        while($s = mysqli_fetch_assoc($staff_r)){
          echo "<option value='{$s['Staff_id']}'>{$s['Staff_id']} - {$s['Name']}</option>";
        }
        ?>
      </select>
      
      <label>Subject Code:</label>
      <select name="Sub_code" required>
        <option value="">-- Select Subject --</option>
        <?php
        $sub_r = mysqli_query($conn, "SELECT Sub_code, Sub_name FROM subject");
        while($sub = mysqli_fetch_assoc($sub_r)){
           echo "<option value='{$sub['Sub_code']}'>{$sub['Sub_code']} - {$sub['Sub_name']}</option>";
        }
        ?>
      </select>
      
      <input type="submit" name="save" value="Save">
    </form>
  </div>
</div>

<h3>📋 Connected Data (Master + Transaction JOIN)</h3>
<table>
<tr><th>Staff Id</th><th>Staff Name</th><th>Sub Code</th><th>Sub Name</th></tr>
<?php
// Ya JOIN ne connection disil
$r = mysqli_query($conn, "SELECT t.Staff_id, t.Sub_code, m.Name, s.Sub_name 
                          FROM staff_transaction t
                          JOIN staff_master m ON t.Staff_id = m.Staff_id
                          JOIN subject s ON t.Sub_code = s.Sub_code");

if(!$r){
  echo "<tr><td colspan='4' style='color:red'>Error: ".mysqli_error($conn)."</td></tr>";
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