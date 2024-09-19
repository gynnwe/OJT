<?php
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "pms";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $pass = $_POST['psw'];

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "SELECT * FROM account WHERE email = :email";
        $stmt = $conn->prepare($sql);

        $stmt->bindParam(':email', $email);

        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($pass, $user['password'])) {
                echo "Login successful! Welcome, " . htmlspecialchars($user['username']) . ".";
            } else {
                echo "Incorrect password. Please try again.";
            }
        } else {
            echo "No account found with that e-mail.";
        }
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }

    $conn = null;
}
?>
