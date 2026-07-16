<?php
require __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Use POST'], 405);
}

$body = read_json_body();
$username = $body['username'] ?? '';
$password = $body['password'] ?? '';

if (!$username || !$password) {
    json_response(['success' => false, 'message' => 'Username and password required'], 400);
}

$stmt = mysqli_prepare($conn, "SELECT PASSWORD FROM USERS WHERE USERNAME = ?");
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    json_response(['success' => false, 'message' => 'Invalid username or password'], 401);
}

$stored = $row['PASSWORD'];
$ok = false;

if (password_verify($password, $stored)) {
    $ok = true;
} elseif (hash_equals($stored, $password)) {
    // Legacy plaintext row (e.g. the seed admin account) — accept once,
    // then upgrade it to a proper hash so it's never stored in plaintext again.
    $ok = true;
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    $upd = mysqli_prepare($conn, "UPDATE USERS SET PASSWORD = ? WHERE USERNAME = ?");
    mysqli_stmt_bind_param($upd, 'ss', $newHash, $username);
    mysqli_stmt_execute($upd);
}

if (!$ok) {
    json_response(['success' => false, 'message' => 'Invalid username or password'], 401);
}

json_response([
    'success' => true,
    'username' => $username,
    'token' => issue_token($username),
]);
