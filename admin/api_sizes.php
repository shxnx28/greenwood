<?php
require_once __DIR__ . '/auth_check.php'; 
require_once 'db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true);

switch ($method) {

    case 'GET':
        $sizes  = [];
        $sql    = "SELECT size_id, size_name, sell_unit, pieces_per_box, created_at
                     FROM size
                 ORDER BY size_id DESC";
        $result = $conn->query($sql);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // Cast to proper types so JS receives numbers, not strings
                $row['size_id']        = (int) $row['size_id'];
                $row['pieces_per_box'] = $row['pieces_per_box'] !== null
                                         ? (int) $row['pieces_per_box']
                                         : null;
                $sizes[] = $row;
            }
            sendResponse(true, 'Sizes retrieved successfully', $sizes);
        } else {
            sendResponse(false, 'Error: ' . $conn->error);
        }
        break;

    case 'POST':
        $size_name    = sanitize($conn, $input['size_name'] ?? '');
        $sell_unit    = in_array($input['sell_unit'] ?? '', ['piece', 'box'])
                        ? $input['sell_unit'] : 'piece';
        $pieces_per_box = resolvePiecesPerBox($sell_unit, $input['pieces_per_box'] ?? null);

        if ($size_name === '') {
            sendResponse(false, 'Size name is required');
            break;
        }
        if ($sell_unit === 'box' && $pieces_per_box === null) {
            sendResponse(false, 'Pieces per box is required when sell unit is Box');
            break;
        }

        $ppb_sql = $pieces_per_box !== null ? (int) $pieces_per_box : 'NULL';

        $sql = "INSERT INTO size (size_name, sell_unit, pieces_per_box)
                VALUES ('$size_name', '$sell_unit', $ppb_sql)";

        if ($conn->query($sql)) {
            sendResponse(true, 'Size added successfully', ['size_id' => $conn->insert_id]);
        } else {
            sendResponse(false, 'Error: ' . $conn->error);
        }
        break;

    case 'PUT':
        $size_id      = intval($input['size_id'] ?? 0);
        $size_name    = sanitize($conn, $input['size_name'] ?? '');
        $sell_unit    = in_array($input['sell_unit'] ?? '', ['piece', 'box'])
                        ? $input['sell_unit'] : 'piece';
        $pieces_per_box = resolvePiecesPerBox($sell_unit, $input['pieces_per_box'] ?? null);

        if ($size_id <= 0) {
            sendResponse(false, 'Invalid size ID');
            break;
        }
        if ($size_name === '') {
            sendResponse(false, 'Size name is required');
            break;
        }
        if ($sell_unit === 'box' && $pieces_per_box === null) {
            sendResponse(false, 'Pieces per box is required when sell unit is Box');
            break;
        }

        $ppb_sql = $pieces_per_box !== null ? (int) $pieces_per_box : 'NULL';

        $sql = "UPDATE size
                   SET size_name     = '$size_name',
                       sell_unit     = '$sell_unit',
                       pieces_per_box = $ppb_sql
                 WHERE size_id = $size_id";

        if ($conn->query($sql)) {
            sendResponse(true, 'Size updated successfully');
        } else {
            sendResponse(false, 'Error: ' . $conn->error);
        }
        break;

    case 'DELETE':
        $size_id = intval($input['size_id'] ?? 0);

        // Guard: don't delete if variants are using this size
        $check_sql    = "SELECT COUNT(*) AS count FROM product_variant WHERE size_id = $size_id";
        $check_result = $conn->query($check_sql);
        $check_row    = $check_result->fetch_assoc();

        if ($check_row['count'] > 0) {
            sendResponse(false, 'Cannot delete size. It is being used by ' . $check_row['count'] . ' variant(s)');
        } else {
            $sql = "DELETE FROM size WHERE size_id = $size_id";
            if ($conn->query($sql)) {
                sendResponse(true, 'Size deleted successfully');
            } else {
                sendResponse(false, 'Error: ' . $conn->error);
            }
        }
        break;

    default:
        sendResponse(false, 'Invalid request method');
        break;
}

/**
 * pieces_per_box is only meaningful for 'box' sell units.
 * Returns an int when valid, null otherwise.
 */
function resolvePiecesPerBox(string $sellUnit, $raw): ?int {
    if ($sellUnit !== 'box') return null;
    if ($raw === null || $raw === '') return null;
    $val = (int) $raw;
    return $val > 0 ? $val : null;
}
?>