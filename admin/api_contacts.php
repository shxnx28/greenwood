<?php
require_once __DIR__ . '/auth_check.php'; 
require_once 'db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true);

switch($method) {

    case 'GET':
        $contacts = [];
        $sql = "SELECT bc.contact_id, bc.location_id, bc.contact_name, bc.contact_number, 
                       bc.contact_email, bc.contact_role, bc.is_primary, bc.display_order, 
                       bc.is_active, bc.created_at, bc.updated_at,
                       wl.location_name, wl.address_line1
                FROM branch_contacts bc
                LEFT JOIN warehouse_location wl ON bc.location_id = wl.location_id
                ORDER BY bc.location_id ASC, bc.display_order ASC, bc.is_primary DESC";
        $result = $conn->query($sql);
        if ($result) {
            while($row = $result->fetch_assoc()) {
                $contacts[] = $row;
            }
            sendResponse(true, 'Contacts retrieved successfully', $contacts);
        } else {
            sendResponse(false, 'Error: ' . $conn->error);
        }
        break;

    case 'POST':
        $location_id    = intval($input['location_id']);
        $contact_name   = sanitize($conn, $input['contact_name']);
        $contact_number = sanitize($conn, $input['contact_number']);
        $contact_email  = isset($input['contact_email']) && $input['contact_email'] !== '' ? sanitize($conn, $input['contact_email']) : null;
        $contact_role   = isset($input['contact_role'])  && $input['contact_role']  !== '' ? sanitize($conn, $input['contact_role'])  : null;
        $is_primary     = isset($input['is_primary'])    ? intval($input['is_primary'])    : 0;
        $display_order  = isset($input['display_order']) ? intval($input['display_order']) : 0;
        $is_active      = isset($input['is_active'])     ? intval($input['is_active'])     : 1;

        // Validate location exists
        $check = $conn->prepare("SELECT location_id FROM warehouse_location WHERE location_id = ?");
        $check->bind_param("i", $location_id);
        $check->execute();
        if ($check->get_result()->num_rows === 0) {
            $check->close();
            sendResponse(false, 'Invalid location ID');
        }
        $check->close();

        $stmt = $conn->prepare("INSERT INTO branch_contacts 
                (location_id, contact_name, contact_number, contact_email, contact_role, is_primary, display_order, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssiis", $location_id, $contact_name, $contact_number, $contact_email, $contact_role, $is_primary, $display_order, $is_active);

        if ($stmt->execute()) {
            sendResponse(true, 'Contact added successfully', ['contact_id' => $conn->insert_id]);
        } else {
            sendResponse(false, 'Error: ' . $stmt->error);
        }
        $stmt->close();
        break;

    case 'PUT':
        $contact_id     = intval($input['contact_id']);
        $location_id    = intval($input['location_id']);
        $contact_name   = sanitize($conn, $input['contact_name']);
        $contact_number = sanitize($conn, $input['contact_number']);
        $contact_email  = isset($input['contact_email']) && $input['contact_email'] !== '' ? sanitize($conn, $input['contact_email']) : null;
        $contact_role   = isset($input['contact_role'])  && $input['contact_role']  !== '' ? sanitize($conn, $input['contact_role'])  : null;
        $is_primary     = isset($input['is_primary'])    ? intval($input['is_primary'])    : 0;
        $display_order  = isset($input['display_order']) ? intval($input['display_order']) : 0;
        $is_active      = isset($input['is_active'])     ? intval($input['is_active'])     : 1;

        // Validate location exists
        $check = $conn->prepare("SELECT location_id FROM warehouse_location WHERE location_id = ?");
        $check->bind_param("i", $location_id);
        $check->execute();
        if ($check->get_result()->num_rows === 0) {
            $check->close();
            sendResponse(false, 'Invalid location ID');
        }
        $check->close();

        $stmt = $conn->prepare("UPDATE branch_contacts SET
                location_id     = ?,
                contact_name    = ?,
                contact_number  = ?,
                contact_email   = ?,
                contact_role    = ?,
                is_primary      = ?,
                display_order   = ?,
                is_active       = ?
                WHERE contact_id = ?");
        $stmt->bind_param("issssiisi", $location_id, $contact_name, $contact_number, $contact_email, $contact_role, $is_primary, $display_order, $is_active, $contact_id);

        if ($stmt->execute()) {
            sendResponse(true, 'Contact updated successfully');
        } else {
            sendResponse(false, 'Error: ' . $stmt->error);
        }
        $stmt->close();
        break;

    case 'DELETE':
        $contact_id = intval($input['contact_id']);
        $stmt = $conn->prepare("DELETE FROM branch_contacts WHERE contact_id = ?");
        $stmt->bind_param("i", $contact_id);
        if ($stmt->execute()) {
            sendResponse(true, 'Contact deleted successfully');
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