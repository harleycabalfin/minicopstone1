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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo SITE_NAME; ?></title>
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
                <a href="../livestock/" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-50 hover:text-gray-800">
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
                <a href="../reports/" class="flex items-center px-6 py-3 text-gray-700 bg-green-50 border-r-4 border-green-600">
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
            <h2 class="text-3xl font-bold text-gray-800 mb-6">Reports & Analytics</h2>

            <!-- Grid of Report Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl">
                
                <!-- Daily Report -->
                <a href="daily.php" class="group bg-white rounded-2xl shadow-md hover:shadow-lg border border-gray-200 p-6 transition transform hover:-translate-y-1">
                    <div class="flex items-center space-x-4">
                        <div class="bg-orange-100 p-3 rounded-full">
                            <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-orange-600">Daily Report</h3>
                            <p class="text-gray-500 text-sm">View today's egg production and sales data.</p>
                        </div>
                    </div>
                </a>

                <!-- Weekly Report -->
                <a href="weekly.php" class="group bg-white rounded-2xl shadow-md hover:shadow-lg border border-gray-200 p-6 transition transform hover:-translate-y-1">
                    <div class="flex items-center space-x-4">
                        <div class="bg-green-100 p-3 rounded-full">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 6h18M3 14h18M3 18h18"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-green-600">Weekly Summary</h3>
                            <p class="text-gray-500 text-sm">Review summarized data for the past week.</p>
                        </div>
                    </div>
                </a>

                <!-- Monthly Report -->
                <a href="monthly.php" class="group bg-white rounded-2xl shadow-md hover:shadow-lg border border-gray-200 p-6 transition transform hover:-translate-y-1">
                    <div class="flex items-center space-x-4">
                        <div class="bg-blue-100 p-3 rounded-full">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-blue-600">Monthly Report</h3>
                            <p class="text-gray-500 text-sm">Analyze trends and performance by month.</p>
                        </div>
                    </div>
                </a>

                <!-- Export Reports -->
                <a href="export.php" class="group bg-white rounded-2xl shadow-md hover:shadow-lg border border-gray-200 p-6 transition transform hover:-translate-y-1">
                    <div class="flex items-center space-x-4">
                        <div class="bg-purple-100 p-3 rounded-full">
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-purple-600">Export Reports</h3>
                            <p class="text-gray-500 text-sm">Export your reports to PDF or Excel formats.</p>
                        </div>
                    </div>
                </a>

            </div>
        </main>
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
    </script>
</body>
</html>