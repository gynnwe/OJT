<?php
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "ictmms";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
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

        $sql = "INSERT INTO user (email, firstname, lastname, username, password) VALUES (:email, :firstname, :lastname, :username, :password)";
        $stmt = $conn->prepare($sql);

        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':firstname', $firstname);
        $stmt->bindParam(':lastname', $lastname);
        $stmt->bindParam(':username', $user);
        $stmt->bindParam(':password', $hashed_password);

        $stmt->execute();

        echo "Registration successful! <br>";
        echo "<p>You can go back to the <a href='dashboard.php'>dashboard</a>.</p>";
         
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }

    $conn = null;
}
?>
