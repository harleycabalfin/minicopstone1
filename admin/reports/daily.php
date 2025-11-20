<?php
require_once '../../includes/db.php';
requireUser();
$db = getDB();

$date = $_GET['date'] ?? date('Y-m-d');

// Fetch egg production
$stmtProd = $db->prepare("SELECT batch_number, eggs_collected, damaged_eggs, egg_weight_kg FROM egg_production WHERE production_date = ?");
$stmtProd->bind_param("s", $date);
$stmtProd->execute();
$production = $stmtProd->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch sales
$stmtSales = $db->prepare("SELECT sale_type, quantity, total_amount, customer_name FROM sales WHERE sale_date = ?");
$stmtSales->bind_param("s", $date);
$stmtSales->execute();
$sales = $stmtSales->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Report - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="bg-gray-800 text-white p-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold"><?php echo SITE_NAME; ?> - Daily Report</h1>
    <a href="../dashboard.php" class="hover:text-gray-300">Dashboard</a>
</div>

<div class="container mx-auto p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold text-gray-700">Report for <?php echo htmlspecialchars($date); ?></h2>
        <form method="GET" class="flex gap-3">
            <input type="date" name="date" value="<?php echo $date; ?>" class="border px-3 py-2 rounded-lg focus:ring-2 focus:ring-orange-500">
            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg font-semibold">View</button>
        </form>
    </div>

    <!-- Egg Production -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-3">Egg Production</h3>
        <?php if (count($production) > 0): ?>
            <table class="min-w-full text-sm border border-gray-200">
                <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-2 text-left">Batch</th>
                        <th class="px-4 py-2 text-left">Eggs Collected</th>
                        <th class="px-4 py-2 text-left">Damaged</th>
                        <th class="px-4 py-2 text-left">Weight (kg)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($production as $row): ?>
                        <tr class="border-t">
                            <td class="px-4 py-2"><?php echo $row['batch_number']; ?></td>
                            <td class="px-4 py-2"><?php echo $row['eggs_collected']; ?></td>
                            <td class="px-4 py-2 text-red-600"><?php echo $row['damaged_eggs']; ?></td>
                            <td class="px-4 py-2"><?php echo number_format($row['egg_weight_kg'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-gray-500">No egg production recorded for this day.</p>
        <?php endif; ?>
    </div>

    <!-- Sales -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-3">Sales</h3>
        <?php if (count($sales) > 0): ?>
            <table class="min-w-full text-sm border border-gray-200">
                <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-2 text-left">Type</th>
                        <th class="px-4 py-2 text-left">Quantity</th>
                        <th class="px-4 py-2 text-left">Customer</th>
                        <th class="px-4 py-2 text-left">Total (₱)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $row): ?>
                        <tr class="border-t">
                            <td class="px-4 py-2 capitalize"><?php echo $row['sale_type']; ?></td>
                            <td class="px-4 py-2"><?php echo $row['quantity']; ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                            <td class="px-4 py-2 font-semibold text-green-700">₱<?php echo number_format($row['total_amount'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-gray-500">No sales recorded for this day.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
