<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ictmms";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $jo_number = $_POST['jo_number'];
    $equipment_type = $_POST['#'];
    $equipment_name = $_POST['remarks'];
    $serial_num = $_POST['#'];
    $model_name = $_POST['date_purchased'];

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        //To add code for db tb

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }

    $conn = null;
}
?>
