<?php
require_once '../../includes/db.php';
requireUser();
$db = getDB();

$success = '';
$error = '';
$reportData = [];

// Handle export request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportType = $_POST['report_type'] ?? '';
    $format = $_POST['format'] ?? '';
    $date = $_POST['date'] ?? '';
    $start = $_POST['start'] ?? '';
    $end = $_POST['end'] ?? '';
    $month = $_POST['month'] ?? '';

    if (empty($reportType) || empty($format)) {
        $error = "Please select a report type and format.";
    } else {
        switch ($reportType) {
            case 'daily':
                $targetDate = $date ?: date('Y-m-d');
                $stmt = $db->prepare("SELECT * FROM egg_production WHERE production_date = ?");
                $stmt->bind_param("s", $targetDate);
                $stmt->execute();
                $reportData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $success = "Daily report generated for $targetDate";
                break;

            case 'weekly':
                if (!$start || !$end) {
                    $error = "Please select a valid date range for the weekly report.";
                    break;
                }
                $stmt = $db->prepare("SELECT * FROM egg_production WHERE production_date BETWEEN ? AND ?");
                $stmt->bind_param("ss", $start, $end);
                $stmt->execute();
                $reportData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $success = "Weekly report generated for $start to $end";
                break;

            case 'monthly':
                if (!$month) {
                    $error = "Please select a valid month for the monthly report.";
                    break;
                }
                $start = $month . '-01';
                $end = date('Y-m-t', strtotime($month));
                $stmt = $db->prepare("SELECT * FROM egg_production WHERE production_date BETWEEN ? AND ?");
                $stmt->bind_param("ss", $start, $end);
                $stmt->execute();
                $reportData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $success = "Monthly report generated for " . date("F Y", strtotime($month));
                break;
        }

        // TODO: Add export logic (PDF/Excel)
        if ($format === 'pdf') {
            // You can integrate FPDF or Dompdf here
        } elseif ($format === 'excel') {
            // Integrate PhpSpreadsheet here
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Export Reports - <?php echo SITE_NAME; ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    // Show/hide date inputs dynamically
    function toggleFields() {
      const type = document.querySelector('select[name="report_type"]').value;
      document.getElementById('daily-field').classList.toggle('hidden', type !== 'daily');
      document.getElementById('weekly-field').classList.toggle('hidden', type !== 'weekly');
      document.getElementById('monthly-field').classList.toggle('hidden', type !== 'monthly');
    }
  </script>
</head>
<body class="bg-gray-100 min-h-screen">
  <!-- Header -->
  <div class="bg-gray-800 text-white p-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold"><?php echo SITE_NAME; ?> - Export Reports</h1>
    <a href="index.php" class="hover:text-gray-300">Back</a>
  </div>

  <div class="container mx-auto p-6 max-w-2xl">
    <!-- Alerts -->
    <?php if ($success): ?>
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6"><?php echo $success; ?></div>
    <?php elseif ($error): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="bg-white shadow-md rounded-lg p-6">
      <h2 class="text-xl font-semibold text-gray-800 mb-4">Choose Report to Export</h2>

      <form method="POST" class="space-y-5">
        <!-- Report Type -->
        <div>
          <label class="block font-medium text-gray-700 mb-1">Report Type *</label>
          <select name="report_type" onchange="toggleFields()" required 
                  class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500">
            <option value="">Select Type</option>
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
          </select>
        </div>

        <!-- Date Inputs -->
        <div id="daily-field" class="hidden">
          <label class="block font-medium text-gray-700 mb-1">Select Date *</label>
          <input type="date" name="date" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500">
        </div>

        <div id="weekly-field" class="hidden grid grid-cols-2 gap-4">
          <div>
            <label class="block font-medium text-gray-700 mb-1">Start Date *</label>
            <input type="date" name="start" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500">
          </div>
          <div>
            <label class="block font-medium text-gray-700 mb-1">End Date *</label>
            <input type="date" name="end" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500">
          </div>
        </div>

        <div id="monthly-field" class="hidden">
          <label class="block font-medium text-gray-700 mb-1">Select Month *</label>
          <input type="month" name="month" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500">
        </div>

        <!-- Format -->
        <div>
          <label class="block font-medium text-gray-700 mb-1">Export Format *</label>
          <select name="format" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500">
            <option value="">Select Format</option>
            <option value="pdf">PDF</option>
            <option value="excel">Excel</option>
          </select>
        </div>

        <!-- Submit -->
        <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white py-3 rounded-lg font-semibold">
          Generate Report
        </button>
      </form>
    </div>

    <!-- Show Preview Data if Generated -->
    <?php if (!empty($reportData)): ?>
      <div class="bg-white shadow-md rounded-lg mt-8 p-6">
        <h3 class="text-lg font-semibold mb-3 text-gray-700">Report Preview</h3>
        <table class="min-w-full text-sm border border-gray-200">
          <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
            <tr>
              <th class="px-4 py-2">Batch</th>
              <th class="px-4 py-2">Date</th>
              <th class="px-4 py-2">Eggs</th>
              <th class="px-4 py-2">Damaged</th>
              <th class="px-4 py-2">Weight (kg)</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reportData as $row): ?>
              <tr class="border-t">
                <td class="px-4 py-2"><?php echo htmlspecialchars($row['batch_number']); ?></td>
                <td class="px-4 py-2"><?php echo htmlspecialchars($row['production_date']); ?></td>
                <td class="px-4 py-2"><?php echo $row['eggs_collected']; ?></td>
                <td class="px-4 py-2 text-red-600"><?php echo $row['damaged_eggs']; ?></td>
                <td class="px-4 py-2"><?php echo number_format($row['egg_weight_kg'], 2); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <script>
    // Keep the right date fields visible on reload
    document.addEventListener('DOMContentLoaded', toggleFields);
  </script>
</body>
</html>
