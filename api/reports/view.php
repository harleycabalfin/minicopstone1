<?php
require_once '../../includes/db.php';
header("Content-Type: application/json");

$type = $_GET['type'] ?? 'daily';
$interval = match($type) {
  'weekly' => '7 DAY',
  'monthly' => '30 DAY',
  default => '1 DAY'
};

$db = getDB();
$query = "SELECT production_date, SUM(eggs_collected) AS total_eggs, SUM(damaged_eggs) AS damaged
          FROM egg_production
          WHERE production_date >= DATE_SUB(CURDATE(), INTERVAL $interval)
          GROUP BY production_date
          ORDER BY production_date DESC";

$result = $db->query($query);
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode(["success" => true, "type" => $type, "data" => $data]);
