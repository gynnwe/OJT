<?php
include 'conn.php';

// Check if equipment type ID was provided
if (!isset($_GET['equip_type_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Equipment type ID is required']);
    exit;
}

try {
    $equipTypeId = $_GET['equip_type_id'];
    
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Query to get count of serviceable equipment
    $query = "SELECT COUNT(*) as count 
              FROM equipment 
              WHERE equip_type_id = :equip_type_id 
              AND status = 'Serviceable' 
              AND deleted_id = 0";
              
    $stmt = $conn->prepare($query);
    $stmt->execute(['equip_type_id' => $equipTypeId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['count' => $result['count']]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}