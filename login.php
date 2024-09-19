<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <form action="login_process.php" method="POST">
        <div class="container">
            <h1>Login</h1>
            <p>Please fill in your credentials to login.</p>
            <hr>

            <label for="email"><b>E-mail:</b></label>
            <input type="email" placeholder="Enter E-mail" name="email" id="email" required>

            <label for="psw"><b>Password:</b></label> 
            <input type="password" placeholder="Enter Password" name="psw" id="psw" required>
            <hr>

            <button type="submit" class="loginbtn">Login</button>
            <p>Don't have an account? <a href="registration.php">Register here</a>.</p>
        </div>
    </form>
</body>
</html>
