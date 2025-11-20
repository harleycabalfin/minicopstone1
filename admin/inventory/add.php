<?php
require_once '../../includes/db.php';
requireUser();

$db = getDB();
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feed_type = trim($_POST['feed_type']);
    $quantity_kg = floatval($_POST['quantity_kg']);
    $unit_price = floatval($_POST['unit_price']);
    $supplier = trim($_POST['supplier']);
    $purchase_date = $_POST['purchase_date'];
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $reorder_level = floatval($_POST['reorder_level']);

    // Validate
    if (empty($feed_type) || $quantity_kg <= 0 || $unit_price <= 0 || empty($purchase_date)) {
        $error = 'Please fill in all required fields with valid values.';
    } else {
        // Insert new feed stock
        $stmt = $db->prepare("INSERT INTO feed_inventory (feed_type, quantity_kg, unit_price, supplier, purchase_date, expiry_date, reorder_level, added_by) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sddsssdi", $feed_type, $quantity_kg, $unit_price, $supplier, $purchase_date, $expiry_date, $reorder_level, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            logAction($_SESSION['user_id'], "Added new feed stock", "feed_inventory", $new_id);
            $success = 'Feed stock added successfully!';
            
            // Clear form
            $_POST = array();
        } else {
            $error = 'Failed to add feed stock: ' . $db->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Feed Stock - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

    <!-- Main Content -->
    <div class="container mx-auto p-6 max-w-3xl">
        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo $success; ?>
                <a href="index.php" class="font-semibold underline ml-2">View inventory</a>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-700">Add New Feed Stock</h3>
            </div>
            
            <form method="POST" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Feed Type -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Feed Type *</label>
                        <input type="text" name="feed_type" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g., Starter Feed, Grower Feed, Layer Feed"
                               value="<?php echo htmlspecialchars($_POST['feed_type'] ?? ''); ?>">
                    </div>

                    <!-- Quantity -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quantity (kg) *</label>
                        <input type="number" name="quantity_kg" required min="0.01" step="0.01" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="0.00"
                               value="<?php echo htmlspecialchars($_POST['quantity_kg'] ?? ''); ?>">
                    </div>

                    <!-- Unit Price -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Unit Price (₱/kg) *</label>
                        <input type="number" name="unit_price" required min="0.01" step="0.01" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="0.00"
                               value="<?php echo htmlspecialchars($_POST['unit_price'] ?? ''); ?>">
                    </div>

                    <!-- Supplier -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Supplier</label>
                        <input type="text" name="supplier" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Supplier name"
                               value="<?php echo htmlspecialchars($_POST['supplier'] ?? ''); ?>">
                    </div>

                    <!-- Purchase Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Purchase Date *</label>
                        <input type="date" name="purchase_date" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               value="<?php echo htmlspecialchars($_POST['purchase_date'] ?? date('Y-m-d')); ?>">
                    </div>

                    <!-- Expiry Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Date</label>
                        <input type="date" name="expiry_date" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               value="<?php echo htmlspecialchars($_POST['expiry_date'] ?? ''); ?>">
                        <p class="text-xs text-gray-500 mt-1">Optional - Leave blank if no expiry</p>
                    </div>

                    <!-- Reorder Level -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reorder Level (kg) *</label>
                        <input type="number" name="reorder_level" required min="0" step="0.01" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="50.00"
                               value="<?php echo htmlspecialchars($_POST['reorder_level'] ?? '50.00'); ?>">
                        <p class="text-xs text-gray-500 mt-1">Alert when stock reaches this level</p>
                    </div>
                </div>

                <!-- Total Value Display -->
                <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                    <p class="text-sm text-gray-600">Total Purchase Value:</p>
                    <p class="text-2xl font-bold text-blue-600" id="total_value">₱0.00</p>
                </div>

                <!-- Submit Buttons -->
                <div class="flex gap-3 mt-6">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold">
                        Add Feed Stock
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Calculate total value dynamically
        function calculateTotal() {
            const quantity = parseFloat(document.querySelector('input[name="quantity_kg"]').value) || 0;
            const price = parseFloat(document.querySelector('input[name="unit_price"]').value) || 0;
            const total = quantity * price;
            document.getElementById('total_value').textContent = '₱' + total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }

        document.querySelector('input[name="quantity_kg"]').addEventListener('input', calculateTotal);
        document.querySelector('input[name="unit_price"]').addEventListener('input', calculateTotal);
    </script>
</body>
</html>