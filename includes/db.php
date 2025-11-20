<?php
// ======================================================================
// DATABASE CONNECTION + AUTH + SESSION + UTILITY FUNCTIONS
// ======================================================================

// ---- DATABASE CONFIG ----
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "poultry_farm_db"; // your uploaded DB name

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(["status" => "error", "message" => "Database connection failed."]));
}
$conn->set_charset("utf8");

// ---- SITE NAME ----
define("SITE_NAME", "Poultry Farm System");

// ======================================================================
// AUTO-DETECT: WEB OR API REQUEST
// ======================================================================
$is_api_request = (
    isset($_SERVER['HTTP_ACCEPT']) &&
    strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
) ||
(strpos($_SERVER['REQUEST_URI'], '/api/') !== false);

// ======================================================================
// SESSION MANAGEMENT (for web interface)
// ======================================================================
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        session_start();
    }
}

if (!$is_api_request) {
    startSecureSession();
}

// ======================================================================
// DATABASE ACCESS HELPER
// ======================================================================
function getDB() {
    global $conn;
    return $conn;
}

// ======================================================================
// AUTHENTICATION HELPERS (web only)
// ======================================================================
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        header("Location: ../login.php");
        exit();
    }
}

function requireUser() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

// ======================================================================
// SESSION TIMEOUT HANDLER
// ======================================================================
function checkSessionTimeout() {
    $timeout_duration = 1800; // 30 minutes
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
        session_unset();
        session_destroy();
        header("Location: ../login.php?timeout=true");
        exit();
    }
    $_SESSION['LAST_ACTIVITY'] = time();
}

// ======================================================================
// LOGIN FUNCTION (supports both Web & API) — uses password_hash
// ======================================================================
function login($username, $password) {
    global $conn, $is_api_request;

    $stmt = $conn->prepare("SELECT user_id, username, password_hash, full_name, role FROM users WHERE username = ? AND is_active = 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // ✅ Secure password verification
        if (password_verify($password, $user['password_hash'])) {

            if (!$is_api_request) {
                startSecureSession();
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['LAST_ACTIVITY'] = time();
            }

            // Update last login
            $update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $update->bind_param("i", $user['user_id']);
            $update->execute();

            // Log the action
            logAction($user['user_id'], "User logged in", "users", $user['user_id']);

            $response = [
                "success" => true,
                "role" => $user['role'],
                "user" => [
                    "id" => $user['user_id'],
                    "name" => $user['full_name'],
                    "username" => $user['username'],
                    "role" => $user['role']
                ]
            ];
        } else {
            $response = ["success" => false, "message" => "Invalid password."];
        }
    } else {
        $response = ["success" => false, "message" => "User not found or inactive."];
    }

    // If API request, send JSON
    if ($is_api_request) {
        header("Content-Type: application/json");
        echo json_encode($response);
        exit;
    }

    return $response;
}

// ======================================================================
// LOGGING FUNCTION
// ======================================================================
function logAction($user_id, $action, $table_affected = null, $record_id = null) {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    $stmt = $conn->prepare("INSERT INTO system_logs (user_id, action, table_affected, record_id, ip_address, user_agent)
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississ", $user_id, $action, $table_affected, $record_id, $ip, $agent);
    $stmt->execute();
}
?>
