<?php
require __DIR__ . '/../config.php';

require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'message' => 'Use GET'], 405);
}

$key = $_GET['q'] ?? '';
$like = '%' . $key . '%';

$sql = "SELECT
            COMPUTER_PARTS.PART_ID, COMPUTER_PARTS.PART_NAME, COMPUTER_PARTS.MANUFACTURER_NAME,
            COMPUTER_PARTS.UNIT_COST, COMPUTER_PARTS.QUANTITY, COMPUTER_PARTS.DEPT_ID,
            STOCKS.CURRENT_STOCK, STOCKS.MINIMUM_STOCK, STOCKS.REORDER_STOCK,
            STOCKS.SHELF_LIFE, STOCKS.LAST_UPDATE
        FROM COMPUTER_PARTS
        LEFT JOIN STOCKS ON COMPUTER_PARTS.PART_ID = STOCKS.PART_ID
        WHERE COMPUTER_PARTS.PART_ID LIKE ? OR COMPUTER_PARTS.PART_NAME LIKE ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ss', $like, $like);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
}

json_response(['success' => true, 'data' => $rows]);
