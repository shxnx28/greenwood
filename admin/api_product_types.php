<?php
require_once __DIR__ . '/auth_check.php'; 
require_once 'db.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true);

switch ($method) {

    case 'GET':
        $product_types = [];
        $sql           = "SELECT * FROM product_type ORDER BY product_type_id DESC";
        $result        = $conn->query($sql);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $product_types[] = $row;
            }
            sendResponse(true, 'Product types retrieved successfully', $product_types);
        } else {
            sendResponse(false, 'Error: ' . $conn->error);
        }
        break;

    case 'POST':
        // FIX: validate required field
        if (empty($input['product_type_name'])) {
            sendResponse(false, 'product_type_name is required');
        }

        $product_type_name = sanitize($conn, $input['product_type_name']);

        // FIX: check for duplicate name before inserting
        $dup = $conn->prepare("SELECT product_type_id FROM product_type WHERE product_type_name = ?");
        $dup->bind_param("s", $product_type_name);
        $dup->execute();
        if ($dup->get_result()->num_rows > 0) {
            $dup->close();
            sendResponse(false, "Product type '$product_type_name' already exists");
        }
        $dup->close();

        $stmt = $conn->prepare("INSERT INTO product_type (product_type_name) VALUES (?)");
        $stmt->bind_param("s", $product_type_name);

        if ($stmt->execute()) {
            sendResponse(true, 'Product type added successfully', ['product_type_id' => $conn->insert_id]);
        } else {
            sendResponse(false, 'Error: ' . $stmt->error);
        }
        $stmt->close();
        break;

    case 'PUT':
        // FIX: validate required fields
        if (empty($input['product_type_id']) || empty($input['product_type_name'])) {
            sendResponse(false, 'product_type_id and product_type_name are required');
        }

        $product_type_id   = intval($input['product_type_id']);
        if ($product_type_id <= 0) {
            sendResponse(false, 'Invalid product_type_id');
        }

        $product_type_name = sanitize($conn, $input['product_type_name']);

        // FIX: check for duplicate name, excluding current record
        $dup = $conn->prepare("SELECT product_type_id FROM product_type WHERE product_type_name = ? AND product_type_id != ?");
        $dup->bind_param("si", $product_type_name, $product_type_id);
        $dup->execute();
        if ($dup->get_result()->num_rows > 0) {
            $dup->close();
            sendResponse(false, "Product type '$product_type_name' already exists");
        }
        $dup->close();

        $stmt = $conn->prepare("UPDATE product_type SET product_type_name = ? WHERE product_type_id = ?");
        $stmt->bind_param("si", $product_type_name, $product_type_id);

        if ($stmt->execute()) {
            sendResponse(true, 'Product type updated successfully');
        } else {
            sendResponse(false, 'Error: ' . $stmt->error);
        }
        $stmt->close();
        break;

    case 'DELETE':
        // FIX: validate product_type_id before casting
        if (empty($input['product_type_id'])) {
            sendResponse(false, 'product_type_id is required');
        }

        $product_type_id = intval($input['product_type_id']);
        if ($product_type_id <= 0) {
            sendResponse(false, 'Invalid product_type_id');
        }

        // Check if type is used in products
        $check = $conn->prepare("SELECT COUNT(*) as cnt FROM product WHERE product_type_id = ?");
        $check->bind_param("i", $product_type_id);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        $check->close();

        if ($row['cnt'] > 0) {
            sendResponse(false, 'Cannot delete product type. It is being used by ' . $row['cnt'] . ' product(s)');
        }

        $stmt = $conn->prepare("DELETE FROM product_type WHERE product_type_id = ?");
        $stmt->bind_param("i", $product_type_id);

        if ($stmt->execute()) {
            sendResponse(true, 'Product type deleted successfully');
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