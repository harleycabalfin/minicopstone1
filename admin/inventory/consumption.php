<?php
require_once '../../includes/db.php';
requireUser();

$db = getDB();
$success = '';
$error = '';

// Get all active batches for dropdown
$batches = $db->query("SELECT batch_number, breed FROM chickens WHERE status = 'active' ORDER BY batch_number");

// Get all feed for dropdown
$feeds = $db->query("SELECT feed_id, feed_type, quantity_kg FROM feed_inventory ORDER BY feed_type");

// Get consumption records with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$records_per_page = 15;
$offset = ($page - 1) * $records_per_page;

$consumption_query = "SELECT fc.*, fi.feed_type, u.full_name as recorded_by_name 
                      FROM feed_consumption fc 
                      LEFT JOIN feed_inventory fi ON fc.feed_id = fi.feed_id 
                      LEFT JOIN users u ON fc.recorded_by = u.user_id 
                      ORDER BY fc.consumption_date DESC, fc.created_at DESC 
                      LIMIT $records_per_page OFFSET $offset";
$consumption_records = $db->query($consumption_query);

// Get total records for pagination
$total_records = $db->query("SELECT COUNT(*) as total FROM feed_consumption")->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feed_id = intval($_POST['feed_id']);
    $batch_number = trim($_POST['batch_number']);
    $quantity_used = floatval($_POST['quantity_used']);
    $consumption_date = $_POST['consumption_date'];
    $notes = trim($_POST['notes']);

    // Validate
    if ($feed_id <= 0 || empty($batch_number) || $quantity_used <= 0 || empty($consumption_date)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Check if feed has enough quantity
        $check = $db->prepare("SELECT feed_type, quantity_kg FROM feed_inventory WHERE feed_id = ?");
        $check->bind_param("i", $feed_id);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows === 0) {
            $error = 'Feed not found.';
        } else {
            $feed_data = $result->fetch_assoc();
            if ($quantity_used > $feed_data['quantity_kg']) {
                $error = 'Quantity exceeds available stock (' . number_format($feed_data['quantity_kg'], 2) . ' kg).';
            } else {
                // Insert consumption record
                $stmt = $db->prepare("INSERT INTO feed_consumption (feed_id, batch_number, quantity_used, consumption_date, notes, recorded_by) 
                                      VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isdssi", $feed_id, $batch_number, $quantity_used, $consumption_date, $notes, $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    $consumption_id = $stmt->insert_id;
                    
                    // Update feed inventory (deduct quantity)
                    $new_qty = $feed_data['quantity_kg'] - $quantity_used;
                    $update = $db->prepare("UPDATE feed_inventory SET quantity_kg = ? WHERE feed_id = ?");
                    $update->bind_param("di", $new_qty, $feed_id);
                    $update->execute();
                    
                    logAction($_SESSION['user_id'], "Recorded feed consumption", "feed_consumption", $consumption_id);
                    $success = 'Feed consumption recorded successfully! Stock updated.';
                    
                    // Refresh records
                    $consumption_records = $db->query($consumption_query);
                    $feeds = $db->query("SELECT feed_id, feed_type, quantity_kg FROM feed_inventory ORDER BY feed_type");
                    
                    // Clear form
                    $_POST = array();
                } else {
                    $error = 'Failed to record consumption: ' . $db->error;
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
    <title>Feed Consumption - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <div class="bg-gray-800 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo SITE_NAME; ?> - Feed Consumption</h1>
            <div class="flex gap-4">
                <a href="index.php" class="hover:text-gray-300">Back to Inventory</a>
                <a href="../dashboard.php" class="hover:text-gray-300">Dashboard</a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto p-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Form Card (Left Side) -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md overflow-hidden sticky top-6">
                    <div class="bg-purple-50 px-6 py-4 border-b border-purple-200">
                        <h3 class="text-lg font-semibold text-purple-900">Record Consumption</h3>
                    </div>
                    
                    <!-- Success/Error Messages -->
                    <?php if ($success): ?>
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 m-4">
                            <p class="text-sm font-semibold"><?php echo $success; ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 m-4">
                            <p class="text-sm font-semibold"><?php echo $error; ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="p-6">
                        <div class="space-y-4">
                            <!-- Feed Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Feed Type *</label>
                                <select name="feed_id" required 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm">
                                    <option value="">Select Feed</option>
                                    <?php 
                                    $feeds->data_seek(0);
                                    while ($feed = $feeds->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $feed['feed_id']; ?>"
                                                <?php echo (isset($_POST['feed_id']) && $_POST['feed_id'] == $feed['feed_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($feed['feed_type']); ?> (<?php echo number_format($feed['quantity_kg'], 2); ?> kg)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Batch Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Batch Number *</label>
                                <select name="batch_number" required 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm">
                                    <option value="">Select Batch</option>
                                    <?php 
                                    $batches->data_seek(0);
                                    while ($batch = $batches->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo htmlspecialchars($batch['batch_number']); ?>"
                                                <?php echo (isset($_POST['batch_number']) && $_POST['batch_number'] === $batch['batch_number']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($batch['batch_number']); ?> - <?php echo htmlspecialchars($batch['breed']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Quantity Used -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Quantity Used (kg) *</label>
                                <input type="number" name="quantity_used" required min="0.01" step="0.01" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                       placeholder="0.00"
                                       value="<?php echo htmlspecialchars($_POST['quantity_used'] ?? ''); ?>">
                            </div>

                            <!-- Consumption Date -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Consumption Date *</label>
                                <input type="date" name="consumption_date" required 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                       value="<?php echo htmlspecialchars($_POST['consumption_date'] ?? date('Y-m-d')); ?>">
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                                <textarea name="notes" rows="3" 
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm"
                                          placeholder="Additional details..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="mt-6">
                            <button type="submit" class="w-full bg-purple-500 hover:bg-purple-600 text-white px-6 py-3 rounded-lg font-semibold">
                                Record Consumption
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Records Table (Right Side) -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-700">Consumption History</h3>
                        <p class="text-sm text-gray-500 mt-1">Total Records: <?php echo number_format($total_records); ?></p>
                    </div>
                    
                    <?php if ($consumption_records->num_rows > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Feed Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity (kg)</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recorded By</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php while ($record = $consumption_records->fetch_assoc()): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo date('M d, Y', strtotime($record['consumption_date'])); ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                <?php echo htmlspecialchars($record['feed_type'] ?? 'Unknown'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($record['batch_number']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <span class="font-semibold"><?php echo number_format($record['quantity_used'], 2); ?></span> kg
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                <?php echo htmlspecialchars($record['recorded_by_name'] ?? 'Unknown'); ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600">
                                                <?php echo htmlspecialchars($record['notes'] ?: '-'); ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-between items-center">
                                <div class="text-sm text-gray-700">
                                    Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                                </div>
                                <div class="flex gap-2">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1; ?>" class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">
                                            Previous
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>" class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">
                                            Next
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <svg class="mx-auto h-16 w-16 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-gray-500">No consumption records yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>