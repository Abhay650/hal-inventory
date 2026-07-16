<?php
require __DIR__ . '/../config.php';

require_auth();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sql = "SELECT STOCKS.*, COMPUTER_PARTS.PART_NAME
            FROM STOCKS
            INNER JOIN COMPUTER_PARTS ON STOCKS.PART_ID = COMPUTER_PARTS.PART_ID
            ORDER BY STOCKS.STOCK_ID";
    $result = mysqli_query($conn, $sql);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    json_response(['success' => true, 'data' => $rows]);
}

if ($method === 'POST') {
    $b = read_json_body();
    foreach (['partid', 'current', 'minimum', 'reorder', 'life', 'date'] as $f) {
        if (!isset($b[$f]) || $b[$f] === '') {
            json_response(['success' => false, 'message' => "Missing field: $f"], 400);
        }
    }
    $stmt = mysqli_prepare($conn, "INSERT INTO STOCKS (PART_ID, CURRENT_STOCK, MINIMUM_STOCK, REORDER_STOCK, SHELF_LIFE, LAST_UPDATE) VALUES (?,?,?,?,?,?)");
    mysqli_stmt_bind_param(
        $stmt, 'siiiss',
        $b['partid'], $b['current'], $b['minimum'], $b['reorder'], $b['life'], $b['date']
    );
    if (mysqli_stmt_execute($stmt)) {
        json_response(['success' => true, 'message' => 'Stock saved']);
    }
    json_response(['success' => false, 'message' => mysqli_stmt_error($stmt)], 400);
}

if ($method === 'PUT') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        json_response(['success' => false, 'message' => 'Missing id'], 400);
    }
    $b = read_json_body();
    $stmt = mysqli_prepare($conn, "UPDATE STOCKS SET CURRENT_STOCK=?, MINIMUM_STOCK=?, REORDER_STOCK=?, SHELF_LIFE=?, LAST_UPDATE=? WHERE STOCK_ID=?");
    mysqli_stmt_bind_param($stmt, 'iiissi', $b['current'], $b['minimum'], $b['reorder'], $b['life'], $b['date'], $id);
    if (mysqli_stmt_execute($stmt)) {
        json_response(['success' => true, 'message' => 'Stock updated']);
    }
    json_response(['success' => false, 'message' => mysqli_stmt_error($stmt)], 400);
}

if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        json_response(['success' => false, 'message' => 'Missing id'], 400);
    }
    $stmt = mysqli_prepare($conn, "DELETE FROM STOCKS WHERE STOCK_ID=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    json_response(['success' => true, 'message' => 'Stock deleted']);
}

json_response(['success' => false, 'message' => 'Method not allowed'], 405);
