<?php
require_once '../../includes/db.php';
requireUser();
$db = getDB();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sale_type = $_POST['sale_type'];
    $batch_number = trim($_POST['batch_number']);
    $quantity = intval($_POST['quantity']);
    $unit_price = floatval($_POST['unit_price']);
    $total_amount = $quantity * $unit_price;
    $customer_name = trim($_POST['customer_name']);
    $customer_phone = trim($_POST['customer_phone']);
    $sale_date = $_POST['sale_date'];
    $payment_status = $_POST['payment_status'];
    $notes = trim($_POST['notes']);

    if ($quantity <= 0 || $unit_price <= 0) {
        $error = "Please enter valid quantity and price.";
    } else {
        $stmt = $db->prepare("INSERT INTO sales (sale_type, batch_number, quantity, unit_price, total_amount, customer_name, customer_phone, sale_date, payment_status, notes, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiddsssssi", $sale_type, $batch_number, $quantity, $unit_price, $total_amount, $customer_name, $customer_phone, $sale_date, $payment_status, $notes, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $success = "Sale recorded successfully.";
            logAction($_SESSION['user_id'], "Added new sale ($sale_type)", "sales", $db->insert_id);
        } else {
            $error = "Error: " . $db->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Sale - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="bg-gray-800 text-white p-4 flex justify-between items-center">
        <h1 class="text-2xl font-bold">Add Sale</h1>
        <a href="index.php" class="hover:text-gray-300">Back</a>
    </div>

    <div class="container mx-auto p-6 max-w-xl">
        <?php if ($success): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6"><?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6"><?php echo $error; ?></div><?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6">
            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Sale Type *</label>
                    <select name="sale_type" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500">
                        <option value="">Select Type</option>
                        <option value="eggs">Eggs</option>
                        <option value="chickens">Chickens</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Batch Number</label>
                    <input type="text" name="batch_number" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">Quantity *</label>
                        <input type="number" name="quantity" required min="1" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">Unit Price (₱) *</label>
                        <input type="number" name="unit_price" required step="0.01" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500">
                    </div>
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Customer Name</label>
                    <input type="text" name="customer_name" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Customer Phone</label>
                    <input type="text" name="customer_phone" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Sale Date *</label>
                    <input type="date" name="sale_date" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Payment Status *</label>
                    <select name="payment_status" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500">
                        <option value="paid">Paid</option>
                        <option value="pending">Pending</option>
                        <option value="partial">Partial</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Notes</label>
                    <textarea name="notes" rows="3" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500"></textarea>
                </div>
                <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white py-3 rounded-lg font-semibold">Save Sale</button>
            </form>
        </div>
    </div>
</body>
</html>
