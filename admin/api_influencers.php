<?php
require_once __DIR__ . '/auth_check.php'; 
require_once 'db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$input = [];
if (empty($_FILES)) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
}

// Handle file upload (same pattern as api_project_images.php)
if (isset($_FILES['file'])) {
    $uploadDir = 'uploads/influencers/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file = $_FILES['file'];

    // Use finfo for real MIME detection — never trust client-supplied $_FILES['type']
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, $allowedTypes)) {
        sendResponse(false, 'Invalid file type');
    }

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'inf_' . time() . '_' . rand(100, 999) . '.' . $ext;
    $destPath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        // Store full relative path — same as projects: "uploads/influencers/filename.jpg"
        sendResponse(true, 'File uploaded successfully', ['path' => $destPath]);
    } else {
        sendResponse(false, 'Failed to upload file');
    }
    exit;
}

switch ($method) {

    case 'GET':
        $influencers = [];
        $result = $conn->query("SELECT * FROM influencer_reactions ORDER BY created_at DESC");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $influencers[] = $row;
            }
            sendResponse(true, 'Influencers retrieved successfully', $influencers);
        } else {
            sendResponse(false, 'Error: ' . $conn->error);
        }
        break;

    case 'POST':
        $name          = trim($input['name'] ?? '');
        $platform      = trim($input['platform'] ?? 'Facebook');
        $description   = isset($input['description'])   ? trim($input['description'])   : null;
        $reaction_url  = isset($input['reaction_url'])  ? trim($input['reaction_url'])  : null;
        // profile_photo stores full relative path e.g. "uploads/influencers/inf_xxx.jpg"
        $profile_photo = isset($input['profile_photo']) ? trim($input['profile_photo']) : null;

        if (empty($name)) sendResponse(false, 'Name is required');

        $stmt = $conn->prepare("INSERT INTO influencer_reactions (name, platform, description, reaction_url, profile_photo) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('sssss', $name, $platform, $description, $reaction_url, $profile_photo);

        if ($stmt->execute()) {
            sendResponse(true, 'Influencer added successfully', ['id' => $conn->insert_id]);
        } else {
            sendResponse(false, 'Error: ' . $stmt->error);
        }
        $stmt->close();
        break;

    case 'PUT':
        $id            = intval($input['id'] ?? 0);
        $name          = trim($input['name'] ?? '');
        $platform      = trim($input['platform'] ?? 'Facebook');
        $description   = isset($input['description'])   ? trim($input['description'])   : null;
        $reaction_url  = isset($input['reaction_url'])  ? trim($input['reaction_url'])  : null;
        $profile_photo = isset($input['profile_photo']) ? trim($input['profile_photo']) : null;

        if (empty($id) || empty($name)) sendResponse(false, 'ID and Name are required');

        $stmt = $conn->prepare("UPDATE influencer_reactions SET name=?, platform=?, description=?, reaction_url=?, profile_photo=? WHERE id=?");
        $stmt->bind_param('sssssi', $name, $platform, $description, $reaction_url, $profile_photo, $id);

        if ($stmt->execute()) {
            sendResponse(true, 'Influencer updated successfully');
        } else {
            sendResponse(false, 'Error: ' . $stmt->error);
        }
        $stmt->close();
        break;

    case 'DELETE':
        $id = intval($input['id'] ?? 0);
        if (empty($id)) sendResponse(false, 'ID is required');

        // Get image path before deleting so we can remove the file too
        $res = $conn->query("SELECT profile_photo FROM influencer_reactions WHERE id=$id");
        if ($res && $row = $res->fetch_assoc()) {
            $stmt = $conn->prepare("DELETE FROM influencer_reactions WHERE id=?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                // Delete physical file if it exists
                if ($row['profile_photo'] && file_exists('../' . $row['profile_photo'])) {
                    @unlink('../' . $row['profile_photo']);
                }
                sendResponse(true, 'Influencer deleted successfully');
            } else {
                sendResponse(false, 'Error: ' . $stmt->error);
            }
            $stmt->close();
        } else {
            sendResponse(false, 'Influencer not found');
        }
        break;

    default:
        sendResponse(false, 'Invalid request method');
        break;
}

$conn->close();