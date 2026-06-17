<?php
require_once __DIR__ . '/auth_check.php'; 
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch($method) {

    case 'GET':
        $products = [];
        $sql = "SELECT p.product_id, p.product_name, p.description, p.image_path, p.banner_image,
                       p.created_at, p.updated_at,
                       c.category_id, c.category_name, c.slug,
                       pt.product_type_id, pt.product_type_name
                FROM product p
                LEFT JOIN category c ON p.category_id = c.category_id
                LEFT JOIN product_type pt ON p.product_type_id = pt.product_type_id
                ORDER BY p.product_id DESC";
        $result = $conn->query($sql);
        if ($result) {
            while($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
            sendResponse(true, 'Products retrieved successfully', $products);
        } else {
            sendResponse(false, 'Error: ' . $conn->error);
        }
        break;

    case 'POST':
        $product_name    = sanitize($conn, $input['product_name']);
        $description     = isset($input['description']) ? sanitize($conn, $input['description']) : null;
        $category_id     = isset($input['category_id']) ? intval($input['category_id']) : null;
        $product_type_id = isset($input['product_type_id']) ? intval($input['product_type_id']) : null;
        $image_path      = isset($input['image_path']) ? sanitize($conn, $input['image_path']) : null;
        $banner_image    = isset($input['banner_image']) ? sanitize($conn, $input['banner_image']) : null;

        $stmt = $conn->prepare("INSERT INTO product (product_name, description, category_id, product_type_id, image_path, banner_image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiiss", $product_name, $description, $category_id, $product_type_id, $image_path, $banner_image);
        
        if ($stmt->execute()) {
            sendResponse(true, 'Product added successfully', ['product_id' => $conn->insert_id]);
        } else {
            sendResponse(false, 'Error: ' . $stmt->error);
        }
        $stmt->close();
        break;

    case 'PUT':
        $product_id      = intval($input['product_id']);
        $product_name    = sanitize($conn, $input['product_name']);
        $description     = isset($input['description']) ? sanitize($conn, $input['description']) : null;
        $category_id     = isset($input['category_id']) ? intval($input['category_id']) : null;
        $product_type_id = isset($input['product_type_id']) ? intval($input['product_type_id']) : null;
        $image_path      = isset($input['image_path']) ? sanitize($conn, $input['image_path']) : null;
        $banner_image    = isset($input['banner_image']) ? sanitize($conn, $input['banner_image']) : null;

        $stmt = $conn->prepare("UPDATE product
                                SET product_name=?, description=?, category_id=?,
                                    product_type_id=?, image_path=?, banner_image=?,
                                    updated_at=current_timestamp()
                                WHERE product_id=?");
        $stmt->bind_param("ssiissi", $product_name, $description, $category_id, $product_type_id, $image_path, $banner_image, $product_id);
        
        if ($stmt->execute()) {
            sendResponse(true, 'Product updated successfully');
        } else {
            sendResponse(false, 'Error: ' . $stmt->error);
        }
        $stmt->close();
        break;

    case 'DELETE':
        $product_id = intval($input['product_id']);
        
        $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM product_variant WHERE product_id = ?");
        $check_stmt->bind_param("i", $product_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();
        $check_stmt->close();
        
        if ($check_row['count'] > 0) {
            sendResponse(false, 'Cannot delete product. It has ' . $check_row['count'] . ' variant(s). Delete them first.');
        } else {
            $del_stmt = $conn->prepare("DELETE FROM product WHERE product_id=?");
            $del_stmt->bind_param("i", $product_id);
            
            if ($del_stmt->execute()) {
                sendResponse(true, 'Product deleted successfully');
            } else {
                sendResponse(false, 'Error: ' . $del_stmt->error);
            }
            $del_stmt->close();
        }
        break;

    default:
        sendResponse(false, 'Invalid request method');
        break;
}

$conn->close();
?>