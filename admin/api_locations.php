<?php
require_once __DIR__ . '/auth_check.php'; 
require_once 'db.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Handle preflight requests
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get input data for POST/PUT/DELETE
$input = json_decode(file_get_contents('php://input'), true);

try {
    switch ($method) {
        case 'GET':
            // Fetch all warehouse locations
            $query = "SELECT * FROM warehouse_location ORDER BY display_order ASC, location_id ASC";
            $result = $conn->query($query);
            
            if ($result) {
                $locations = [];
                while ($row = $result->fetch_assoc()) {
                    $locations[] = $row;
                }
                sendResponse(true, '', $locations);
            } else {
                sendResponse(false, 'Error fetching locations: ' . $conn->error);
            }
            break;
            
        case 'POST':
            // Create new warehouse location
            $stmt = $conn->prepare("
                INSERT INTO warehouse_location (
                    location_name, address_line1, address_line2, address_line3,
                    city, province, postal_code, facebook_url, contact_number,
                    email, google_maps_url, latitude, longitude, operating_hours,
                    special_note, is_active, display_order
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param(
                "sssssssssssddssii",
                $input['location_name'],
                $input['address_line1'],
                $input['address_line2'],
                $input['address_line3'],
                $input['city'],
                $input['province'],
                $input['postal_code'],
                $input['facebook_url'],
                $input['contact_number'],
                $input['email'],
                $input['google_maps_url'],
                $input['latitude'],
                $input['longitude'],
                $input['operating_hours'],
                $input['special_note'],
                $input['is_active'],
                $input['display_order']
            );
            
            if ($stmt->execute()) {
                sendResponse(true, 'Warehouse location added successfully', ['location_id' => $conn->insert_id]);
            } else {
                sendResponse(false, 'Error adding location: ' . $stmt->error);
            }
            
            $stmt->close();
            break;
            
        case 'PUT':
            // Update warehouse location
            $stmt = $conn->prepare("
                UPDATE warehouse_location SET
                    location_name = ?,
                    address_line1 = ?,
                    address_line2 = ?,
                    address_line3 = ?,
                    city = ?,
                    province = ?,
                    postal_code = ?,
                    facebook_url = ?,
                    contact_number = ?,
                    email = ?,
                    google_maps_url = ?,
                    latitude = ?,
                    longitude = ?,
                    operating_hours = ?,
                    special_note = ?,
                    is_active = ?,
                    display_order = ?
                WHERE location_id = ?
            ");
            
            $stmt->bind_param(
                "sssssssssssddssiii",
                $input['location_name'],
                $input['address_line1'],
                $input['address_line2'],
                $input['address_line3'],
                $input['city'],
                $input['province'],
                $input['postal_code'],
                $input['facebook_url'],
                $input['contact_number'],
                $input['email'],
                $input['google_maps_url'],
                $input['latitude'],
                $input['longitude'],
                $input['operating_hours'],
                $input['special_note'],
                $input['is_active'],
                $input['display_order'],
                $input['location_id']
            );
            
            if ($stmt->execute()) {
                sendResponse(true, 'Warehouse location updated successfully');
            } else {
                sendResponse(false, 'Error updating location: ' . $stmt->error);
            }
            
            $stmt->close();
            break;
            
        case 'DELETE':
            // Delete warehouse location
            $stmt = $conn->prepare("DELETE FROM warehouse_location WHERE location_id = ?");
            $stmt->bind_param("i", $input['location_id']);
            
            if ($stmt->execute()) {
                sendResponse(true, 'Warehouse location deleted successfully');
            } else {
                sendResponse(false, 'Error deleting location: ' . $stmt->error);
            }
            
            $stmt->close();
            break;
            
        default:
            http_response_code(405);
            sendResponse(false, 'Method not allowed');
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    sendResponse(false, 'Server error: ' . $e->getMessage());
}

$conn->close();
?>