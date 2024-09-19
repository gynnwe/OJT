<?php
$servername = "localhost";
$username = "root"; 
$password = ""; 

try {
    $conn = new PDO("mysql:host=$servername", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "CREATE DATABASE IF NOT EXISTS pms";
    $conn->exec($sql);
    echo "Database created successfully<br>";

    $conn->exec("USE pms");

    $sql = "CREATE TABLE IF NOT EXISTS account (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(50) NOT NULL,
        username VARCHAR(30) NOT NULL,
        password VARCHAR(255) NOT NULL
    )";

    $conn->exec($sql);
    echo "Table account created successfully";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$conn = null;
?>
