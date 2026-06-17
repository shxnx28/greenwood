<?php
require_once __DIR__ . '/auth_check.php'; 
require_once 'db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch($method) {
    case 'GET':
        $prices = [];
        $sql = "
            SELECT 
                pr.price_id,
                pr.variant_id,
                pr.price_type,
                pr.min_quantity,
                pr.price,
                pr.created_at,
                pv.sku,
                p.product_name,
                c.color_name,
                s.size_name
            FROM price pr
            INNER JOIN product_variant pv ON pr.variant_id = pv.variant_id
            INNER JOIN product p ON pv.product_id = p.product_id
            INNER JOIN color c ON pv.color_id = c.color_id
            INNER JOIN size s ON pv.size_id = s.size_id
            ORDER BY pr.variant_id, pr.price_type, pr.min_quantity
        ";
        $result = $conn->query($sql);
        
        if ($result) {
            while($row = $result->fetch_assoc()) {
                $prices[] = $row;
            }
            sendResponse(true, 'Prices retrieved successfully', $prices);
        } else {
            sendResponse(false, 'Error: ' . $conn->error);
        }
        break;

    case 'POST':
        $variant_id   = intval($input['variant_id']);
        $price_type   = sanitize($conn, $input['price_type']);
        $min_quantity = intval($input['min_quantity'] ?? 1);
        $price        = floatval($input['price']);

        if ($variant_id <= 0) {
            sendResponse(false, 'Invalid variant selected');
            break;
        }

        // Verify variant exists
        $check = $conn->query("SELECT 1 FROM product_variant WHERE variant_id = $variant_id");
        if ($check->num_rows === 0) {
            sendResponse(false, 'Variant not found');
            break;
        }

        // Check for duplicate
        $dup = $conn->query("SELECT 1 FROM price WHERE variant_id=$variant_id AND price_type='$price_type' AND min_quantity=$min_quantity");
        if ($dup->num_rows > 0) {
            sendResponse(false, 'A price with this variant, type and quantity already exists');
            break;
        }

        $sql = "INSERT INTO price (variant_id, price_type, min_quantity, price) 
                VALUES ($variant_id, '$price_type', $min_quantity, $price)";

        if ($conn->query($sql)) {
            sendResponse(true, 'Price added successfully', ['price_id' => $conn->insert_id]);
        } else {
            sendResponse(false, 'Error: ' . $conn->error);
        }
        break;

    case 'PUT':
        $price_id     = intval($input['price_id']);
        $variant_id   = intval($input['variant_id']);
        $price_type   = sanitize($conn, $input['price_type']);
        $min_quantity = intval($input['min_quantity'] ?? 1);
        $price        = floatval($input['price']);

        if ($price_id <= 0) {
            sendResponse(false, 'Invalid price ID');
            break;
        }

        // Check for duplicate on another record
        $dup = $conn->query("SELECT 1 FROM price WHERE variant_id=$variant_id AND price_type='$price_type' AND min_quantity=$min_quantity AND price_id != $price_id");
        if ($dup->num_rows > 0) {
            sendResponse(false, 'A price with this variant, type and quantity already exists');
            break;
        }

        $sql = "UPDATE price 
                SET variant_id=$variant_id, price_type='$price_type', 
                    min_quantity=$min_quantity, price=$price 
                WHERE price_id=$price_id";

        if ($conn->query($sql)) {
            sendResponse(true, 'Price updated successfully');
        } else {
            sendResponse(false, 'Error: ' . $conn->error);
        }
        break;

    case 'DELETE':
        $price_id = intval($input['price_id']);

        if ($price_id <= 0) {
            sendResponse(false, 'Invalid price ID');
            break;
        }

        $sql = "DELETE FROM price WHERE price_id=$price_id";

        if ($conn->query($sql)) {
            sendResponse(true, 'Price deleted successfully');
        } else {
            sendResponse(false, 'Error: ' . $conn->error);
        }
        break;

    default:
        sendResponse(false, 'Invalid request method');
        break;
}
?>