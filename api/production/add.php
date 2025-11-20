<?php
require_once '../../includes/db.php';
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "No input received"]);
    exit;
}

$batch = $data['batch_number'] ?? '';
$collected = intval($data['eggs_collected'] ?? 0);
$damaged = intval($data['damaged_eggs'] ?? 0);
$weight = floatval($data['egg_weight_kg'] ?? 0);
$date = $data['production_date'] ?? date('Y-m-d');
$notes = $data['notes'] ?? '';
$recorded_by = intval($data['recorded_by'] ?? 0);

$db = getDB();

if ($db->connect_error) {
    echo json_encode(["success" => false, "message" => "DB connect failed: " . $db->connect_error]);
    exit;
}

if (empty($batch)) {
    echo json_encode(["success" => false, "message" => "Batch number is empty"]);
    exit;
}

$sql = "INSERT INTO egg_production 
         (batch_number, production_date, eggs_collected, damaged_eggs, egg_weight_kg, notes, recorded_by)
         VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $db->prepare($sql);

if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Prepare failed: " . $db->error]);
    exit;
}

$stmt->bind_param("ssiidsi", $batch, $date, $collected, $damaged, $weight, $notes, $recorded_by);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Record added successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Insert failed: " . $stmt->error]);
}

$stmt->close();
$db->close();
?>
