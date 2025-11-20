<?php
require_once '../../includes/db.php';

header("Content-Type: application/json");

$db = getDB();
$result = $db->query("SELECT * FROM egg_production ORDER BY production_date DESC");

$productions = [];
while ($row = $result->fetch_assoc()) {
    $productions[] = $row;
}

echo json_encode(["success" => true, "data" => $productions]);
