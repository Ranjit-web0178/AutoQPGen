<?php
include 'config.php';
$msg = "";
$msg_type = "green";

// Stream data
$streams = [];
$q = mysqli_query($conn, "SELECT * FROM stream ORDER BY Str_id, Dept_id");
while($r=mysqli_fetch_assoc($q)){ $streams[$r['Str_id']][] = $r; }

// Auto count for 51,52...
$count_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM subject"));
$next_no = 51 + intval($count_res['cnt']);

if(isset($_POST['save'])){
  $str_id = trim($_POST['Str_id'] ?? '');
  $dept_id = trim($_POST['Dept_id'] ?? '');
  $sub_code = strtoupper(trim($_POST['Sub_code'] ?? ''));
  $sub_name = trim($_POST['Sub_name'] ?? '');
  $semester = trim($_POST['Semester'] ?? '');

  if(empty($str_id) || empty($dept_id) || empty($sub_code) || empty($sub_name) || empty($semester)){
    $msg = "Error: All fields are required.";
    $msg_type = "red";
  } elseif(strlen($sub_name) < 2){
    $msg = "Error: Subject Name must be at least 2 characters.";
    $msg_type = "red";
  } else {
    $str_id_safe = mysqli_real_escape_string($conn, $str_id);
    $dept_id_safe = mysqli_real_escape_string($conn, $dept_id);
    $sub_code_safe = mysqli_real_escape_string($conn, $sub_code);
    $sub_name_safe = mysqli_real_escape_string($conn, $sub_name);
    $semester_safe = mysqli_real_escape_string($conn, $semester);

    $check = mysqli_query($conn, "SELECT Sub_code FROM subject WHERE Sub_code='$sub_code_safe'");
    if(mysqli_num_rows($check)==0){
      $ins = mysqli_query($conn, "INSERT INTO subject (Str_id, Dept_id, Sub_code, Sub_name, Semester) VALUES ('$str_id_safe','$dept_id_safe','$sub_code_safe','$sub_name_safe','$semester_safe')");
      if($ins) {
        $msg = "Saved successfully: $sub_code - $sub_name";
        $msg_type = "green";
      } else {
        $msg = "Database Error: " . mysqli_error($conn);
        $msg_type = "red";
      }
    } else { 
      $msg = "Error: Subject code $sub_code already exists."; 
      $msg_type = "red";
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Subject Master - Vivekanand College</title>
<style>
body { font-family: Arial; background: #e1f5fe; margin:0; padding:20px; font-size:14px; }
.container { background:white; max-width:1150px; margin:auto; padding:30px 40px; border-radius:20px; box-shadow:0 4px 15px rgba(0,0,0,0.1); }
.header { display:flex; align-items:center; gap:15px; }
.logo-left { display:flex; align-items:center; gap:15px; }
.logo-left img { width:90px; height:90px; object-fit:contain; }
.logo-text { font-weight:bold; color:#0a3d6b; font-size:23px; }
h2 { color:#0a2a5a; text-align:center; font-size:32px; }
.nav { text-align:center; margin-bottom:20px; }
.nav a { color:#0288d1; text-decoration:none; font-weight:bold; margin:0 10px; }
.main-split { display:flex; gap:30px; }
.left-img { width:40%; } .left-img img { width:100%; height:420px; object-fit:cover; border-radius:15px; }
.right-form { width:60%; background:#f7fbff; padding:20px; border-radius:15px; border:1px solid #b3e5fc; }
label { display:block; margin-top:12px; font-weight:bold; }
input[type=text], select { width:100%; padding:12px; margin-top:5px; border:2px solid #b3e5fc; border-radius:8px; box-sizing:border-box; }
input[type=submit] { width:220px; padding:12px; margin:20px auto 0; display:block; background:#1565c0; color:white; border:none; border-radius:25px; font-size:16px; font-weight:bold; cursor:pointer; }
.msg { text-align:center; font-weight:bold; margin-bottom:12px; padding:8px; border-radius:6px; }
.msg.green { color:#2e7d32; background:#e8f5e9; }
.msg.red { color:#c62828; background:#ffebee; }
.preview { background:#e3f2fd; padding:10px; border-radius:8px; text-align:center; font-weight:bold; color:#0a3d6b; margin-top:10px; }
table { width:100%; border-collapse:collapse; margin-top:20px; } th { background:#0a3d6b; color:white; padding:10px; } td { padding:8px; border:1px solid #b3e5fc; text-align:center; }
</style>
</head>
<body>
<div class="container">
<div class="header"><div class="logo-left"><img src="logo.jpg"><div class="logo-text">Vivekanand College<br><span>Subject Entry Form</span></div></div></div>
<h2>Subject</h2>
<div class="nav"><a href="stream.php">Back: Stream</a> | <a href="staff_master.php">Next: Staff</a> | <a href="dashboard.php">Dashboard</a> | <a href="logout.php">Logout</a></div>

<div class="main-split">
  <div class="left-img"><img src="sub.jpeg"></div>
  <div class="right-form">
    <?php if($msg!=""){ echo "<div class='msg $msg_type'>$msg</div>"; } ?>
    <form method="post">
      <label>Stream ID:</label>
      <select id="Str_id" name="Str_id" onchange="loadDepts()" required>
        <option value="">-- Select Stream --</option>
        <?php foreach(array_keys($streams) as $sid){ echo "<option value='$sid'>$sid - {$streams[$sid][0]['Str_name']}</option>"; } ?>
      </select>

      <label>Department ID:</label>
      <select id="Dept_id" name="Dept_id" onchange="genCode()" required>
        <option value="">-- Select Dept --</option>
      </select>

    
      <label>Course Type:</label>
      <select id="CourseType" onchange="genCode()" required>
        <option value="DSC">DSC (Core)</option>
        <option value="DSE">DSE (Elective)</option>
      </select>

    
      <label>Subject Code:</label>
      <input type="text" name="Sub_code" id="Sub_code" value="DSC06COM51" required readonly style="background:#eee; font-weight:bold;">

      <label>Subject Name:</label>
      <input type="text" name="Sub_name" id="Sub_name" placeholder="Ex: Operating System" required minlength="2">

      <label>Semester:</label>
      <select id="Semester" name="Semester" onchange="genCode()" required>
        <option value="VIII">Sem VIII</option>
        <option value="VII">Sem VII</option>
        <option value="VI">Sem VI</option>
        <option value="V">Sem V</option>
        <option value="IV">Sem IV</option>
        <option value="III">Sem III</option>
        <option value="II">Sem II</option>
        <option value="I">Sem I</option>
      </select>

      <div class="preview" id="preview">Preview: DSC06COM51</div>

      <input type="submit" name="save" value="Save Subject">
    </form>
  </div>
</div>

<h3>📋 Saved Records</h3>
<table><tr><th>Subject Code & Subject</th><th>Stream ID</th><th>Dept ID</th><th>Sem</th></tr>
<?php
$r = mysqli_query($conn, "SELECT * FROM subject ORDER BY Sub_code");
while($row = mysqli_fetch_assoc($r)){
  echo "<tr><td>{$row['Sub_code']} - {$row['Sub_name']}</td><td>{$row['Str_id']}</td><td>{$row['Dept_id']}</td><td>{$row['Semester']}</td></tr>";
}
?>
</table>
</div>

<script>
let streamData = <?php echo json_encode($streams); ?>;
let nextNo = <?php echo $next_no; ?>;

function loadDepts(){
  let sid = document.getElementById('Str_id').value;
  let deptSel = document.getElementById('Dept_id');
  deptSel.innerHTML = '<option value="">-- Select Dept --</option>';
  if(streamData[sid]){
    streamData[sid].forEach(row=>{
      let opt = document.createElement('option');
      opt.value = row.Dept_id;
      let short = row.Dept_name.substring(0,3).toUpperCase();
      opt.text = row.Dept_id + ' - ' + row.Dept_name + ' ('+short+')';
      opt.dataset.short = short;
      deptSel.appendChild(opt);
    });
  }
  genCode();
}
function genCode(){
  let sem = document.getElementById('Semester').value;
  let type = document.getElementById('CourseType').value;
  let deptSel = document.getElementById('Dept_id');
  let selOpt = deptSel.options[deptSel.selectedIndex];
  let deptShort = selOpt && selOpt.dataset.short ? selOpt.dataset.short : 'COM';
  let code = `${type}${sem}${deptShort}${nextNo}`;
  document.getElementById('Sub_code').value = code;
  let subName = document.getElementById('Sub_name').value;
  let prev = document.getElementById('preview');
  if(prev) {
    prev.innerText = `Preview: ${code}${subName ? ' - ' + subName : ''}`;
  }
}
document.getElementById('Sub_name').addEventListener('input', genCode);
loadDepts();
</script>
</body>
</html>