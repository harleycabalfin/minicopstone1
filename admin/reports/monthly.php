<?php
require_once '../../includes/db.php';
requireUser();
$db = getDB();

$month = $_GET['month'] ?? date('Y-m');
$start = $month . "-01";
$end = date("Y-m-t", strtotime($month));

$stmtEggs = $db->prepare("SELECT SUM(eggs_collected) as eggs, SUM(egg_weight_kg) as weight FROM egg_production WHERE production_date BETWEEN ? AND ?");
$stmtEggs->bind_param("ss", $start, $end);
$stmtEggs->execute();
$eggData = $stmtEggs->get_result()->fetch_assoc();

$stmtSales = $db->prepare("SELECT SUM(total_amount) as total FROM sales WHERE sale_date BETWEEN ? AND ?");
$stmtSales->bind_param("ss", $start, $end);
$stmtSales->execute();
$salesData = $stmtSales->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Monthly Report - <?php echo SITE_NAME; ?></title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="bg-gray-800 text-white p-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold">Monthly Report</h1>
    <a href="../dashboard.php" class="hover:text-gray-300">Dashboard</a>
</div>

<div class="container mx-auto p-6 max-w-3xl">
    <form method="GET" class="flex gap-3 mb-6">
        <input type="month" name="month" value="<?php echo $month; ?>" class="border px-3 py-2 rounded-lg">
        <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg font-semibold">View</button>
    </form>

    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Report for <?php echo date("F Y", strtotime($month)); ?></h2>
        <p><strong>Total Eggs Collected:</strong> <?php echo $eggData['eggs'] ?? 0; ?></p>
        <p><strong>Total Weight:</strong> <?php echo number_format($eggData['weight'] ?? 0, 2); ?> kg</p>
        <p><strong>Total Sales:</strong> ₱<?php echo number_format($salesData['total'] ?? 0, 2); ?></p>
    </div>
</div>
</body>
</html>
