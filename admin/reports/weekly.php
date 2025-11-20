<?php
require_once '../../includes/db.php';
requireUser();
$db = getDB();

$start = $_GET['start'] ?? date('Y-m-d', strtotime('monday this week'));
$end = $_GET['end'] ?? date('Y-m-d', strtotime('sunday this week'));

// Get totals
$stmt1 = $db->prepare("SELECT SUM(eggs_collected) as total_eggs, SUM(damaged_eggs) as damaged, SUM(egg_weight_kg) as weight FROM egg_production WHERE production_date BETWEEN ? AND ?");
$stmt1->bind_param("ss", $start, $end);
$stmt1->execute();
$egg_data = $stmt1->get_result()->fetch_assoc();

$stmt2 = $db->prepare("SELECT SUM(total_amount) as total_sales FROM sales WHERE sale_date BETWEEN ? AND ?");
$stmt2->bind_param("ss", $start, $end);
$stmt2->execute();
$sales_data = $stmt2->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Weekly Report - <?php echo SITE_NAME; ?></title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="bg-gray-800 text-white p-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold">Weekly Summary</h1>
    <a href="../dashboard.php" class="hover:text-gray-300">Dashboard</a>
</div>

<div class="container mx-auto p-6 max-w-3xl">
    <form method="GET" class="flex gap-3 mb-6">
        <input type="date" name="start" value="<?php echo $start; ?>" class="border px-3 py-2 rounded-lg">
        <input type="date" name="end" value="<?php echo $end; ?>" class="border px-3 py-2 rounded-lg">
        <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg font-semibold">View</button>
    </form>

    <div class="bg-white rounded-lg shadow-md p-6 space-y-4">
        <h2 class="text-lg font-semibold text-gray-700">Summary: <?php echo "$start to $end"; ?></h2>
        <p><span class="font-semibold">Total Eggs Collected:</span> <?php echo $egg_data['total_eggs'] ?? 0; ?></p>
        <p><span class="font-semibold">Damaged Eggs:</span> <?php echo $egg_data['damaged'] ?? 0; ?></p>
        <p><span class="font-semibold">Total Weight:</span> <?php echo number_format($egg_data['weight'] ?? 0, 2); ?> kg</p>
        <p><span class="font-semibold">Total Sales:</span> ₱<?php echo number_format($sales_data['total_sales'] ?? 0, 2); ?></p>
    </div>
</div>
</body>
</html>
