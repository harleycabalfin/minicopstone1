<?php
require_once '../../includes/db.php';
requireUser();

$db = getDB();
$success = '';
$error = '';

// Get chicken ID
$chicken_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($chicken_id <= 0) {
    header("Location: index.php");
    exit();
}

// Fetch chicken data
$stmt = $db->prepare("SELECT * FROM chickens WHERE chicken_id = ?");
$stmt->bind_param("i", $chicken_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$chicken = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batch_number = trim($_POST['batch_number']);
    $breed = trim($_POST['breed']);
    $quantity = intval($_POST['quantity']);
    $age_in_weeks = intval($_POST['age_in_weeks']);
    $status = $_POST['status'];
    $purchase_date = $_POST['purchase_date'];
    $purchase_price = floatval($_POST['purchase_price']);
    $current_weight = !empty($_POST['current_weight']) ? floatval($_POST['current_weight']) : null;
    $notes = trim($_POST['notes']);

    // Validate
    if (empty($batch_number) || empty($breed) || $quantity <= 0 || empty($purchase_date)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Check for duplicate batch number
        $check = $db->prepare("SELECT chicken_id FROM chickens WHERE batch_number = ? AND chicken_id != ?");
        $check->bind_param("si", $batch_number, $chicken_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Batch number already exists for another record!';
        } else {
            // Update record
            $stmt = $db->prepare("UPDATE chickens 
                SET batch_number = ?, breed = ?, quantity = ?, age_in_weeks = ?, status = ?, 
                    purchase_date = ?, purchase_price = ?, current_weight = ?, notes = ? 
                WHERE chicken_id = ?");
            $stmt->bind_param("ssiissdssi", $batch_number, $breed, $quantity, $age_in_weeks, $status, 
                              $purchase_date, $purchase_price, $current_weight, $notes, $chicken_id);

            if ($stmt->execute()) {
                logAction($_SESSION['user_id'], "Updated chicken batch", "chickens", $chicken_id);
                $success = 'Chicken batch updated successfully!';

                // Refresh data
                $stmt = $db->prepare("SELECT * FROM chickens WHERE chicken_id = ?");
                $stmt->bind_param("i", $chicken_id);
                $stmt->execute();
                $chicken = $stmt->get_result()->fetch_assoc();
            } else {
                $error = 'Failed to update batch: ' . $db->error;
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
    <title>Edit Batch - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <div class="bg-gray-800 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo SITE_NAME; ?> - Edit Batch</h1>
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
                <h3 class="text-lg font-semibold text-gray-700">
                    Edit Batch: <?php echo htmlspecialchars($chicken['batch_number']); ?>
                </h3>
            </div>

            <form method="POST" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Batch Number -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Batch Number *</label>
                        <input type="text" name="batch_number" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               value="<?php echo htmlspecialchars($chicken['batch_number']); ?>">
                    </div>

                    <!-- Breed -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Breed *</label>
                        <input type="text" name="breed" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               value="<?php echo htmlspecialchars($chicken['breed']); ?>">
                    </div>

                    <!-- Quantity -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quantity *</label>
                        <input type="number" name="quantity" required min="1"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               value="<?php echo htmlspecialchars($chicken['quantity']); ?>">
                    </div>

                    <!-- Age -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Age (weeks) *</label>
                        <input type="number" name="age_in_weeks" required min="0"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               value="<?php echo htmlspecialchars($chicken['age_in_weeks']); ?>">
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                        <select name="status" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="active" <?php echo $chicken['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="sold" <?php echo $chicken['status'] === 'sold' ? 'selected' : ''; ?>>Sold</option>
                            <option value="deceased" <?php echo $chicken['status'] === 'deceased' ? 'selected' : ''; ?>>Deceased</option>
                        </select>
                    </div>

                    <!-- Purchase Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Purchase Date *</label>
                        <input type="date" name="purchase_date" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               value="<?php echo htmlspecialchars($chicken['purchase_date']); ?>">
                    </div>

                    <!-- Purchase Price -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Purchase Price (₱) *</label>
                        <input type="number" name="purchase_price" required min="0" step="0.01"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               value="<?php echo htmlspecialchars($chicken['purchase_price']); ?>">
                    </div>

                    <!-- Current Weight -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Current Weight (kg)</label>
                        <input type="number" name="current_weight" min="0" step="0.01"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               value="<?php echo htmlspecialchars($chicken['current_weight'] ?? ''); ?>">
                    </div>

                    <!-- Notes -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                        <textarea name="notes" rows="4"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo htmlspecialchars($chicken['notes'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex gap-3 mt-6">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold">
                        Update Batch
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
