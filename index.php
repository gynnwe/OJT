<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-image: url('assets/images/dashboard_background.jpg');
            background-size: cover;
            background-position: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .main-container {
            position: relative;
            width: 768px;
            height: 407px;
            background-color: #ffffff;
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            display: flex;
        }

        .maroon-square {
            position: absolute;
            width: 408px;
            height: 407px;
            background-color: #632121;
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .content {
            padding: 20px;
            color: white;
            text-align: left;
            width: 100%;
            height: 100%;
        }

        .logos {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 15px;
            align-items: flex-end;
        }

        .logos img {
            width: auto;
        }

        .logos img:nth-child(2) {
            align-self: flex-end;
        }

        .content h1 {
            margin-top: 30px;
            margin-left: 30px;
            font-size: 40px;
            font-weight: 800;
            letter-spacing: 2%;
            line-height: 39px;
        }

        .sign-in-form {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            width: 320px;
        }

        .form-container {
            width: 100%;
            text-align: left;
        }

        label {
            display: block;
            margin-top: 20px;
            font-weight: bold;
            color: #333;
        }

        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 20px;
            background-color: #a50000;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #8b0000;
        }

        .password-container {
            position: relative;
            width: 100%;
        }

        .password-container input[type="password"],
        .password-container input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 14px;
            padding-right: 40px; /* Add padding to make space for the eye icon */
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
        }

        .sign-in-title {
            font-size: 35px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
    </style>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.5.0/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>
    <div class="main-container">
        <div class="maroon-square">
            <div class="content">
                <div class="logos">
                    <img src="assets/images/usep-logo.png" alt="USeP Logo" width="165" height="164">
                    <img src="assets/images/cic.png" alt="CIC Logo" width="120" height="136">
                </div>
                <h1>ICT Equipment<br>Monitoring<br>System</h1>
            </div>
        </div>
        <div class="sign-in-form">
            <form action="login_process.php" method="POST" class="form-container">
                <div class="sign-in-title">Sign In</div>
                
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="youremail@usep.edu.ph" required>

                <label for="password">Password</label>
                <div class="password-container">
                <input type="password" placeholder="••••••••••" name="psw" id="psw" required>
                    <i class="bi bi-eye password-toggle" id="togglePassword"></i>
                </div>

                <button type="submit">Sign In</button>
            </form>
        </div>
    </div>

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
