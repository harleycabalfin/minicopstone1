<?php
require_once '../../includes/db.php';
requireUser();

$db = getDB();

// Fetch notification data for the bell icon
function getNotificationStats() {
    $db = getDB();
    $stats = [];
    
    // Unread notifications count
    $result = $db->query("SELECT COUNT(*) as total FROM notifications WHERE is_read = 0");
    $stats['unread_notifications'] = $result->fetch_assoc()['total'];
    
    // Low stock alerts
    $result = $db->query("SELECT COUNT(*) as total FROM feed_inventory WHERE quantity_kg < reorder_level");
    $stats['low_stock_count'] = $result->fetch_assoc()['total'];
    
    return $stats;
}

// Fetch low stock items
function getLowStockItems() {
    $db = getDB();
    return $db->query("SELECT * FROM feed_inventory WHERE quantity_kg < reorder_level ORDER BY quantity_kg ASC")->fetch_all(MYSQLI_ASSOC);
}

// Fetch notifications
function getNotifications($limit = 10) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM notifications ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$notificationStats = getNotificationStats();
$lowStockItems = getLowStockItems();
$notifications = getNotifications();

// Calculate total notification count (unread + low stock alerts)
$total_notifications = $notificationStats['unread_notifications'] + $notificationStats['low_stock_count'];

// --- Handle Add Batch Submission ---
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_batch'])) {
    $batch_number = trim($_POST['batch_number']);
    $breed = trim($_POST['breed']);
    $quantity = intval($_POST['quantity']);
    $age_in_weeks = intval($_POST['age_in_weeks']);
    $purchase_date = $_POST['purchase_date'];
    $weight = floatval($_POST['current_weight']);
    $added_by = $_SESSION['user_id'];
    $status = 'active';

    if (empty($batch_number) || empty($breed) || $quantity <= 0) {
        $error = "Please fill in all required fields correctly.";
    } else {
        $stmt = $db->prepare("INSERT INTO chickens (batch_number, breed, quantity, age_in_weeks, purchase_date, current_weight, added_by, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssiisdss", $batch_number, $breed, $quantity, $age_in_weeks, $purchase_date, $weight, $added_by, $status);

        if ($stmt->execute()) {
            logAction($_SESSION['user_id'], "Added new batch: $batch_number", "chickens", $db->insert_id);
            $success = "New batch added successfully!";
        } else {
            $error = "Error adding batch: " . $db->error;
        }
    }
}

// --- Fetch All Batches ---
$query = "SELECT c.*, u.full_name AS added_by_name 
          FROM chickens c 
          LEFT JOIN users u ON c.added_by = u.user_id 
          ORDER BY c.created_at DESC";
$result = $db->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livestock Management - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Navigation Bar -->
    <nav class="bg-green-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold">🐔 Poultry Farm System</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm">
                        Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'); ?>
                    </span>
                    <div class="relative">
                        <button id="notificationBtn" class="relative p-2 hover:bg-green-700 rounded-lg transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            <?php if ($total_notifications > 0): ?>
                                <span class="absolute top-0 right-0 bg-red-500 text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                    <?php echo $total_notifications; ?>
                                </span>
                            <?php endif; ?>
                        </button>
                        
                        <!-- Notification Dropdown -->
                        <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-xl z-50 max-h-96 overflow-y-auto">
                            <div class="p-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-800">Notifications</h3>
                            </div>
                            
                            <!-- Low Stock Alerts -->
                            <?php if (!empty($lowStockItems)): ?>
                                <div class="border-b border-gray-200">
                                    <div class="p-4 bg-red-50">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0">
                                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <h4 class="text-sm font-medium text-red-800">Low Stock Alert</h4>
                                                <div class="mt-2 text-xs text-red-700 space-y-1">
                                                    <?php foreach ($lowStockItems as $item): ?>
                                                        <div class="flex justify-between">
                                                            <span><?php echo htmlspecialchars($item['feed_type']); ?></span>
                                                            <span class="font-semibold"><?php echo number_format($item['quantity_kg'], 2); ?> kg</span>
                                                        </div>
                                                        <p class="text-xs text-gray-600">Reorder level: <?php echo number_format($item['reorder_level'], 2); ?> kg</p>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- System Notifications -->
                            <?php if (!empty($notifications)): ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="p-4 border-b border-gray-200 hover:bg-gray-50 <?php echo $notification['is_read'] ? 'bg-white' : 'bg-blue-50'; ?>">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0">
                                                <?php if ($notification['type'] == 'warning'): ?>
                                                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                    </svg>
                                                <?php elseif ($notification['type'] == 'success'): ?>
                                                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                    </svg>
                                                <?php else: ?>
                                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                                    </svg>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($notification['title']); ?></p>
                                                <p class="text-xs text-gray-600 mt-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                                <p class="text-xs text-gray-400 mt-1"><?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php if (empty($lowStockItems)): ?>
                                    <div class="p-4 text-center text-gray-500">
                                        <p class="text-sm">No notifications</p>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <div class="p-3 text-center border-t border-gray-200">
                                <a href="../notifications.php" class="text-sm text-green-600 hover:text-green-800 font-medium">View All Notifications</a>
                            </div>
                        </div>
                    </div>
                    <a href="../../logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg transition">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-white h-screen shadow-lg sticky top-0">
            <nav class="mt-6">
                <a href="../dashboard.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-50 hover:text-gray-800">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard
                </a>
                <a href="../livestock/" class="flex items-center px-6 py-3 text-gray-700 bg-green-50 border-r-4 border-green-600">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    Livestock
                </a>
                <a href="../inventory/" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-50 hover:text-gray-800">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    Inventory
                </a>
                <a href="../production/" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-50 hover:text-gray-800">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Production
                </a>
                <a href="../sale/" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-50 hover:text-gray-800">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Sales
                </a>
                <a href="../reports/" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-50 hover:text-gray-800">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Reports
                </a>
                <?php if (isAdmin()): ?>
                <a href="../users/" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-50 hover:text-gray-800">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    Users
                </a>
                 <a href="../logs/index.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-50 hover:text-gray-800">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    System Logs
                </a>
                <?php endif; ?>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-6">Livestock Management</h2>

            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded mb-6"><?php echo $success; ?></div>
            <?php elseif ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded mb-6"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Top Actions -->
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-800">Chicken Batches</h3>
                <div class="flex gap-3">
                    <a href="mortality.php" class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2 rounded-lg font-semibold transition">
                        Record Mortality
                    </a>
                    <button id="openModal" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold transition">
                        Add New Batch
                    </button>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-700">All Livestock Batches</h3>
                </div>

                <?php if ($result->num_rows > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch #</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Breed</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age (weeks)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purchase Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Weight (kg)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Added By</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($row['batch_number']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($row['breed']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo number_format($row['quantity']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $row['age_in_weeks']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php 
                                            $statusColors = [
                                                'active' => 'bg-green-100 text-green-800',
                                                'sold' => 'bg-blue-100 text-blue-800',
                                                'deceased' => 'bg-red-100 text-red-800'
                                            ];
                                            $colorClass = $statusColors[$row['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full <?php echo $colorClass; ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M d, Y', strtotime($row['purchase_date'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $row['current_weight'] ? number_format($row['current_weight'], 2) : 'N/A'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($row['added_by_name'] ?? 'Unknown'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="edit.php?id=<?php echo $row['chicken_id']; ?>" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm transition">
                                                Edit
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12 text-gray-500">
                        <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        <p class="mt-4 text-lg">No batches found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal -->
    <div id="batchModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg w-full max-w-lg shadow-lg">
            <!-- Header -->
            <div class="flex justify-between items-center border-b px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-800">Add New Chicken Batch</h3>
                <button id="closeModal" class="text-gray-500 hover:text-gray-700 text-2xl leading-none transition">&times;</button>
            </div>

            <!-- Form -->
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="add_batch" value="1">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Batch Number *</label>
                    <input type="text" name="batch_number" required 
                        placeholder="e.g., BATCH-2025-01" 
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Breed *</label>
                    <input type="text" name="breed" required 
                        placeholder="e.g., Rhode Island Red, Layer, or Broiler" 
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                        <input type="number" name="quantity" required min="1"
                            placeholder="e.g., 150"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Age (weeks)</label>
                        <input type="number" name="age_in_weeks" min="0" 
                            placeholder="e.g., 2"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Date</label>
                    <input type="date" name="purchase_date" 
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Average Weight (kg)</label>
                    <input type="number" name="current_weight" step="0.01"
                        placeholder="e.g., 0.85"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" rows="3"
                        placeholder="e.g., Purchased from ABC Hatchery, 5% mortality in first week."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"></textarea>
                </div>

                <!-- Footer Buttons -->
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" id="closeModalFooter" 
                        class="bg-gray-400 hover:bg-gray-500 text-white px-5 py-2 rounded-lg transition">Cancel</button>
                    <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg font-semibold transition">
                        Save Batch
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle notification dropdown
        const notificationBtn = document.getElementById('notificationBtn');
        const notificationDropdown = document.getElementById('notificationDropdown');

        notificationBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationDropdown.classList.toggle('hidden');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!notificationBtn.contains(e.target) && !notificationDropdown.contains(e.target)) {
                notificationDropdown.classList.add('hidden');
            }
        });

        // Modal functionality
        const openBtn = document.getElementById('openModal');
        const closeBtn = document.getElementById('closeModal');
        const closeFooter = document.getElementById('closeModalFooter');
        const modal = document.getElementById('batchModal');

        openBtn.addEventListener('click', () => modal.classList.remove('hidden'));
        closeBtn.addEventListener('click', () => modal.classList.add('hidden'));
        closeFooter.addEventListener('click', () => modal.classList.add('hidden'));
        window.addEventListener('click', (e) => { if (e.target === modal) modal.classList.add('hidden'); });
    </script>
</body>
</html>