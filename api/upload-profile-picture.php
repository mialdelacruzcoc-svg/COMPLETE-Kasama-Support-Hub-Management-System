<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['profile_picture'];

// Validate file size (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB.']);
    exit;
}

// Validate MIME type
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime_type = $finfo->file($file['tmp_name']);

if (!in_array($mime_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.']);
    exit;
}

// Validate it's actually an image
$image_info = getimagesize($file['tmp_name']);
if ($image_info === false) {
    echo json_encode(['success' => false, 'message' => 'File is not a valid image.']);
    exit;
}

// Generate unique filename
$ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
$ext = $ext_map[$mime_type] ?? 'jpg';
$filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
$upload_dir = __DIR__ . '/../uploads/profiles/';
$upload_path = $upload_dir . $filename;

// Create directory if it doesn't exist
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Delete old profile picture if exists
$stmt = mysqli_prepare($conn, "SELECT profile_picture FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$current = mysqli_fetch_assoc($result);

if (!empty($current['profile_picture'])) {
    $old_path = $upload_dir . $current['profile_picture'];
    if (file_exists($old_path)) {
        unlink($old_path);
    }
}

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file. Please try again.']);
    exit;
}

// Update database
$stmt = mysqli_prepare($conn, "UPDATE users SET profile_picture = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, "si", $filename, $user_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => true,
        'message' => 'Profile picture updated successfully',
        'filename' => $filename
    ]);
} else {
    // Clean up uploaded file on DB failure
    if (file_exists($upload_path)) {
        unlink($upload_path);
    }
    echo json_encode(['success' => false, 'message' => 'Failed to update profile. Please try again.']);
}

mysqli_close($conn);
