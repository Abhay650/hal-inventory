<?php
require __DIR__ . '/../config.php';

require_auth();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $result = mysqli_query($conn, "SELECT * FROM COMPUTER_PARTS ORDER BY PART_ID");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    json_response(['success' => true, 'data' => $rows]);
}

if ($method === 'POST') {
    $b = read_json_body();
    $required = ['partid', 'partname', 'transaction', 'manufacturer', 'cost', 'quantity', 'dept'];
    foreach ($required as $f) {
        if (!isset($b[$f]) || $b[$f] === '') {
            json_response(['success' => false, 'message' => "Missing field: $f"], 400);
        }
    }
    $stmt = mysqli_prepare($conn, "INSERT INTO COMPUTER_PARTS VALUES (?,?,?,?,?,?,?)");
    mysqli_stmt_bind_param(
        $stmt, 'sssssis',
        $b['partid'], $b['partname'], $b['transaction'], $b['manufacturer'],
        $b['cost'], $b['quantity'], $b['dept']
    );
    if (mysqli_stmt_execute($stmt)) {
        json_response(['success' => true, 'message' => 'Part added']);
    }
    json_response(['success' => false, 'message' => mysqli_stmt_error($stmt)], 400);
}

if ($method === 'PUT') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        json_response(['success' => false, 'message' => 'Missing id'], 400);
    }
    $b = read_json_body();
    $stmt = mysqli_prepare($conn, "UPDATE COMPUTER_PARTS SET PART_NAME=?, TRANSACTION_ID=?, MANUFACTURER_NAME=?, UNIT_COST=?, QUANTITY=?, DEPT_ID=? WHERE PART_ID=?");
    mysqli_stmt_bind_param(
        $stmt, 'sssssss',
        $b['partname'], $b['transaction'], $b['manufacturer'], $b['cost'], $b['quantity'], $b['dept'], $id
    );
    if (mysqli_stmt_execute($stmt)) {
        json_response(['success' => true, 'message' => 'Part updated']);
    }
    json_response(['success' => false, 'message' => mysqli_stmt_error($stmt)], 400);
}

if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        json_response(['success' => false, 'message' => 'Missing id'], 400);
    }
    $stmt = mysqli_prepare($conn, "DELETE FROM COMPUTER_PARTS WHERE PART_ID=?");
    mysqli_stmt_bind_param($stmt, 's', $id);
    mysqli_stmt_execute($stmt);
    json_response(['success' => true, 'message' => 'Part deleted']);
}

json_response(['success' => false, 'message' => 'Method not allowed'], 405);
