<?php
require_once '../../includes/db.php';
requireUser();
$db = getDB();

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $full_name = trim($_POST['full_name']);
  $username = trim($_POST['username']);
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $role = trim($_POST['role']);

  if (!$full_name || !$username || !$password || !$role) {
    $error = "All fields are required.";
  } else {
    $stmt = $db->prepare("INSERT INTO users (full_name, username, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $full_name, $username, $password, $role);

    if ($stmt->execute()) {
      $success = "User added successfully!";
    } else {
      $error = "Failed to add user. Username may already exist.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add User - <?php echo SITE_NAME; ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
  <div class="bg-gray-800 text-white p-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold">Add User</h1>
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
        <input type="text" name="full_name" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500">
      </div>
      <div>
        <label class="block text-gray-700 font-medium mb-1">Username *</label>
        <input type="text" name="username" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500">
      </div>
      <div>
        <label class="block text-gray-700 font-medium mb-1">Password *</label>
        <input type="password" name="password" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500">
      </div>
      <div>
        <label class="block text-gray-700 font-medium mb-1">Role *</label>
        <select name="role" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500">
          <option value="">Select Role</option>
          <option value="admin">Admin</option>
          <option value="staff">Staff</option>
        </select>
      </div>
      <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white py-3 rounded-lg font-semibold">Add User</button>
    </form>
  </div>
</body>
</html>
