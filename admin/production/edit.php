<?php
require_once '../../includes/db.php';
requireUser();
$db = getDB();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: index.php"); exit; }

$success = '';
$error = '';

$stmt = $db->prepare("SELECT * FROM egg_production WHERE production_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();

if (!$record) {
    die("Record not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batch_number = trim($_POST['batch_number']);
    $production_date = $_POST['production_date'];
    $eggs_collected = intval($_POST['eggs_collected']);
    $damaged_eggs = intval($_POST['damaged_eggs']);
    $egg_weight_kg = floatval($_POST['egg_weight_kg']);
    $notes = trim($_POST['notes']);

    $stmt = $db->prepare("UPDATE egg_production SET batch_number=?, production_date=?, eggs_collected=?, damaged_eggs=?, egg_weight_kg=?, notes=? WHERE production_id=?");
    $stmt->bind_param("ssiidss", $batch_number, $production_date, $eggs_collected, $damaged_eggs, $egg_weight_kg, $notes, $id);

    if ($stmt->execute()) {
        $success = "Record updated successfully.";
        logAction($_SESSION['user_id'], "Edited production record for batch $batch_number", "egg_production", $id);
    } else {
        $error = "Failed to update record: " . $db->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Production - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="bg-gray-800 text-white p-4 flex justify-between items-center">
        <h1 class="text-2xl font-bold">Edit Production</h1>
        <a href="index.php" class="hover:text-gray-300">Back</a>
    </div>

    <div class="container mx-auto p-6 max-w-xl">
        <?php if ($success): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6"><?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6"><?php echo $error; ?></div><?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6">
            <form method="POST" class="space-y-5">
                <div>
                    <label class="block font-medium text-gray-700">Batch Number *</label>
                    <input type="text" name="batch_number" value="<?php echo htmlspecialchars($record['batch_number']); ?>" required class="w-full border rounded-lg px-4 py-2 mt-1 focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block font-medium text-gray-700">Production Date *</label>
                    <input type="date" name="production_date" value="<?php echo htmlspecialchars($record['production_date']); ?>" required class="w-full border rounded-lg px-4 py-2 mt-1 focus:ring-2 focus:ring-orange-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium text-gray-700">Eggs Collected *</label>
                        <input type="number" name="eggs_collected" value="<?php echo $record['eggs_collected']; ?>" required class="w-full border rounded-lg px-4 py-2 mt-1 focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700">Damaged Eggs</label>
                        <input type="number" name="damaged_eggs" value="<?php echo $record['damaged_eggs']; ?>" class="w-full border rounded-lg px-4 py-2 mt-1 focus:ring-2 focus:ring-orange-500">
                    </div>
                </div>
                <div>
                    <label class="block font-medium text-gray-700">Total Weight (kg)</label>
                    <input type="number" name="egg_weight_kg" value="<?php echo $record['egg_weight_kg']; ?>" step="0.01" class="w-full border rounded-lg px-4 py-2 mt-1 focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block font-medium text-gray-700">Notes</label>
                    <textarea name="notes" rows="3" class="w-full border rounded-lg px-4 py-2 mt-1 focus:ring-2 focus:ring-orange-500"><?php echo htmlspecialchars($record['notes']); ?></textarea>
                </div>
                <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white py-3 rounded-lg font-semibold">Update Record</button>
            </form>
        </div>
    </div>
</body>
</html>
