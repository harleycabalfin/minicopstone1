<?php
require_once '../../includes/db.php';
requireUser();
$db = getDB();

$user_id = intval($_GET['id'] ?? 0);

if ($user_id > 0) {
    $stmt = $db->prepare("UPDATE users SET status = 'active' WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    logAction($_SESSION['user_id'], "Restored user ID #$user_id", "users", $user_id);
}

header("Location: index.php");
exit;
?>
