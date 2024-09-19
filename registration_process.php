<?php
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "pms";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $user = $_POST['username'];
    $pass = $_POST['psw'];
    $pass_repeat = $_POST['psw-repeat'];

    if ($pass !== $pass_repeat) {
        die("Passwords do not match.");
    }

    $hashed_password = password_hash($pass, PASSWORD_DEFAULT);

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "INSERT INTO account (email, username, password) VALUES (:email, :username, :password)";
        $stmt = $conn->prepare($sql);

        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':username', $user);
        $stmt->bindParam(':password', $hashed_password);

        $stmt->execute();

        echo "Registration successful!";
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }

    $conn = null;
}
?>
