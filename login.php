<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="login">
    <form action="login_process.php" method="POST">
        <div class="container-login">
            <img src="assets/usep-logo.png" alt="USeP Logo" class="logo">
            <h1>Sign In</h1>

            <label for="email"><b>Email Address</b></label>
            <input type="email" placeholder="youremail@usep.edu.ph" name="email" id="email" required>

            <label for="psw"><b>Password</b></label>
            <input type="password" placeholder="••••••••••••" name="psw" id="psw" required>

            <button type="submit" class="loginbtn">Sign In</button>
            <p class="forgot-password"><a href="#">Forgot password?</a></p>
        </div>
    </form>
</body>
</html>
