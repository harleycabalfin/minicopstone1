<?php
require_once '../../includes/db.php';
requireUser();

$db = getDB();
$success = '';
$error = '';

// Get all active batches for dropdown
$batches = $db->query("SELECT batch_number, breed, quantity FROM chickens WHERE status = 'active' ORDER BY batch_number");

// Get recent mortality records
$mortality_records = $db->query("SELECT m.*, u.full_name as recorded_by_name 
                                 FROM mortality_records m 
                                 LEFT JOIN users u ON m.recorded_by = u.user_id 
                                 ORDER BY m.death_date DESC, m.created_at DESC 
                                 LIMIT 10");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batch_number = trim($_POST['batch_number']);
    $death_date = $_POST['death_date'];
    $number_of_deaths = intval($_POST['number_of_deaths']);
    $cause_of_death = trim($_POST['cause_of_death']);
    $age_at_death_weeks = !empty($_POST['age_at_death_weeks']) ? intval($_POST['age_at_death_weeks']) : null;
    $notes = trim($_POST['notes']);

    // Validate
    if (empty($batch_number) || empty($death_date) || $number_of_deaths <= 0) {
        $error = 'Please fill in all required fields.';
    } else {
        // Check if batch exists and has enough chickens
        $check = $db->prepare("SELECT quantity FROM chickens WHERE batch_number = ? AND status = 'active'");
        $check->bind_param("s", $batch_number);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows === 0) {
            $error = 'Batch not found or not active.';
        } else {
            $batch_data = $result->fetch_assoc();
            if ($number_of_deaths > $batch_data['quantity']) {
                $error = 'Number of deaths exceeds current batch quantity!';
            } else {
                // Insert mortality record
                $stmt = $db->prepare("INSERT INTO mortality_records (batch_number, death_date, number_of_deaths, cause_of_death, age_at_death_weeks, notes, recorded_by) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssisssi", $batch_number, $death_date, $number_of_deaths, $cause_of_death, $age_at_death_weeks, $notes, $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    $mortality_id = $stmt->insert_id;
                    
                    // Update chicken quantity
                    $new_quantity = $batch_data['quantity'] - $number_of_deaths;
                    $update = $db->prepare("UPDATE chickens SET quantity = ? WHERE batch_number = ?");
                    $update->bind_param("is", $new_quantity, $batch_number);
                    $update->execute();
                    
                    // If quantity reaches 0, mark as deceased
                    if ($new_quantity <= 0) {
                        $db->query("UPDATE chickens SET status = 'deceased' WHERE batch_number = '$batch_number'");
                    }
                    
                    logAction($_SESSION['user_id'], "Recorded mortality", "mortality_records", $mortality_id);
                    $success = "Mortality record added successfully! Batch quantity updated.";
                    
                    // Refresh records
                    $mortality_records = $db->query("SELECT m.*, u.full_name as recorded_by_name 
                                                     FROM mortality_records m 
                                                     LEFT JOIN users u ON m.recorded_by = u.user_id 
                                                     ORDER BY m.death_date DESC, m.created_at DESC 
                                                     LIMIT 10");
                    
                    // Clear form
                    $_POST = array();
                } else {
                    $error = 'Failed to record mortality: ' . $db->error;
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
    <title>Record Mortality - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <div class="bg-gray-800 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo SITE_NAME; ?> - Mortality Records</h1>
            <div class="flex gap-4">
                <a href="index.php" class="hover:text-gray-300">Back to Livestock</a>
                <a href="../dashboard.php" class="hover:text-gray-300">Dashboard</a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto p-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Form Card -->
            <div>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="bg-red-50 px-6 py-4 border-b border-red-200">
                        <h3 class="text-lg font-semibold text-red-900">Record New Mortality</h3>
                    </div>
                    
                    <!-- Success/Error Messages -->
                    <?php if ($success): ?>
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 m-4">
                            <p class="font-semibold"><?php echo $success; ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 m-4">
                            <p class="font-semibold"><?php echo $error; ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="p-6">
                        <div class="space-y-4">
                            <!-- Batch Number -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Batch Number *</label>
                                <select name="batch_number" required 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                    <option value="">Select Batch</option>
                                    <?php while ($batch = $batches->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($batch['batch_number']); ?>"
                                                <?php echo (isset($_POST['batch_number']) && $_POST['batch_number'] === $batch['batch_number']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($batch['batch_number']); ?> - 
                                            <?php echo htmlspecialchars($batch['breed']); ?> 
                                            (Qty: <?php echo $batch['quantity']; ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Death Date -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Death Date *</label>
                                <input type="date" name="death_date" required 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                       value="<?php echo htmlspecialchars($_POST['death_date'] ?? date('Y-m-d')); ?>">
                            </div>

                            <!-- Number of Deaths -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Number of Deaths *</label>
                                <input type="number" name="number_of_deaths" required min="1" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                       placeholder="Number of chickens"
                                       value="<?php echo htmlspecialchars($_POST['number_of_deaths'] ?? ''); ?>">
                            </div>

                            <!-- Cause of Death -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cause of Death</label>
                                <input type="text" name="cause_of_death" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                       placeholder="e.g., Disease, Natural, Unknown"
                                       value="<?php echo htmlspecialchars($_POST['cause_of_death'] ?? ''); ?>">
                            </div>

                            <!-- Age at Death -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Age at Death (weeks)</label>
                                <input type="number" name="age_at_death_weeks" min="0" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                       placeholder="Optional"
                                       value="<?php echo htmlspecialchars($_POST['age_at_death_weeks'] ?? ''); ?>">
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                                <textarea name="notes" rows="3" 
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                          placeholder="Additional details..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="mt-6">
                            <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-lg font-semibold">
                                Record Mortality
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Recent Records Card -->
            <div>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-700">Recent Mortality Records</h3>
                    </div>
                    
                    <div class="p-6">
                        <?php if ($mortality_records->num_rows > 0): ?>
                            <div class="space-y-4">
                                <?php while ($record = $mortality_records->fetch_assoc()): ?>
                                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <h4 class="font-semibold text-gray-900"><?php echo htmlspecialchars($record['batch_number']); ?></h4>
                                                <p class="text-sm text-gray-500"><?php echo date('M d, Y', strtotime($record['death_date'])); ?></p>
                                            </div>
                                            <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-semibold">
                                                <?php echo $record['number_of_deaths']; ?> deaths
                                            </span>
                                        </div>
                                        <?php if ($record['cause_of_death']): ?>
                                            <p class="text-sm text-gray-700 mb-1">
                                                <span class="font-medium">Cause:</span> <?php echo htmlspecialchars($record['cause_of_death']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ($record['age_at_death_weeks']): ?>
                                            <p class="text-sm text-gray-700 mb-1">
                                                <span class="font-medium">Age:</span> <?php echo $record['age_at_death_weeks']; ?> weeks
                                            </p>
                                        <?php endif; ?>
                                        <?php if ($record['notes']): ?>
                                            <p class="text-sm text-gray-600 mt-2 italic"><?php echo htmlspecialchars($record['notes']); ?></p>
                                        <?php endif; ?>
                                        <p class="text-xs text-gray-400 mt-2">
                                            Recorded by: <?php echo htmlspecialchars($record['recorded_by_name'] ?? 'Unknown'); ?>
                                        </p>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8 text-gray-500">
                                <svg class="mx-auto h-16 w-16 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p>No mortality records yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>