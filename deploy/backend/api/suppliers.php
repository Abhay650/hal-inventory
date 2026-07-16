<?php
require __DIR__ . '/../config.php';

require_auth();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $result = mysqli_query($conn, "SELECT * FROM SUPPLIER ORDER BY SUPPLIER_ID");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    json_response(['success' => true, 'data' => $rows]);
}

if ($method === 'POST') {
    $b = read_json_body();
    foreach (['id', 'name', 'contact', 'address'] as $f) {
        if (!isset($b[$f]) || $b[$f] === '') {
            json_response(['success' => false, 'message' => "Missing field: $f"], 400);
        }
    }
    $stmt = mysqli_prepare($conn, "INSERT INTO SUPPLIER VALUES (?,?,?,?)");
    mysqli_stmt_bind_param($stmt, 'ssss', $b['id'], $b['name'], $b['contact'], $b['address']);
    if (mysqli_stmt_execute($stmt)) {
        json_response(['success' => true, 'message' => 'Supplier added']);
    }
    json_response(['success' => false, 'message' => mysqli_stmt_error($stmt)], 400);
}

if ($method === 'PUT') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        json_response(['success' => false, 'message' => 'Missing id'], 400);
    }
    $b = read_json_body();
    $stmt = mysqli_prepare($conn, "UPDATE SUPPLIER SET SUPPLIER_NAME=?, CONTACT_NO=?, ADDRESS=? WHERE SUPPLIER_ID=?");
    mysqli_stmt_bind_param($stmt, 'ssss', $b['name'], $b['contact'], $b['address'], $id);
    if (mysqli_stmt_execute($stmt)) {
        json_response(['success' => true, 'message' => 'Supplier updated']);
    }
    json_response(['success' => false, 'message' => mysqli_stmt_error($stmt)], 400);
}

if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        json_response(['success' => false, 'message' => 'Missing id'], 400);
    }
    $stmt = mysqli_prepare($conn, "DELETE FROM SUPPLIER WHERE SUPPLIER_ID=?");
    mysqli_stmt_bind_param($stmt, 's', $id);
    mysqli_stmt_execute($stmt);
    json_response(['success' => true, 'message' => 'Supplier deleted']);
}

json_response(['success' => false, 'message' => 'Method not allowed'], 405);
