<?php
include 'config.php';
$msg = "";

// Fixed 01 to 05
$fixed_streams = ["01","02","03","04","05"];

// Auto Dept ID - 01 + 01 = 0101
function getNextDeptId($conn, $str_id){
    $q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM stream WHERE Str_id='$str_id'");
    $row = mysqli_fetch_assoc($q);
    return $str_id . sprintf("%02d", $row['cnt'] + 1);
}

if(isset($_POST['save'])){
  $str_id = sprintf("%02d", $_POST['Str_id']);
  $str_name = $_POST['Str_name'];
  $dept_name = $_POST['Dept_name'];
  $dept_id = getNextDeptId($conn, $str_id);

  // Duplicate check Dept_id var
  $check = mysqli_query($conn, "SELECT * FROM stream WHERE Dept_id='$dept_id'");
  if(mysqli_num_rows($check)==0){
    $q = mysqli_query($conn, "INSERT INTO stream (Str_id, Str_name, Dept_id, Dept_name) VALUES ('$str_id','$str_name','$dept_id','$dept_name')");
    if($q){ $msg = "Saved successfully!"; }
  } else { $msg = "Already exists: $dept_id"; }
}

// Selection sathi sagla data
$all_data = [];
$res = mysqli_query($conn, "SELECT * FROM stream ORDER BY Str_id, Dept_id");
while($r = mysqli_fetch_assoc($res)){ $all_data[$r['Str_id']][] = $r; }
$json_data = json_encode($all_data);
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
.left-img img { width:100%; height:350px; object-fit:cover; border-radius:15px; }
.right-form { width:60%; background:#f7fbff; padding:20px; border-radius:15px; border:1px solid #b3e5fc; }
label { display:block; margin-top:12px; font-weight:bold; }
input[type=text], select { width:100%; padding:12px; margin-top:5px; border:2px solid #b3e5fc; border-radius:8px; box-sizing:border-box; }
input[type=submit] { width:220px; padding:12px; margin:20px auto 0; display:block; background:#1565c0; color:white; border:none; border-radius:25px; font-size:16px; font-weight:bold; cursor:pointer; }
.msg { text-align:center; color:green; font-weight:bold; }
.dept-preview{ text-align:center; background:#e3f2fd; padding:8px; border-radius:8px; margin-top:8px; font-weight:bold; color:#0a3d6b; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th { background:#0a3d6b; color:white; padding:10px; }
td { padding:8px; border:1px solid #b3e5fc; text-align:center; }
@media(max-width:700px){ .main-split{ flex-direction:column; } .left-img,.right-form{ width:100%; } }
</style>
</head>
<body>
<div class="container">
<div class="header">
  <div class="logo-left"><img src="logo.jpg"><div class="logo-text">Vivekanand College<br><span>Stream Entry Form</span></div></div>
</div>
<h2>Stream</h2>
<div class="nav"><a href="subject.php">Next: Subject</a> | <a href="logout.php">Logout</a></div>

<div class="main-split">
  <div class="left-img"><img src="stream.png"></div>
  <div class="right-form">
    <div class="msg"><?php echo $msg; ?></div>
    <form method="post">
      <label>Stream ID:</label>
      <select name="Str_id" id="Str_id" onchange="loadDepts()" required>
        <option value="">-- Select Stream --</option>
        <?php foreach($fixed_streams as $fs){ echo "<option value='$fs'>$fs</option>"; } ?>
      </select>

      <label>Stream Name:</label>
      <input type="text" name="Str_name" id="Str_name" placeholder="Ex: Science" required>

      <label>Department ID:</label>
      <select name="existing_dept" id="existing_dept" onchange="changeDept()">
        <option value="new">-- New Department --</option>
      </select>


      <label>Department Name:</label>
      <input type="text" name="Dept_name" id="Dept_name" placeholder="Ex: Physics " required>

      <input type="submit" name="save" value="Save">
    </form>
  </div>
</div>

<h3>📋 Saved Records</h3>
<table><tr><th>Stream ID</th><th>Stream Name</th><th>Dept ID</th><th>Dept Name</th></tr>
<?php
$r = mysqli_query($conn, "SELECT * FROM stream ORDER BY Str_id, Dept_id");
while($row = mysqli_fetch_assoc($r)){
  echo "<tr><td>{$row['Str_id']}</td><td>{$row['Str_name']}</td><td>{$row['Dept_id']}</td><td>{$row['Dept_name']}</td></tr>";
}
?>
</table>
</div>

<script>
let db = <?php echo $json_data; ?>;
function loadDepts(){
  let sid = document.getElementById('Str_id').value;
  let deptSel = document.getElementById('existing_dept');
  let preview = document.getElementById('deptPreview');
  let sname = document.getElementById('Str_name');
  deptSel.innerHTML = '<option value="new">-- New Department --</option>';
  if(db[sid]){
    sname.value = db[sid][0].Str_name;
    sname.readOnly = true; sname.style.background = "#eee";
    db[sid].forEach(row => {
      let opt = document.createElement('option');
      opt.value = row.Dept_id;
      opt.text = row.Dept_id + ' - ' + row.Dept_name;
      deptSel.appendChild(opt);
    });
    let nextSeq = String(db[sid].length + 1).padStart(2,'0');
    preview.innerText = `Next Dept ID = ${sid}${nextSeq} (01->0101, 02->0201)`;
  } else {
    sname.value = ""; sname.readOnly = false; sname.style.background = "white";
    preview.innerText = `New Dept ID = ${sid}01 (0101, 0201, 0301...)`;
  }
}
function changeDept(){
  let sid = document.getElementById('Str_id').value;
  let deptSel = document.getElementById('existing_dept');
  let dname = document.getElementById('Dept_name');
  let preview = document.getElementById('deptPreview');
  if(deptSel.value == 'new'){
    dname.value = ""; dname.readOnly = false;
    if(db[sid]) preview.innerText = `New Dept ID = ${sid}${String(db[sid].length+1).padStart(2,'0')}`;
    else preview.innerText = `New Dept ID = ${sid}01`;
  } else {
    let sel = db[sid].find(x => x.Dept_id == deptSel.value);
    if(sel){ dname.value = sel.Dept_name; preview.innerText = `Selected: ${sel.Dept_id}`; }
  }
}
</script>

</body>
</html>