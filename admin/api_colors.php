<?php
require_once __DIR__ . '/auth_check.php'; 
require_once 'db.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true);

switch ($method) {

    case 'GET':
        $colors = [];
        $sql    = "SELECT * FROM color ORDER BY color_id DESC";
        $result = $conn->query($sql);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $colors[] = $row;
            }
            sendResponse(true, 'Colors retrieved successfully', $colors);
        } else {
            sendResponse(false, 'Error: ' . $conn->error);
        }
        break;

    case 'POST':
        // FIX: validate required fields before sanitizing
        if (empty($input['color_name'])) {
            sendResponse(false, 'color_name is required');
        }

        $color_name = sanitize($conn, $input['color_name']);
        $hex_code   = isset($input['hex_code']) ? sanitize($conn, $input['hex_code']) : null;

        // FIX: check for duplicate color_name before inserting
        $dup = $conn->prepare("SELECT color_id FROM color WHERE color_name = ?");
        $dup->bind_param("s", $color_name);
        $dup->execute();
        if ($dup->get_result()->num_rows > 0) {
            $dup->close();
            sendResponse(false, "Color '$color_name' already exists");
        }
        $dup->close();

        $stmt = $conn->prepare("INSERT INTO color (color_name, hex_code) VALUES (?, ?)");
        $stmt->bind_param("ss", $color_name, $hex_code);

        if ($stmt->execute()) {
            sendResponse(true, 'Color added successfully', ['color_id' => $conn->insert_id]);
        } else {
            sendResponse(false, 'Error: ' . $stmt->error);
        }
        $stmt->close();
        break;

    case 'PUT':
        // FIX: validate required fields
        if (empty($input['color_id']) || empty($input['color_name'])) {
            sendResponse(false, 'color_id and color_name are required');
        }

        $color_id   = intval($input['color_id']);
        if ($color_id <= 0) {
            sendResponse(false, 'Invalid color_id');
        }

        $color_name = sanitize($conn, $input['color_name']);
        $hex_code   = isset($input['hex_code']) ? sanitize($conn, $input['hex_code']) : null;

        // FIX: check for duplicate color_name, excluding current record
        $dup = $conn->prepare("SELECT color_id FROM color WHERE color_name = ? AND color_id != ?");
        $dup->bind_param("si", $color_name, $color_id);
        $dup->execute();
        if ($dup->get_result()->num_rows > 0) {
            $dup->close();
            sendResponse(false, "Color '$color_name' already exists");
        }
        $dup->close();

        $stmt = $conn->prepare("UPDATE color SET color_name = ?, hex_code = ? WHERE color_id = ?");
        $stmt->bind_param("ssi", $color_name, $hex_code, $color_id);

        if ($stmt->execute()) {
            sendResponse(true, 'Color updated successfully');
        } else {
            sendResponse(false, 'Error: ' . $stmt->error);
        }
        $stmt->close();
        break;

    case 'DELETE':
        // FIX: validate color_id before casting
        if (empty($input['color_id'])) {
            sendResponse(false, 'color_id is required');
        }

        $color_id = intval($input['color_id']);
        if ($color_id <= 0) {
            sendResponse(false, 'Invalid color_id');
        }

        // Check if color is used in product_variant
        // (product_color table does not exist in this schema)
        $check2 = $conn->prepare("SELECT COUNT(*) as cnt FROM product_variant WHERE color_id = ?");
        $check2->bind_param("i", $color_id);
        $check2->execute();
        $row2 = $check2->get_result()->fetch_assoc();
        $check2->close();

        if ($row2['cnt'] > 0) {
            sendResponse(false, 'Cannot delete color. It is used in ' . $row2['cnt'] . ' product variant(s)');
        }

        $stmt = $conn->prepare("DELETE FROM color WHERE color_id = ?");
        $stmt->bind_param("i", $color_id);

        if ($stmt->execute()) {
            sendResponse(true, 'Color deleted successfully');
        } else {
            sendResponse(false, 'Error: ' . $stmt->error);
        }
        $stmt->close();
        break;

    default:
        sendResponse(false, 'Invalid request method');
        break;
}
?>