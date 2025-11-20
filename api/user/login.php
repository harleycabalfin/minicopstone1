<?php
require_once '../../includes/db.php';
header('Content-Type: application/json; charset=UTF-8');

// read raw JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// debug output if decoding fails
if (!$data) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid or empty JSON',
        'raw' => $json
    ]);
    exit;
}

$username = trim($data['username'] ?? '');
$password = trim($data['password'] ?? '');

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Username or password missing']);
    exit;
}

$response = login($username, $password);
echo json_encode($response);
?>
