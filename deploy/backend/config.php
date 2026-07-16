<?php
/**
 * Shared bootstrap for every API endpoint.
 * All config comes from environment variables so nothing secret is
 * committed to the repo. Set these in the Render dashboard:
 *
 *   DB_HOST      e.g. dpg-xxxxxxxx-a  (internal hostname Render gives your MySQL service)
 *   DB_PORT      3306
 *   DB_NAME      Computer_Spare
 *   DB_USER      root (or the user Render created)
 *   DB_PASS      the password Render generated
 *   APP_SECRET   any long random string, used to sign login tokens
 *   FRONTEND_ORIGIN   e.g. https://your-app.vercel.app  (or * while testing)
 */

error_reporting(E_ALL & ~E_DEPRECATED);

$allowedOrigin = getenv('FRONTEND_ORIGIN') ?: '*';
header("Access-Control-Allow-Origin: $allowedOrigin");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Preflight requests end here.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

function json_response($data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data);
    exit();
}

function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return $_POST; // fallback for classic form posts
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

// ---- Database ----------------------------------------------------------

$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_PORT = getenv('DB_PORT') ?: 3306;
$DB_NAME = getenv('DB_NAME') ?: 'Computer_Spare';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';

$conn = mysqli_init();
$connected = @mysqli_real_connect($conn, $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, (int)$DB_PORT);

if (!$connected) {
    json_response(['success' => false, 'message' => 'Database connection failed: ' . mysqli_connect_error()], 500);
}

// ---- Auth (simple signed token, no server-side session needed) --------

$APP_SECRET = getenv('APP_SECRET') ?: 'change-this-secret';

function issue_token(string $username): string
{
    global $APP_SECRET;
    $expires = time() + (60 * 60 * 12); // 12 hours
    $payload = base64_encode($username . '|' . $expires);
    $sig = hash_hmac('sha256', $payload, $APP_SECRET);
    return $payload . '.' . $sig;
}

function verify_token(?string $token): ?string
{
    global $APP_SECRET;
    if (!$token || !str_contains($token, '.')) {
        return null;
    }
    [$payload, $sig] = explode('.', $token, 2);
    $expected = hash_hmac('sha256', $payload, $APP_SECRET);
    if (!hash_equals($expected, $sig)) {
        return null;
    }
    $decoded = base64_decode($payload);
    [$username, $expires] = explode('|', $decoded);
    if ((int)$expires < time()) {
        return null;
    }
    return $username;
}

function require_auth(): string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!$header && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $header = $headers['Authorization'] ?? '';
    }
    $token = null;
    if (preg_match('/Bearer\s+(\S+)/', $header, $m)) {
        $token = $m[1];
    }
    $username = verify_token($token);
    if (!$username) {
        json_response(['success' => false, 'message' => 'Unauthorized. Please log in again.'], 401);
    }
    return $username;
}
