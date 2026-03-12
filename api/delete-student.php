<?php
require_once 'config.php';
header('Content-Type: application/json');

// Must be logged in as coach
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;

if ($student_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
    exit;
}

// Verify the user is actually a student
$stmt = mysqli_prepare($conn, "SELECT id, profile_picture, student_id FROM users WHERE id = ? AND role = 'student'");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);

if (!$student) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

$sid = $student['student_id'];

// Delete profile picture file if exists
if (!empty($student['profile_picture'])) {
    $pic_path = __DIR__ . '/../uploads/profiles/' . $student['profile_picture'];
    if (file_exists($pic_path)) {
        unlink($pic_path);
    }
}

// Delete related concern attachments
$att_query = mysqli_prepare($conn, "SELECT file_path FROM concern_attachments WHERE concern_id IN (SELECT id FROM concerns WHERE student_id = ?)");
if ($att_query) {
    mysqli_stmt_bind_param($att_query, "s", $sid);
    mysqli_stmt_execute($att_query);
    $att_result = mysqli_stmt_get_result($att_query);
    while ($att = mysqli_fetch_assoc($att_result)) {
        $att_path = __DIR__ . '/../' . $att['file_path'];
        if (file_exists($att_path)) {
            unlink($att_path);
        }
    }
}

// Delete related records (order matters for foreign keys)
// Delete concern responses
$stmt1 = mysqli_prepare($conn, "DELETE FROM concern_responses WHERE concern_id IN (SELECT id FROM concerns WHERE student_id = ?)");
if ($stmt1) {
    mysqli_stmt_bind_param($stmt1, "s", $sid);
    mysqli_stmt_execute($stmt1);
}

// Delete concern attachments
$stmt2 = mysqli_prepare($conn, "DELETE FROM concern_attachments WHERE concern_id IN (SELECT id FROM concerns WHERE student_id = ?)");
if ($stmt2) {
    mysqli_stmt_bind_param($stmt2, "s", $sid);
    mysqli_stmt_execute($stmt2);
}

// Delete concerns
$stmt3 = mysqli_prepare($conn, "DELETE FROM concerns WHERE student_id = ?");
if ($stmt3) {
    mysqli_stmt_bind_param($stmt3, "s", $sid);
    mysqli_stmt_execute($stmt3);
}

// Delete appointments
$stmt4 = mysqli_prepare($conn, "DELETE FROM appointments WHERE student_id = ?");
if ($stmt4) {
    mysqli_stmt_bind_param($stmt4, "s", $sid);
    mysqli_stmt_execute($stmt4);
}

// Delete notifications
$stmt5 = mysqli_prepare($conn, "DELETE FROM notifications WHERE user_id = ?");
if ($stmt5) {
    mysqli_stmt_bind_param($stmt5, "i", $student_id);
    mysqli_stmt_execute($stmt5);
}

// Finally delete the user
$stmt6 = mysqli_prepare($conn, "DELETE FROM users WHERE id = ? AND role = 'student'");
mysqli_stmt_bind_param($stmt6, "i", $student_id);

if (mysqli_stmt_execute($stmt6) && mysqli_stmt_affected_rows($stmt6) > 0) {
    echo json_encode(['success' => true, 'message' => 'Student has been removed successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove student. Please try again.']);
}

mysqli_close($conn);
