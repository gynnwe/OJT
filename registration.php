<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register and Login</title>
</head>
<body>
    <form action="registration_process.php" method="POST">
        <div class="container">
            <h1>Register</h1>
            <p>Please fill out this form to create an account.</p>
            <hr>

            <label for="email"><b>E-mail:</b></label>
            <input type="email" placeholder="Enter e-mail" name="email" id="email" required>

            <label for="username"><b>Username:</b></label>
            <input type="text" placeholder="Enter username" name="username" id="username" required>

            <label for="psw"><b>Password:</b></label> 
            <input type="password" placeholder="Enter Password" name="psw" id="psw" required>

            <label for="psw-repeat"><b>Repeat Password:</b></label>
            <input type="password" placeholder="Repeat Password" name="psw-repeat" id="psw-repeat" required>
            <hr>

            <p>By creating an account, you agree to our <a href="#">Terms and Privacy</a>.</p>
            <button type="submit" class="registerbtn">Register</button>
            <p>Already have an account? <a href="login.php">Sign in here</a>.</p>
        </div>
    </form>
</body>
</html>
