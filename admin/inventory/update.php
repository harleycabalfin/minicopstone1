<?php
require_once '../../includes/db.php';
requireUser();

$db = getDB();
$success = '';
$error = '';

// Get feed ID if provided
$feed_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$selected_feed = null;

if ($feed_id > 0) {
    $stmt = $db->prepare("SELECT * FROM feed_inventory WHERE feed_id = ?");
    $stmt->bind_param("i", $feed_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $selected_feed = $result->fetch_assoc();
    }
}

// Get all feeds for dropdown
$all_feeds = $db->query("SELECT feed_id, feed_type, quantity_kg FROM feed_inventory ORDER BY feed_type");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feed_id = intval($_POST['feed_id']);
    $update_type = $_POST['update_type'];
    $quantity = floatval($_POST['quantity']);
    $notes = trim($_POST['notes']);

    if ($feed_id <= 0 || $quantity <= 0) {
        $error = 'Please select a feed and enter a valid quantity.';
    } else {
        $stmt = $db->prepare("SELECT feed_type, quantity_kg FROM feed_inventory WHERE feed_id = ?");
        $stmt->bind_param("i", $feed_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = 'Feed not found.';
        } else {
            $feed_data = $result->fetch_assoc();
            $current_qty = $feed_data['quantity_kg'];

            if ($update_type === 'add') {
                $new_qty = $current_qty + $quantity;
            } else {
                if ($quantity > $current_qty) {
                    $error = 'Cannot deduct more than current stock (' . number_format($current_qty, 2) . ' kg).';
                } else {
                    $new_qty = $current_qty - $quantity;
                }
            }

            if (!$error) {
                $stmt = $db->prepare("UPDATE feed_inventory SET quantity_kg = ? WHERE feed_id = ?");
                $stmt->bind_param("di", $new_qty, $feed_id);
                if ($stmt->execute()) {
                    $action = $update_type === 'add' ? 'Added stock' : 'Deducted stock';
                    logAction($_SESSION['user_id'], "$action - " . $feed_data['feed_type'] . " ($quantity kg)", "feed_inventory", $feed_id);
                    $success = ucfirst($update_type) . "ed " . number_format($quantity, 2) . " kg successfully! New quantity: " . number_format($new_qty, 2) . " kg.";
                    $_POST = [];
                    $all_feeds = $db->query("SELECT feed_id, feed_type, quantity_kg FROM feed_inventory ORDER BY feed_type");
                } else {
                    $error = 'Failed to update stock: ' . $db->error;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Stock - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<!-- Header -->
<div class="bg-gray-800 text-white p-4">
    <div class="container mx-auto flex justify-between items-center">
        <h1 class="text-2xl font-bold"><?php echo SITE_NAME; ?> - Update Stock</h1>
        <div class="flex gap-4">
            <a href="index.php" class="hover:text-gray-300">Back to Inventory</a>
            <a href="../dashboard.php" class="hover:text-gray-300">Dashboard</a>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container mx-auto p-6 max-w-2xl">

    <!-- Success / Error Alerts -->
    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Form Card -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-orange-50 px-6 py-4 border-b border-orange-200">
            <h3 class="text-lg font-semibold text-orange-900">Update Feed Stock</h3>
            <p class="text-sm text-orange-700 mt-1">Add new stock or deduct consumed/damaged stock</p>
        </div>

        <form method="POST" class="p-6 space-y-6">
            <!-- Feed Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Feed *</label>
                <select name="feed_id" id="feed_select" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    <option value="">Choose feed type...</option>
                    <?php while ($feed = $all_feeds->fetch_assoc()): ?>
                        <option value="<?php echo $feed['feed_id']; ?>" data-current="<?php echo $feed['quantity_kg']; ?>" <?php echo ($selected_feed && $selected_feed['feed_id'] == $feed['feed_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($feed['feed_type']); ?> - Current: <?php echo number_format($feed['quantity_kg'], 2); ?> kg
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Current Stock Display -->
            <div id="current_stock_display" class="hidden p-4 bg-blue-50 rounded-lg border border-blue-200">
                <p class="text-sm text-gray-600">Current Stock:</p>
                <p class="text-2xl font-bold text-blue-600" id="current_stock">0.00 kg</p>
            </div>

            <!-- Update Type -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Update Type *</label>
                <div class="grid grid-cols-2 gap-4">
                    <label class="relative flex cursor-pointer p-4 border-2 border-gray-300 rounded-lg hover:border-green-500 transition">
                        <input type="radio" name="update_type" value="add" required class="sr-only" checked>
                        <div class="flex items-center w-full">
                            <div class="flex-shrink-0 bg-green-500 rounded-full p-2 mr-3">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Add Stock</p>
                                <p class="text-xs text-gray-500">New purchase</p>
                            </div>
                        </div>
                    </label>

                    <label class="relative flex cursor-pointer p-4 border-2 border-gray-300 rounded-lg hover:border-red-500 transition">
                        <input type="radio" name="update_type" value="deduct" required class="sr-only">
                        <div class="flex items-center w-full">
                            <div class="flex-shrink-0 bg-red-500 rounded-full p-2 mr-3">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Deduct Stock</p>
                                <p class="text-xs text-gray-500">Used/Damaged</p>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Quantity -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Quantity (kg) *</label>
                <input type="number" name="quantity" required min="0.01" step="0.01"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent text-lg"
                       placeholder="0.00"
                       value="<?php echo htmlspecialchars($_POST['quantity'] ?? ''); ?>">
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                <textarea name="notes" rows="3"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                          placeholder="Reason for stock update..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
            </div>

            <!-- Submit Buttons -->
            <div class="flex gap-3 mt-6">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-3 rounded-lg font-semibold">Update Stock</button>
                <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold inline-block">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
const feedSelect = document.getElementById('feed_select');
const currentStockDisplay = document.getElementById('current_stock_display');
const currentStockText = document.getElementById('current_stock');

feedSelect.addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const currentQty = selectedOption.getAttribute('data-current');
    if (currentQty) {
        currentStockText.textContent = parseFloat(currentQty).toFixed(2) + ' kg';
        currentStockDisplay.classList.remove('hidden');
    } else {
        currentStockDisplay.classList.add('hidden');
    }
});

if (feedSelect.value) feedSelect.dispatchEvent(new Event('change'));
</script>

</body>
</html>
