<?php
require_once __DIR__ . '/auth_check.php'; 
require_once 'db.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

try {
    switch ($method) {

        case 'GET':
            $query  = "SELECT * FROM upcoming_branch ORDER BY display_order ASC, upcoming_id ASC";
            $result = $conn->query($query);

            if ($result) {
                $branches = [];
                while ($row = $result->fetch_assoc()) {
                    $branches[] = $row;
                }
                sendResponse(true, '', $branches);
            } else {
                sendResponse(false, 'Error fetching branches: ' . $conn->error);
            }
            break;

        case 'POST':
            // FIX: validate required fields
            if (empty($input['branch_name'])) {
                sendResponse(false, 'branch_name is required');
            }

            $branch_name       = $input['branch_name']       ?? '';
            $city              = $input['city']              ?? '';
            $province          = $input['province']          ?? '';
            $estimated_opening = $input['estimated_opening'] ?? null;
            $status            = $input['status']            ?? 'planned';
            $description       = $input['description']       ?? null;
            $icon              = $input['icon']              ?? null;
            // FIX: intval('') = 0 — guard empty strings on integer fields
            $is_active     = isset($input['is_active'])     && $input['is_active']     !== '' ? intval($input['is_active'])     : 1;
            $display_order = isset($input['display_order']) && $input['display_order'] !== '' ? intval($input['display_order']) : 0;

            $stmt = $conn->prepare("
                INSERT INTO upcoming_branch
                    (branch_name, city, province, estimated_opening, status, description, icon, is_active, display_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            // FIX: correct type string — all strings first, then two ints at end
            // branch_name(s), city(s), province(s), estimated_opening(s), status(s), description(s), icon(s), is_active(i), display_order(i)
            $stmt->bind_param(
                "sssssssii",
                $branch_name,
                $city,
                $province,
                $estimated_opening,
                $status,
                $description,
                $icon,
                $is_active,
                $display_order
            );

            if ($stmt->execute()) {
                sendResponse(true, 'Upcoming branch added successfully', ['upcoming_id' => $conn->insert_id]);
            } else {
                sendResponse(false, 'Error adding branch: ' . $stmt->error);
            }
            $stmt->close();
            break;

        case 'PUT':
            // FIX: validate upcoming_id is present and not empty
            if (empty($input['upcoming_id'])) {
                sendResponse(false, 'upcoming_id is required');
            }

            // FIX: upcoming_id must be cast to int — was typed as "s" (string) in the original bind_param
            $upcoming_id = intval($input['upcoming_id']);
            if ($upcoming_id <= 0) {
                sendResponse(false, 'Invalid upcoming_id');
            }

            $branch_name       = $input['branch_name']       ?? '';
            $city              = $input['city']              ?? '';
            $province          = $input['province']          ?? '';
            $estimated_opening = $input['estimated_opening'] ?? null;
            $status            = $input['status']            ?? 'planned';
            $description       = $input['description']       ?? null;
            $icon              = $input['icon']              ?? null;
            // FIX: same empty-string guard as POST
            $is_active     = isset($input['is_active'])     && $input['is_active']     !== '' ? intval($input['is_active'])     : 1;
            $display_order = isset($input['display_order']) && $input['display_order'] !== '' ? intval($input['display_order']) : 0;

            $stmt = $conn->prepare("
                UPDATE upcoming_branch SET
                    branch_name       = ?,
                    city              = ?,
                    province          = ?,
                    estimated_opening = ?,
                    status            = ?,
                    description       = ?,
                    icon              = ?,
                    is_active         = ?,
                    display_order     = ?
                WHERE upcoming_id = ?
            ");

            // FIX: correct type string — was "sssssssiis" (upcoming_id typed as "s")
            // branch_name(s), city(s), province(s), estimated_opening(s), status(s),
            // description(s), icon(s), is_active(i), display_order(i), upcoming_id(i)
            $stmt->bind_param(
                "sssssssiii",
                $branch_name,
                $city,
                $province,
                $estimated_opening,
                $status,
                $description,
                $icon,
                $is_active,
                $display_order,
                $upcoming_id
            );

            if ($stmt->execute()) {
                sendResponse(true, 'Upcoming branch updated successfully');
            } else {
                sendResponse(false, 'Error updating branch: ' . $stmt->error);
            }
            $stmt->close();
            break;

        case 'DELETE':
            // FIX: validate upcoming_id before casting
            if (empty($input['upcoming_id'])) {
                sendResponse(false, 'upcoming_id is required');
            }

            $upcoming_id = intval($input['upcoming_id']);
            if ($upcoming_id <= 0) {
                sendResponse(false, 'Invalid upcoming_id');
            }

            $stmt = $conn->prepare("DELETE FROM upcoming_branch WHERE upcoming_id = ?");
            $stmt->bind_param("i", $upcoming_id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    sendResponse(true, 'Upcoming branch deleted successfully');
                } else {
                    sendResponse(false, 'No branch found with that ID');
                }
            } else {
                sendResponse(false, 'Error deleting branch: ' . $stmt->error);
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