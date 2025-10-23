<?php
include 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = isset($_POST['type']) ? $_POST['type'] : '';
    $value = isset($_POST['value']) ? $_POST['value'] : '';
    
    if (empty($type) || empty($value)) {
        echo json_encode(['error' => 'Invalid request']);
        exit;
    }
    
    $response = ['available' => true];
    
    if ($type === 'username') {
        $check = mysqli_query($conn, "SELECT id FROM `users` WHERE name = '" . mysqli_real_escape_string($conn, $value) . "'") or die('query failed');
        $response['available'] = mysqli_num_rows($check) === 0;
    } 
    elseif ($type === 'email') {
        $check = mysqli_query($conn, "SELECT id FROM `users` WHERE email = '" . mysqli_real_escape_string($conn, $value) . "'") or die('query failed');
        $response['available'] = mysqli_num_rows($check) === 0;
    }
    
    echo json_encode($response);
    exit;
}

echo json_encode(['error' => 'Invalid request']);
?>
