<?php
require_once '../../includes/db.php';
requireUser();
$db = getDB();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batch_number = trim($_POST['batch_number']);
    $production_date = $_POST['production_date'];
    $eggs_collected = intval($_POST['eggs_collected']);
    $damaged_eggs = intval($_POST['damaged_eggs']);
    $egg_weight_kg = floatval($_POST['egg_weight_kg']);
    $notes = trim($_POST['notes']);

    if (empty($batch_number) || empty($production_date) || $eggs_collected <= 0) {
        $error = "Please fill in all required fields.";
    } else {
        $stmt = $db->prepare("INSERT INTO egg_production (batch_number, production_date, eggs_collected, damaged_eggs, egg_weight_kg, notes, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiidss", $batch_number, $production_date, $eggs_collected, $damaged_eggs, $egg_weight_kg, $notes, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $success = "Production record added successfully.";
            logAction($_SESSION['user_id'], "Added production record for batch $batch_number", "egg_production", $db->insert_id);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Production - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="bg-gray-800 text-white p-4 flex justify-between items-center">
        <h1 class="text-2xl font-bold">Add Production</h1>
        <a href="index.php" class="hover:text-gray-300">Back</a>
    </div>

    <div class="container mx-auto p-6 max-w-xl">
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6"><?php echo $success; ?></div>
        <?php elseif ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6">
            <form method="POST" class="space-y-5">
                <div>
                    <label class="block font-medium text-gray-700">Batch Number *</label>
                    <input type="text" name="batch_number" required class="w-full border rounded-lg px-4 py-2 mt-1 focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block font-medium text-gray-700">Production Date *</label>
                    <input type="date" name="production_date" required class="w-full border rounded-lg px-4 py-2 mt-1 focus:ring-2 focus:ring-orange-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium text-gray-700">Eggs Collected *</label>
                        <input type="number" name="eggs_collected" required min="1" class="w-full border rounded-lg px-4 py-2 mt-1 focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700">Damaged Eggs</label>
                        <input type="number" name="damaged_eggs" min="0" class="w-full border rounded-lg px-4 py-2 mt-1 focus:ring-2 focus:ring-orange-500">
                    </div>
                </div>
                <div>
                    <label class="block font-medium text-gray-700">Total Weight (kg)</label>
                    <input type="number" name="egg_weight_kg" step="0.01" class="w-full border rounded-lg px-4 py-2 mt-1 focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block font-medium text-gray-700">Notes</label>
                    <textarea name="notes" rows="3" class="w-full border rounded-lg px-4 py-2 mt-1 focus:ring-2 focus:ring-orange-500"></textarea>
                </div>
                <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white py-3 rounded-lg font-semibold">Save Record</button>
            </form>
        </div>
    </div>
</body>
</html>
