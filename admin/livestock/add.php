<?php
require_once '../../includes/db.php';
requireUser();

$db = getDB();
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batch_number = trim($_POST['batch_number']);
    $breed = trim($_POST['breed']);
    $quantity = intval($_POST['quantity']);
    $age_in_weeks = intval($_POST['age_in_weeks']);
    $purchase_date = $_POST['purchase_date'];
    $purchase_price = floatval($_POST['purchase_price']);
    $current_weight = !empty($_POST['current_weight']) ? floatval($_POST['current_weight']) : null;
    $notes = trim($_POST['notes']);

    // Validate
    if (empty($batch_number) || empty($breed) || $quantity <= 0 || empty($purchase_date)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Check if batch number already exists
        $check = $db->prepare("SELECT chicken_id FROM chickens WHERE batch_number = ?");
        $check->bind_param("s", $batch_number);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Batch number already exists!';
        } else {
            // Insert new batch
            $stmt = $db->prepare("INSERT INTO chickens (batch_number, breed, quantity, age_in_weeks, purchase_date, purchase_price, current_weight, notes, added_by) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiisddsi", $batch_number, $breed, $quantity, $age_in_weeks, $purchase_date, $purchase_price, $current_weight, $notes, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $new_id = $stmt->insert_id;
                logAction($_SESSION['user_id'], "Added new chicken batch", "chickens", $new_id);
                $success = 'Chicken batch added successfully!';
                
                // Clear form
                $_POST = array();
            } else {
                $error = 'Failed to add batch: ' . $db->error;
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
    <title>Add New Batch - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <div class="bg-gray-800 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo SITE_NAME; ?> - Add New Batch</h1>
            <div class="flex gap-4">
                <a href="index.php" class="hover:text-gray-300">Back to Livestock</a>
                <a href="../dashboard.php" class="hover:text-gray-300">Dashboard</a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto p-6 max-w-3xl">
        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo $success; ?>
                <a href="index.php" class="font-semibold underline ml-2">View all batches</a>
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
                <h3 class="text-lg font-semibold text-gray-700">Add New Chicken Batch</h3>
            </div>
            
            <form method="POST" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Batch Number -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Batch Number *</label>
                        <input type="text" name="batch_number" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g., BATCH-001"
                               value="<?php echo htmlspecialchars($_POST['batch_number'] ?? ''); ?>">
                    </div>

                    <!-- Breed -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Breed *</label>
                        <input type="text" name="breed" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g., Broiler, Layer"
                               value="<?php echo htmlspecialchars($_POST['breed'] ?? ''); ?>">
                    </div>

                    <!-- Quantity -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quantity *</label>
                        <input type="number" name="quantity" required min="1" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Number of chickens"
                               value="<?php echo htmlspecialchars($_POST['quantity'] ?? ''); ?>">
                    </div>

                    <!-- Age in Weeks -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Age (weeks) *</label>
                        <input type="number" name="age_in_weeks" required min="0" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="0"
                               value="<?php echo htmlspecialchars($_POST['age_in_weeks'] ?? '0'); ?>">
                    </div>

                    <!-- Purchase Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Purchase Date *</label>
                        <input type="date" name="purchase_date" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               value="<?php echo htmlspecialchars($_POST['purchase_date'] ?? date('Y-m-d')); ?>">
                    </div>

                    <!-- Purchase Price -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Purchase Price (₱) *</label>
                        <input type="number" name="purchase_price" required min="0" step="0.01" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="0.00"
                               value="<?php echo htmlspecialchars($_POST['purchase_price'] ?? ''); ?>">
                    </div>

                    <!-- Current Weight -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Current Weight (kg)</label>
                        <input type="number" name="current_weight" min="0" step="0.01" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Optional"
                               value="<?php echo htmlspecialchars($_POST['current_weight'] ?? ''); ?>">
                    </div>

                    <!-- Notes -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                        <textarea name="notes" rows="4" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Any additional information..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex gap-3 mt-6">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold">
                        Add Batch
                    </button>
                    <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold inline-block">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>