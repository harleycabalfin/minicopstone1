<?php
require_once '../../includes/db.php';
requireUser();
$db = getDB();

$user_id = intval($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
  die("User not found.");
}

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $full_name = trim($_POST['full_name']);
  $role = $_POST['role'];
  $new_password = trim($_POST['password']);

  if (!in_array($role, ['admin', 'staff'])) {
    $error = "Invalid role selected.";
  } elseif (empty($full_name)) {
    $error = "Full name is required.";
  } else {
    if (!empty($new_password)) {
      // ✅ Hash new password if entered
      $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
      $stmt = $db->prepare("UPDATE users SET full_name = ?, role = ?, password_hash = ? WHERE user_id = ?");
      $stmt->bind_param("sssi", $full_name, $role, $hashed_password, $user_id);
    } else {
      // ✅ Keep old password
      $stmt = $db->prepare("UPDATE users SET full_name = ?, role = ? WHERE user_id = ?");
      $stmt->bind_param("ssi", $full_name, $role, $user_id);
    }

    if ($stmt->execute()) {
      $success = !empty($new_password) 
        ? "Staff and password updated successfully." 
        : "Staff updated successfully.";
    } else {
      $error = "Failed to update staff.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Staff - <?php echo SITE_NAME; ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
  <div class="bg-gray-800 text-white p-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold">Edit Staff</h1>
    <a href="index.php" class="hover:text-gray-300">Back</a>
  </div>

  <div class="container mx-auto p-6 max-w-lg">
    <?php if ($success): ?>
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo $success; ?></div>
    <?php elseif ($error): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" class="bg-white p-6 rounded-lg shadow-md space-y-4">
      <div>
        <label class="block text-gray-700 font-medium mb-1">Full Name *</label>
        <input 
          type="text" 
          name="full_name" 
          value="<?php echo htmlspecialchars($user['full_name']); ?>" 
          required 
          class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500"
        >
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Role *</label>
        <select 
          name="role" 
          required
          class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500"
        >
          <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
          <option value="staff" <?php echo $user['role'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
        </select>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">New Password (optional)</label>
        <input 
          type="password" 
          name="password" 
          placeholder="Leave blank to keep current password"
          class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500"
        >
      </div>

      <button 
        type="submit" 
        class="w-full bg-orange-500 hover:bg-orange-600 text-white py-3 rounded-lg font-semibold"
      >
        Save Changes
      </button>
    </form>
  </div>
</body>
</html>
