<?php
require_once '../../includes/db.php';
requireUser();
$db = getDB();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { die("Invalid invoice."); }

$stmt = $db->prepare("SELECT * FROM sales WHERE sale_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$sale = $stmt->get_result()->fetch_assoc();

if (!$sale) {
    die("Sale not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?php echo $sale['sale_id']; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-2xl mx-auto bg-white shadow-lg rounded-lg p-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-orange-600"><?php echo SITE_NAME; ?> Invoice</h1>
            <p class="text-gray-600">Date: <?php echo htmlspecialchars($sale['sale_date']); ?></p>
        </div>

        <div class="mb-6">
            <p class="font-semibold">Customer:</p>
            <p><?php echo htmlspecialchars($sale['customer_name'] ?? 'N/A'); ?></p>
            <p><?php echo htmlspecialchars($sale['customer_phone'] ?? ''); ?></p>
        </div>

        <table class="min-w-full text-sm border border-gray-200 mb-6">
            <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                <tr>
                    <th class="px-4 py-2 text-left">Item</th>
                    <th class="px-4 py-2 text-left">Batch</th>
                    <th class="px-4 py-2 text-left">Quantity</th>
                    <th class="px-4 py-2 text-left">Unit Price</th>
                    <th class="px-4 py-2 text-left">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-t">
                    <td class="px-4 py-2 capitalize"><?php echo htmlspecialchars($sale['sale_type']); ?></td>
                    <td class="px-4 py-2"><?php echo htmlspecialchars($sale['batch_number']); ?></td>
                    <td class="px-4 py-2"><?php echo $sale['quantity']; ?></td>
                    <td class="px-4 py-2">₱<?php echo number_format($sale['unit_price'], 2); ?></td>
                    <td class="px-4 py-2 font-semibold text-green-700">₱<?php echo number_format($sale['total_amount'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="flex justify-between items-center">
            <p class="text-gray-700">Payment Status: 
                <span class="font-semibold capitalize"><?php echo htmlspecialchars($sale['payment_status']); ?></span>
            </p>
            <button onclick="window.print()" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg font-semibold">
                Print Invoice
            </button>
        </div>
    </div>
</body>
</html>
