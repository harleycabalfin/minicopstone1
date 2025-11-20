<?php
require_once '../../includes/db.php';
requireUser();
$db = getDB();

$user_id = intval($_GET['id'] ?? 0);

if ($user_id > 0) {
    // Update user status to 'inactive' instead of deleting
    $stmt = $db->prepare("UPDATE users SET status = 'inactive' WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        // Optional: log the action if you have a log system
        logAction($_SESSION['user_id'], "Archived user ID #$user_id", "users", $user_id);
    }
}

header("Location: index.php");
exit;
?>
