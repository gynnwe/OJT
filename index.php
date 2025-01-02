<?php
session_start();

include 'conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $pass = $_POST['psw'];

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "SELECT * FROM user WHERE email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['admin_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['firstname'] = $user['firstname'];
            $_SESSION['lastname'] = $user['lastname'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            header("location: dashboard.php");
            exit;
        } 

        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                const emailField = document.getElementById('email');
                const passwordField = document.getElementById('psw');
                const form = document.querySelector('.form-container');
                
                emailField.classList.add('quake', 'error-field');
                passwordField.classList.add('quake', 'error-field');
                
                const alertBox = document.createElement('div');
                alertBox.textContent = 'Invalid credentials';
                alertBox.style.position = 'absolute';
                alertBox.style.bottom = '60px';
                alertBox.style.left = '50%';
                alertBox.style.transform = 'translateX(-50%)';
                alertBox.style.backgroundColor = '#ff0000';
                alertBox.style.color = '#fff';
                alertBox.style.opacity = '0.5';
                alertBox.style.padding = '5px 10px';
                alertBox.style.fontSize = '8px';
                alertBox.style.borderRadius = '5px';
                alertBox.style.zIndex = '1000';
                form.appendChild(alertBox);

                setTimeout(() => {
                    alertBox.style.transition = 'opacity 1s';
                    alertBox.style.opacity = '0';
                    setTimeout(() => alertBox.remove(), 1000);
                }, 3000);

                setTimeout(() => {
                    emailField.classList.remove('quake', 'error-field');
                    passwordField.classList.remove('quake', 'error-field');
                }, 500);
            });
        </script>";

    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }

    $conn = null;
}
?>

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
            background-image: url('assets/images/login_bg.png');
            background-size: cover;
            background-position: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .quake {
            animation: quake 0.05s ease-in-out 5;
        }

        .error-field {
            border-color: red !important;
        }

        @keyframes quake {
            0%, 100% {
                transform: translateX(0);
            }
            20%, 60% {
                transform: translateX(-5px);
            }
            40%, 80% {
                transform: translateX(5px);
            }
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
            border: 2px solid #ccc;
            border-radius: 24px !important;
            font-size: 14px !important;
            background-color: white;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 45px;
            background-color: #a50000;
            color: white;
            border: none;
            border-radius: 24px;
            font-size: 14px;
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
            border: 2px solid #ccc;
            border-radius: 24px !important;
            font-size: 14px;
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            margin-top: 2px;
            opacity: 50%;
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
            <form action="index.php" method="POST" class="form-container">
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
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });
    </script>
</body>
</html>
