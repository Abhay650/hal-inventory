<?php
require __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Use POST'], 405);
}

$body = read_json_body();
$required = ['empid', 'name', 'dept', 'username', 'password', 'email', 'phone'];
foreach ($required as $field) {
    if (empty($body[$field])) {
        json_response(['success' => false, 'message' => "Missing field: $field"], 400);
    }
}

$hashed = password_hash($body['password'], PASSWORD_DEFAULT);

$stmt = mysqli_prepare($conn, "INSERT INTO USERS (EMPLOYEE_ID, EMPLOYEE_NAME, DEPARTMENT, USERNAME, PASSWORD, EMAIL, PHONE) VALUES (?,?,?,?,?,?,?)");
mysqli_stmt_bind_param(
    $stmt,
    'sssssss',
    $body['empid'],
    $body['name'],
    $body['dept'],
    $body['username'],
    $hashed,
    $body['email'],
    $body['phone']
);

if (mysqli_stmt_execute($stmt)) {
    json_response(['success' => true, 'message' => 'Registration successful']);
} else {
    $msg = mysqli_stmt_error($stmt);
    $friendly = str_contains($msg, 'Duplicate') ? 'That username is already taken' : 'Registration failed';
    json_response(['success' => false, 'message' => $friendly], 400);
}
