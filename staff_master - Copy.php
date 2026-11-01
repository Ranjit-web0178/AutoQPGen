<?php
include 'config.php';
$msg = "";

$ad = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(CAST(Staff_id AS UNSIGNED)) as nid FROM staff_master"));
$next_num = $ad['nid'] ? $ad['nid'] + 1 : 1;
$auto_staff_id = sprintf("%02d", $next_num);

if(isset($_POST['save'])){
  $staff_id = $_POST['Staff_id']; 
  $dept_id = $_POST['Dept_id'];
  $str_id = $_POST['Str_id'];
  $name = $_POST['Name'];
  $contact = $_POST['Contact'];
  
  $check = mysqli_query($conn, "SELECT * FROM staff_master WHERE Staff_id='$staff_id'");
  if(mysqli_num_rows($check) == 0){
    $q = mysqli_query($conn, "INSERT INTO staff_master (Staff_id, Dept_id, Str_id, Name, Contact) VALUES ('$staff_id','$dept_id','$str_id','$name','$contact')");
    if($q){ 
        $msg = "Saved successfully"; 
        $next_num = intval($staff_id) + 1;
        $auto_staff_id = sprintf("%02d", $next_num);
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
.logo-left img { width:90px; height:90px; object-fit:contain; border:none; }
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
input[type=text] { width:100%; padding:12px; margin-top:5px; border:2px solid #b3e5fc; border-radius:8px; box-sizing:border-box; }
input[type=submit] { width:220px; padding:12px; margin:20px auto 0; display:block; background:#1565c0; color:white; border:none; border-radius:25px; font-size:16px; font-weight:bold; cursor:pointer; }
.msg { text-align:center; color:green; font-weight:bold; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th { background:#0a3d6b; color:white; padding:10px; }
td { padding:8px; border:1px solid #b3e5fc; text-align:center; }
@media(max-width:700px){ .main-split{ flex-direction:column; } .left-img,.right-form{ width:100%; } }
</style>
</head>
<body>
<div class="container">
<div class="header">
  <div class="logo-left"><img src="logo.jpg"><div class="logo-text">Vivekanand College<br><span>Staff Master Entry Form</span></div></div>
</div>
<h2>Staff Master</h2>
<div class="nav"><a href="subject.php">Back:Subject</a> | <a href="staff_transaction.php">Next:Staff Transaction</a> | <a href="logout.php">Logout</a></div>

<div class="main-split">
  <div class="left-img"><img src="staff.png"></div>
  <div class="right-form">
    <div class="msg"><?php echo $msg; ?></div>
    <form method="post">
      <label>Staff Id :</label>
      <input type="text" name="Staff_id" value="<?php echo $auto_staff_id; ?>" readonly style="background:#eee;">
      
      <label>Department Id:</label>
      <input type="text" name="Dept_id" required>
      
      <label>Stream Id:</label>
      <input type="text" name="Str_id" required>
      
      <label>Staff Name:</label>
      <input type="text" name="Name" required>
      
      <label>Contact No:</label>
      <input type="text" name="Contact" required>
      
      <input type="submit" name="save" value="Save">
    </form>
  </div>
</div>
<h3>📋 Saved Records</h3>
<table>
<tr><th>Staff Id</th><th>Dept Id</th><th>Str Id</th><th>Name</th><th>Contact</th></tr>
<?php
$r = mysqli_query($conn, "SELECT * FROM staff_master ORDER BY CAST(Staff_id AS UNSIGNED) DESC");
while($row = mysqli_fetch_assoc($r)){
  echo "<tr><td>{$row['Staff_id']}</td><td>{$row['Dept_id']}</td><td>{$row['Str_id']}</td><td>{$row['Name']}</td><td>{$row['Contact']}</td></tr>";
}
?>
</table>
</div>
</body>
</html>