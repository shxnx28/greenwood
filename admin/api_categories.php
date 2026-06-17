<?php
require_once __DIR__ . '/auth_check.php'; 
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $sql = "
            SELECT 
                category_id,
                category_name,
                slug,
                description,
                created_at
            FROM category
            ORDER BY category_name ASC
        ";
        $result = $conn->query($sql);
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        sendResponse(true, '', $categories);
    }

    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input) {
        sendResponse(false, 'Invalid JSON input');
    }

    if ($method === 'POST') {
        if (empty($input['category_name'])) {
            sendResponse(false, 'Category name is required');
        }

        $category_name = sanitize($conn, $input['category_name']);
        $slug = !empty($input['slug'])
            ? sanitize($conn, $input['slug'])
            : strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $category_name), '-'));
        $description = isset($input['description'])
            ? sanitize($conn, $input['description'])
            : null;

        $stmt = $conn->prepare("
            INSERT INTO category (category_name, slug, description)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("sss", $category_name, $slug, $description);
        $stmt->execute();

        sendResponse(true, 'Category created successfully');
    }

    if ($method === 'PUT') {
        if (empty($input['category_id']) || empty($input['category_name'])) {
            sendResponse(false, 'Category ID and name are required');
        }

        $category_id = (int)$input['category_id'];
        $category_name = sanitize($conn, $input['category_name']);
        $slug = !empty($input['slug'])
            ? sanitize($conn, $input['slug'])
            : strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $category_name), '-'));
        $description = isset($input['description'])
            ? sanitize($conn, $input['description'])
            : null;

        $stmt = $conn->prepare("
            UPDATE category
            SET category_name = ?, slug = ?, description = ?
            WHERE category_id = ?
        ");
        $stmt->bind_param("sssi", $category_name, $slug, $description, $category_id);
        $stmt->execute();

        sendResponse(true, 'Category updated successfully');
    }

    if ($method === 'DELETE') {
        if (empty($input['category_id'])) {
            sendResponse(false, 'Category ID is required');
        }

        $category_id = (int)$input['category_id'];

        $stmt = $conn->prepare("DELETE FROM category WHERE category_id = ?");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();

        sendResponse(true, 'Category deleted successfully');
    }

    sendResponse(false, 'Method not allowed');

} catch (Exception $e) {
    sendResponse(false, $e->getMessage());
}
