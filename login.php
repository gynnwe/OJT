<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Login Page */
        .login {
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f3f3f3;
        }

        .container-login {
            background-color: #fff;
            border-radius: 32px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            padding: 30px 20px;
            width: 360px;
            text-align: center;
        }

        .container-login h1 {
            font-size: 24px;
            font-weight: bolder;
            margin-bottom: 20px;
            color: #090E1D;
            text-align: left;
        }

        .container-login p {
            font-size: 14px;
            margin-bottom: 20px;
            color: #555;
        }

        .container-login label {
            display: block;
            text-align: left;
            margin-bottom: 8px;
            font-weight: bold;
            color: #090E1D;
        }

        .container-login input[type="email"],
        .container-login input[type="password"],
        .container-login input[type="text"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 10px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        button.loginbtn {
            margin: 10px 0;
            width: 100%;
            padding: 12px;
            background-color: #a50000;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        button.loginbtn:hover {
            background-color: #8b0000;
        }

        .container-login a {
            font-weight: bold;
            color: #090E1D;
            text-decoration: underline;
        }

        .container-login a:hover {
            text-decoration: underline;
        }

        .forgot-password {
            margin-top: 10px;
            text-align: left;
        }

        .password-container {
            position: relative;
            width: 100%;
        }

        .password-container input[type="password"],
        .password-container input[type="text"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 10px;
            border: 1px solid #ccc;
            font-size: 14px;
            padding-right: 40px; /* Add padding to make space for the eye icon */
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            color: #666;
        }

        .container-login .logo {
            width: 152px;
            margin-bottom: 20px;
        }
    </style>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.5.0/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="login">
    <form action="login_process.php" method="POST">
        <div class="container-login">
            <img src="assets/usep-logo.png" alt="USeP Logo" class="logo">
            <h1>Sign In</h1>

            <label for="email"><b>Email Address</b></label>
            <input type="email" placeholder="youremail@usep.edu.ph" name="email" id="email" required>

            <label for="psw"><b>Password</b></label>
            <div class="password-container">
                <input type="password" placeholder="••••••••••" name="psw" id="psw" required>
                <i class="bi bi-eye password-toggle" id="togglePassword"></i>
            </div>

            <button type="submit" class="loginbtn">Sign In</button>
            <p class="forgot-password"><a href="#">Forgot password?</a></p>
        </div>
    </form>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordField = document.getElementById('psw');

        togglePassword.addEventListener('click', function() {
            // Toggle the type attribute of the password field
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);

            // Toggle the eye icon classes between bi-eye and bi-eye-slash
            if (type === 'password') {
                this.classList.remove('bi-eye-slash');
                this.classList.add('bi-eye');
            } else {
                this.classList.remove('bi-eye');
                this.classList.add('bi-eye-slash');
            }
        });
    </script>
</body>
</html>
