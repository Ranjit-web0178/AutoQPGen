<?php
session_start();
include 'config.php';
$msg = "";
if (isset($_POST['login'])) {
 $u = $_POST['User_name'];
 $p = $_POST['Password'];
 $q = mysqli_query($conn, "SELECT * FROM admin WHERE User_name='$u' AND Password='$p'");
 if (mysqli_num_rows($q) == 1) {
  $_SESSION['admin'] = $u;
  header("Location: dashboard.php");
  exit();
 } else {
  $msg = "Invalid username or password";
 }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Login - AutoQPGen</title>
<style>
body {
    background-image: url('img.jpg'); 
    background-size: cover;  
    background-position: center;
    background-repeat: no-repeat;
    height: 100vh;
    margin: 0;
}
body{ font-family: Arial, sans-serif; background: #e1f5fe; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
.login-box { background: white; padding: 85px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 340px; text-align: center; }
.login-box h2 { margin-bottom: 20px; color: #333; }
.login-box input[type=text], .login-box input[type=password] { width: 92%; padding: 10px; margin: 8px 0; border: 1px solid #ccc; border-radius: 5px; }
.login-box input[type=submit] { width: 100%; padding: 10px; background: #0288d1; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin-top: 10px; }
.login-box input[type=submit]:hover { background: #0277bd; }
.error { color: red; margin-bottom: 10px; font-size: 14px; }
.logo { font-size: 24px; font-weight: bold; color: #0288d1; margin-bottom: 2px; }
.subtitle { font-size: 13px; color: #666; margin-bottom: 15px; }
.login-box{
   background: rgba(255,255,255,0.9); 
   width: 600px;
   padding: 30px;
   margin: 100px auto;
   border-radius: 10px;
}
</style>
</head>
<body>
<div class="login-box">
<img src="logo.jpg"style="width:150px;height:150px;border-radius:80%;margin-bottom:9px;">
<h1><div class="subtitle">(Automatic Question Paper Generation)</div></h1>
<h1>Admin Login</h1>
<?php if($msg!=""){ echo "<div class='error'>$msg</div>"; } ?>
<form method="post">
<input type="text" name="User_name" placeholder="User Name" required><br>
<input type="password" name="Password" placeholder="Password" required><br>
<input type="submit" name="login" value="Login">
</form>
</div>
</body>
</html>