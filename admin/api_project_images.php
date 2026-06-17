<?php
require_once __DIR__ . '/auth_check.php'; 
require_once 'db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch($method) {

    case 'GET':
        $projects = [];
        $sql = "SELECT * FROM project_images ORDER BY display_order ASC, uploaded_at DESC";
        $result = $conn->query($sql);
        if ($result) {
            while($row = $result->fetch_assoc()) {
                $projects[] = $row;
            }
            sendResponse(true, 'Projects retrieved successfully', $projects);
        } else {
            sendResponse(false, 'Error: ' . $conn->error);
        }
        break;

    case 'POST':
        $title = sanitize($conn, $input['title']);
        $description = isset($input['description']) ? sanitize($conn, $input['description']) : null;
        $category = sanitize($conn, $input['category']);
        $location = isset($input['location']) ? sanitize($conn, $input['location']) : null;
        $city = isset($input['city']) ? sanitize($conn, $input['city']) : null;
        $year = isset($input['year']) ? intval($input['year']) : null;
        $image_filename = isset($input['image_filename']) ? sanitize($conn, $input['image_filename']) : null;
        $image_path = isset($input['image_path']) ? sanitize($conn, $input['image_path']) : null;
        $products_used = isset($input['products_used']) ? sanitize($conn, $input['products_used']) : null;
        $is_featured = isset($input['is_featured']) ? (int)$input['is_featured'] : 0;
        $display_order = isset($input['display_order']) ? intval($input['display_order']) : 0;
        $album = isset($input['album']) ? sanitize($conn, $input['album']) : null;

        $stmt = $conn->prepare("INSERT INTO project_images
            (title, album, description, category, location, city, year,
             image_path, image_filename, products_used, is_featured, display_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("ssssssssssii",
            $title, $album, $description, $category, $location, $city, $year,
            $image_path, $image_filename, $products_used, $is_featured, $display_order);

        if ($stmt->execute()) {
            sendResponse(true, 'Project added successfully', ['id' => $stmt->insert_id]);
        } else {
            sendResponse(false, 'Error: ' . $stmt->error);
        }
        $stmt->close();
        break;

    case 'PUT':
        $id = intval($input['id']);
        $title = sanitize($conn, $input['title']);
        $album = isset($input['album']) ? sanitize($conn, $input['album']) : null;
        $description = isset($input['description']) ? sanitize($conn, $input['description']) : null;
        $category = sanitize($conn, $input['category']);
        $location = isset($input['location']) ? sanitize($conn, $input['location']) : null;
        $city = isset($input['city']) ? sanitize($conn, $input['city']) : null;
        $year = isset($input['year']) ? intval($input['year']) : null;
        $image_filename = isset($input['image_filename']) ? sanitize($conn, $input['image_filename']) : null;
        $image_path = isset($input['image_path']) ? sanitize($conn, $input['image_path']) : null;
        $products_used = isset($input['products_used']) ? sanitize($conn, $input['products_used']) : null;
        $is_featured = isset($input['is_featured']) ? (int)$input['is_featured'] : 0;
        $display_order = isset($input['display_order']) ? intval($input['display_order']) : 0;

        $sql = "UPDATE project_images SET 
                title='$title',
                album=" . ($album ? "'$album'" : "NULL") . ",
                description=" . ($description ? "'$description'" : "NULL") . ",
                category='$category',
                location=" . ($location ? "'$location'" : "NULL") . ",
                city=" . ($city ? "'$city'" : "NULL") . ",
                year=" . ($year ?: "NULL") . ",
                image_filename=" . ($image_filename ? "'$image_filename'" : "NULL") . ",
                image_path=" . ($image_path ? "'$image_path'" : "NULL") . ",
                products_used=" . ($products_used ? "'$products_used'" : "NULL") . ",
                is_featured=$is_featured,
                display_order=$display_order
                WHERE id=$id";
        
        if ($conn->query($sql)) {
            sendResponse(true, 'Project updated successfully');
        } else {
            sendResponse(false, 'Error: ' . $conn->error);
        }
        break;

    case 'DELETE':
        $id = intval($input['id']);
        
        // Get image path before deleting
        $get_sql = "SELECT image_path FROM project_images WHERE id=$id";
        $result = $conn->query($get_sql);
        if ($result && $row = $result->fetch_assoc()) {
            $image_path = $row['image_path'];
            
            // Delete from database
            $sql = "DELETE FROM project_images WHERE id=$id";
            if ($conn->query($sql)) {
                // Try to delete the physical file
                if ($image_path && file_exists('../' . $image_path)) {
                    @unlink('../' . $image_path);
                }
                sendResponse(true, 'Project deleted successfully');
            } else {
                sendResponse(false, 'Error: ' . $conn->error);
            }
        } else {
            sendResponse(false, 'Project not found');
        }
        break;

    default:
        sendResponse(false, 'Invalid request method');
        break;
}
?>