<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f3f3f3;
        }

        .container {
            background-color: #fff;
            border-radius: 32px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            padding: 30px 20px;
            width: 360px;
            text-align: center;
        }

        .container .logo {
            width: 152px;
            margin-bottom: 20px;
        }

        .container h1 {
    font-size: 24px;
    font-weight: bolder;
    margin-bottom: 20px;
    color: #090E1D;
    text-align: left;
}
        .container p {
    font-size: 14px;
    margin-bottom: 20px;
    color: #555;
    text-align: left;
}

        .container input[type="email"],
        .container input[type="password"],
        .container input[type="text"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 10px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        .password-container {
            position: relative;
            width: 100%;
        }

        .password-container input {
            padding-right: 40px;
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

        button.registerbtn {
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

        button.registerbtn:hover {
            background-color: #8b0000;
        }

        .container a {
            font-weight: bold;
            color: #090E1D;
            text-decoration: underline;
        }

        .container a:hover {
            text-decoration: underline;
        }

        hr {
            border: 1px solid #e0e0e0;
            margin: 20px 0;
        }
    </style>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.5.0/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>
    <form action="registration_process.php" method="POST">
        <div class="container">
            <img src="assets/usep-logo.png" alt="Logo" class="logo">
            <h1>Register</h1>
            <p>Please fill out this form to create an account.</p>
            <hr>

            <input type="email" placeholder="Enter e-mail" name="email" id="email" required>
            <input type="text" placeholder="Enter your first name" name="firstname" id="firstname" required>
            <input type="text" placeholder="Enter your last name" name="lastname" id="lastname" required>
            <input type="text" placeholder="Enter username" name="username" id="username" required>

            <div class="password-container">
                <input type="password" placeholder="Enter Password" name="psw" id="psw" required>
                <i class="bi bi-eye password-toggle" id="togglePassword"></i>
            </div>

            <div class="password-container">
                <input type="password" placeholder="Repeat Password" name="psw-repeat" id="psw-repeat" required>
                <i class="bi bi-eye password-toggle" id="toggleRepeatPassword"></i>
            </div>
            <hr>

            <p>By creating an account, you agree to our <a href="#">Terms and Privacy</a>.</p>
            <button type="submit" class="registerbtn">Register</button>
            <p>Already have an account? <a href="login.php">Sign in here</a>.</p>
        </div>
    </form>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordField = document.getElementById('psw');
        const toggleRepeatPassword = document.getElementById('toggleRepeatPassword');
        const repeatPasswordField = document.getElementById('psw-repeat');

        togglePassword.addEventListener('click', function() {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.classList.toggle('bi-eye-slash');
            this.classList.toggle('bi-eye');
        });

        toggleRepeatPassword.addEventListener('click', function() {
            const type = repeatPasswordField.getAttribute('type') === 'password' ? 'text' : 'password';
            repeatPasswordField.setAttribute('type', type);
            this.classList.toggle('bi-eye-slash');
            this.classList.toggle('bi-eye');
        });
    </script>
</body>
</html>
