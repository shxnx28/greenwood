<?php
require_once 'auth_check.php';
if (!isset($conn) || $conn === null) {
    require_once 'db.php';
}

// ── Secure Image Upload ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_image') {
    header('Content-Type: application/json');

    if (!isset($_FILES['image'])) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded.']); exit;
    }
    $file = $_FILES['image'];

    // 1. PHP upload error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errs = [1=>'Exceeds server limit.',2=>'Exceeds form limit.',3=>'Partial upload.',
                 4=>'No file sent.',6=>'No temp folder.',7=>'Cannot write to disk.',8=>'Blocked by extension.'];
        echo json_encode(['success'=>false,'message'=>$errs[$file['error']] ?? 'Upload error.']); exit;
    }

    // 2. Size limit 5 MB
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success'=>false,'message'=>'File too large. Max 5 MB.']); exit;
    }

    // 3. Validate MIME via finfo — never trust $_FILES['type']
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
    $finfo   = finfo_open(FILEINFO_MIME_TYPE);
    $mime    = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!array_key_exists($mime, $allowed)) {
        echo json_encode(['success'=>false,'message'=>'Invalid type. JPEG, PNG, GIF, WebP only.']); exit;
    }

    // 4. Safe server-generated filename — zero user input
    $ext      = $allowed[$mime];
    $filename = 'ann_' . uniqid('', true) . '.' . $ext;

    // 5. Ensure upload dir exists; drop .htaccess to block PHP execution
    $uploadDir = __DIR__ . '/uploads/announcements/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        file_put_contents($uploadDir . '.htaccess',
            "Options -Indexes\n<FilesMatch \"\\.php$\">\n    Order allow,deny\n    Deny from all\n</FilesMatch>\n");
    }

    // 6. Move from temp — never copy
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        echo json_encode(['success'=>false,'message'=>'Could not save. Check folder permissions (755 on uploads/announcements/).']); exit;
    }

    echo json_encode(['success'=>true,'path'=>'/admin/uploads/announcements/'.$filename]);
    exit;
}

// ── Helpers ────────────────────────────────────────────────────────────────────
header('Content-Type: application/json');

/**
 * Bind params safely for PHP 7 — bind_param requires variables passed by reference,
 * nullable strings must still be typed 's' (not 'n') in MySQLi.
 */
function bindAndExecute($stmt, string $types, array $vals): bool {
    $refs = [];
    foreach ($vals as $i => $v) $refs[$i] = &$vals[$i];
    array_unshift($refs, $types);
    call_user_func_array([$stmt, 'bind_param'], $refs);
    return $stmt->execute();
}

/**
 * Convert datetime-local value ("2026-04-27T08:49") to MySQL datetime ("2026-04-27 08:49:00").
 * Returns null if empty.
 */
function toMysql(?string $v): ?string {
    if (!$v) return null;
    $v = str_replace('T', ' ', $v);
    if (strlen($v) === 16) $v .= ':00'; // add seconds if missing
    return $v;
}

$method = $_SERVER['REQUEST_METHOD'];

// ── GET: list all ─────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $result = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
    $rows = [];
    if ($result) while ($row = $result->fetch_assoc()) $rows[] = $row;
    sendResponse(true, '', $rows);
}

// ── POST: create ──────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $d      = json_decode(file_get_contents('php://input'), true) ?? [];
    $title  = trim($d['title']   ?? '');
    $msg    = trim($d['message'] ?? '');
    if (!$title || !$msg) sendResponse(false, 'Title and message are required.');

    $img    = trim($d['image_url']    ?? '') ?: null;
    $bl     = trim($d['button_label'] ?? '') ?: null;
    $bu     = trim($d['button_url']   ?? '') ?: null;
    $bg     = trim($d['bg_color']     ?? '#ffffff');
    $active = (int)($d['is_active']     ?? 1);
    $once   = (int)($d['show_once']     ?? 0);
    $delay  = (int)($d['display_delay'] ?? 1000);
    $ss     = toMysql($d['schedule_start'] ?? null);
    $se     = toMysql($d['schedule_end']   ?? null);

    $stmt = $conn->prepare(
        "INSERT INTO announcements
         (title, message, image_url, button_label, button_url, bg_color,
          is_active, show_once, display_delay, schedule_start, schedule_end)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) sendResponse(false, 'Prepare failed: ' . $conn->error);

    // types: s s s s s s i i i s s  (11 params)
    if (bindAndExecute($stmt, 'ssssssiiiss', [$title,$msg,$img,$bl,$bu,$bg,$active,$once,$delay,$ss,$se])) {
        sendResponse(true, 'Announcement created.', ['id' => $conn->insert_id]);
    } else {
        sendResponse(false, 'DB error: ' . $stmt->error);
    }
}

// ── PUT: update ───────────────────────────────────────────────────────────────
if ($method === 'PUT') {
    $d     = json_decode(file_get_contents('php://input'), true) ?? [];
    $id    = (int)($d['id'] ?? 0);
    $title = trim($d['title']   ?? '');
    $msg   = trim($d['message'] ?? '');
    if (!$id || !$title || !$msg) sendResponse(false, 'ID, title and message are required.');

    $img    = trim($d['image_url']    ?? '') ?: null;
    $bl     = trim($d['button_label'] ?? '') ?: null;
    $bu     = trim($d['button_url']   ?? '') ?: null;
    $bg     = trim($d['bg_color']     ?? '#ffffff');
    $active = (int)($d['is_active']     ?? 1);
    $once   = (int)($d['show_once']     ?? 0);
    $delay  = (int)($d['display_delay'] ?? 1000);
    $ss     = toMysql($d['schedule_start'] ?? null);
    $se     = toMysql($d['schedule_end']   ?? null);

    $stmt = $conn->prepare(
        "UPDATE announcements SET
         title=?, message=?, image_url=?, button_label=?, button_url=?, bg_color=?,
         is_active=?, show_once=?, display_delay=?, schedule_start=?, schedule_end=?
         WHERE id=?"
    );
    if (!$stmt) sendResponse(false, 'Prepare failed: ' . $conn->error);

    // types: s s s s s s i i i s s i  (12 params)
    if (bindAndExecute($stmt, 'ssssssiiissi', [$title,$msg,$img,$bl,$bu,$bg,$active,$once,$delay,$ss,$se,$id])) {
        sendResponse(true, 'Announcement updated.');
    } else {
        sendResponse(false, 'DB error: ' . $stmt->error);
    }
}

// ── DELETE ────────────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    $d   = json_decode(file_get_contents('php://input'), true) ?? [];
    $ids = array_filter(array_map('intval', (array)($d['ids'] ?? [])));
    if (empty($ids)) sendResponse(false, 'No IDs provided.');
    $in  = implode(',', $ids);

    // Delete uploaded images from disk before removing rows
    $ir = $conn->query("SELECT image_url FROM announcements WHERE id IN ($in)");
    if ($ir) while ($row = $ir->fetch_assoc()) {
        if (!empty($row['image_url']) && strpos($row['image_url'], '/admin/uploads/announcements/') === 0) {
            $lp = __DIR__ . '/uploads/announcements/' . basename($row['image_url']);
            if (file_exists($lp)) @unlink($lp);
        }
    }

    if ($conn->query("DELETE FROM announcements WHERE id IN ($in)")) {
        sendResponse(true, 'Deleted.');
    } else {
        sendResponse(false, 'DB error: ' . $conn->error);
    }
}

sendResponse(false, 'Invalid request method.');
