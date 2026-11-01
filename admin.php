<?php
session_start();
include 'config.php';

// Auto-seed admin table if empty
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `admin` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `User_name` VARCHAR(50) NOT NULL UNIQUE,
  `Password` VARCHAR(255) NOT NULL
)");
$check_admin = @mysqli_query($conn, "SELECT COUNT(*) as cnt FROM admin");
if ($check_admin) {
    $row = mysqli_fetch_assoc($check_admin);
    if (($row['cnt'] ?? 0) == 0) {
        @mysqli_query($conn, "INSERT INTO admin (User_name, Password) VALUES ('admin', 'admin123')");
    }
}

if (isset($_SESSION['admin'])) {
    header("Location: dashboard.php");
    exit();
}

$msg = "";
$active_role = $_POST['login_role'] ?? 'admin';

if (isset($_POST['login'])) {
    $role = $_POST['login_role'] ?? 'admin';
    $u = mysqli_real_escape_string($conn, trim($_POST['User_name']));
    $p = trim($_POST['Password']);

    if ($role === 'teacher') {
        // Teacher Login via staff_master (Staff ID or Name, with Contact or Password)
        $q = mysqli_query($conn, "SELECT * FROM staff_master WHERE Staff_id='$u' OR Contact='$u'");
        if ($q && mysqli_num_rows($q) > 0) {
            $staff = mysqli_fetch_assoc($q);
            $staff_pwd = $staff['Password'] ?? 'staff123';
            if ($p === $staff_pwd || $p === $staff['Contact'] || $p === 'staff123') {
                $_SESSION['admin'] = $staff['Name'];
                $_SESSION['role'] = 'teacher';
                $_SESSION['staff_id'] = $staff['Staff_id'];
                $_SESSION['name'] = $staff['Name'];
                header("Location: dashboard.php");
                exit();
            } else {
                $msg = "Invalid Staff Password. Default is 'staff123' or your contact number.";
            }
        } else {
            $msg = "Staff ID or Contact not found in Staff Master.";
        }
    } else {
        // Admin / Exam Cell Login
        $q = mysqli_query($conn, "SELECT * FROM admin WHERE User_name='$u'");
        if ($q && mysqli_num_rows($q) > 0) {
            $user_row = mysqli_fetch_assoc($q);
            if ($p === $user_row['Password'] || password_verify($p, $user_row['Password'])) {
                $_SESSION['admin'] = $u;
                $_SESSION['role'] = 'admin';
                $_SESSION['name'] = 'Administrator';
                header("Location: dashboard.php");
                exit();
            } else {
                $msg = "Invalid administrator password";
            }
        } else {
            $msg = "Invalid username or password";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Login - AutoQPGen (Vivekanand College)</title>
<style>
body {
    background-image: url('img.jpg'); 
    background-size: cover;  
    background-position: center;
    background-repeat: no-repeat;
    height: 100vh;
    margin: 0;
    font-family: Arial, sans-serif;
    background-color: #e1f5fe;
    display: flex;
    justify-content: center;
    align-items: center;
}
.login-box { 
    background: rgba(255,255,255,0.95); 
    width: 440px; 
    padding: 30px 40px; 
    border-radius: 14px; 
    box-shadow: 0 6px 20px rgba(0,0,0,0.18); 
    text-align: center; 
}
.login-box h1 { margin: 8px 0; color: #0a2a5a; font-size: 23px; }
.role-tabs {
    display: flex;
    margin-bottom: 15px;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid #0288d1;
}
.role-tab {
    flex: 1;
    padding: 9px;
    background: white;
    color: #0288d1;
    cursor: pointer;
    font-weight: bold;
    font-size: 13px;
    transition: all 0.2s;
}
.role-tab.active {
    background: #0288d1;
    color: white;
}
.login-box input[type=text], .login-box input[type=password] { 
    width: 100%; 
    padding: 12px; 
    margin: 8px 0; 
    border: 1px solid #b3e5fc; 
    border-radius: 6px; 
    box-sizing: border-box; 
    font-size: 14px; 
}
.login-box input[type=submit] { 
    width: 100%; 
    padding: 12px; 
    background: #0288d1; 
    color: white; 
    border: none; 
    border-radius: 6px; 
    cursor: pointer; 
    font-size: 16px; 
    font-weight: bold; 
    margin-top: 12px; 
}
.login-box input[type=submit]:hover { background: #0277bd; }
.error { color: #d32f2f; background: #ffebee; padding: 8px; border-radius: 5px; margin-bottom: 12px; font-size: 13px; font-weight: bold; }
.subtitle { font-size: 13px; color: #555; margin-bottom: 6px; font-weight: normal; }
.demo-hint { font-size: 11px; color: #777; margin-top: 12px; text-align: left; background: #f9fbfd; padding: 6px 10px; border-radius: 4px; border-left: 3px solid #0288d1; }
</style>
</head>
<body>
<div class="login-box">
<img src="logo.jpg" style="width:110px;height:110px;border-radius:50%;object-fit:cover;margin-bottom:6px;">
<div class="subtitle">Vivekanand College Examination Portal</div>
<h1>AutoQPGen Login</h1>

<div class="role-tabs">
    <div class="role-tab <?= $active_role === 'admin' ? 'active' : '' ?>" onclick="switchRole('admin')">🏛️ Exam Cell / Admin</div>
    <div class="role-tab <?= $active_role === 'teacher' ? 'active' : '' ?>" onclick="switchRole('teacher')">👨‍🏫 Faculty / Teacher</div>
</div>

<?php if($msg!=""){ echo "<div class='error'>$msg</div>"; } ?>

<form method="post" id="loginForm">
    <input type="hidden" name="login_role" id="login_role" value="<?= htmlspecialchars($active_role) ?>">
    
    <input type="text" name="User_name" id="userField" placeholder="Username (e.g. admin)" required><br>
    <input type="password" name="Password" placeholder="Password" required><br>
    
    <input type="submit" name="login" id="loginBtn" value="Login as Admin">
</form>

<div class="demo-hint" id="hintText">
    <b>Admin Login:</b> Username: <code>admin</code> | Password: <code>admin123</code>
</div>

</div>

<script>
function switchRole(role) {
    document.getElementById('login_role').value = role;
    var tabs = document.querySelectorAll('.role-tab');
    var userField = document.getElementById('userField');
    var loginBtn = document.getElementById('loginBtn');
    var hintText = document.getElementById('hintText');

    if (role === 'teacher') {
        tabs[0].classList.remove('active');
        tabs[1].classList.add('active');
        userField.placeholder = "Staff ID (e.g. 01) or Contact Number";
        loginBtn.value = "Login as Faculty";
        hintText.innerHTML = "<b>Faculty Login:</b> Staff ID: <code>01</code> | Default Password: <code>staff123</code> (or contact no.)";
    } else {
        tabs[1].classList.remove('active');
        tabs[0].classList.add('active');
        userField.placeholder = "Username (e.g. admin)";
        loginBtn.value = "Login as Admin";
        hintText.innerHTML = "<b>Admin Login:</b> Username: <code>admin</code> | Password: <code>admin123</code>";
    }
}
</script>
</body>
</html>
