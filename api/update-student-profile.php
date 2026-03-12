<?php
require_once 'config.php';
header('Content-Type: application/json');

// Must be logged in as student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Collect and sanitize fields
$fields = [
    'last_name'      => trim($_POST['last_name'] ?? ''),
    'first_name'     => trim($_POST['first_name'] ?? ''),
    'middle_name'    => trim($_POST['middle_name'] ?? ''),
    'extension_name' => trim($_POST['extension_name'] ?? ''),
    'gender'         => trim($_POST['gender'] ?? ''),
    'date_of_birth'  => trim($_POST['date_of_birth'] ?? ''),
    'place_of_birth' => trim($_POST['place_of_birth'] ?? ''),
    'civil_status'   => trim($_POST['civil_status'] ?? ''),
    'citizenship'    => trim($_POST['citizenship'] ?? ''),
    'religion'       => trim($_POST['religion'] ?? ''),
    'mobile_number'  => trim($_POST['mobile_number'] ?? ''),
    'facebook_name'  => trim($_POST['facebook_name'] ?? ''),
    'facebook_link'  => trim($_POST['facebook_link'] ?? ''),
    'course'         => trim($_POST['course'] ?? ''),
    'campus'         => trim($_POST['campus'] ?? ''),
    'college'        => trim($_POST['college'] ?? ''),
    'address'        => trim($_POST['address'] ?? ''),
];

// Validation
if (empty($fields['last_name']) || strlen($fields['last_name']) < 2) {
    echo json_encode(['success' => false, 'message' => 'Last name is required (at least 2 characters)']);
    exit;
}
if (empty($fields['first_name']) || strlen($fields['first_name']) < 2) {
    echo json_encode(['success' => false, 'message' => 'First name is required (at least 2 characters)']);
    exit;
}
if (empty($fields['gender'])) {
    echo json_encode(['success' => false, 'message' => 'Please select a gender']);
    exit;
}
if (empty($fields['date_of_birth'])) {
    echo json_encode(['success' => false, 'message' => 'Date of birth is required']);
    exit;
}
if (empty($fields['citizenship'])) {
    echo json_encode(['success' => false, 'message' => 'Citizenship is required']);
    exit;
}
if (empty($fields['mobile_number'])) {
    echo json_encode(['success' => false, 'message' => 'Mobile number is required']);
    exit;
}

// Validate date format
$dob = $fields['date_of_birth'];
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob) || !strtotime($dob)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date of birth format']);
    exit;
}

// Validate mobile number (basic)
if (!preg_match('/^[0-9+\-\s]{7,20}$/', $fields['mobile_number'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid mobile number format']);
    exit;
}

// Validate facebook link if provided
if (!empty($fields['facebook_link'])) {
    if (!filter_var($fields['facebook_link'], FILTER_VALIDATE_URL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid Facebook link URL']);
        exit;
    }
}

// Validate allowed values for select fields
$allowed_genders = ['Male', 'Female', 'Other', 'Prefer not to say'];
if (!in_array($fields['gender'], $allowed_genders)) {
    echo json_encode(['success' => false, 'message' => 'Invalid gender selection']);
    exit;
}

$allowed_civil = ['', 'Single', 'Married', 'Widowed', 'Separated', 'Divorced'];
if (!in_array($fields['civil_status'], $allowed_civil)) {
    echo json_encode(['success' => false, 'message' => 'Invalid civil status selection']);
    exit;
}

$allowed_ext = ['', 'Jr.', 'Sr.', 'II', 'III', 'IV', 'V'];
if (!in_array($fields['extension_name'], $allowed_ext)) {
    echo json_encode(['success' => false, 'message' => 'Invalid extension name selection']);
    exit;
}

// Build the full name from parts
$full_name = $fields['first_name'];
if (!empty($fields['middle_name'])) {
    $full_name .= ' ' . $fields['middle_name'];
}
$full_name .= ' ' . $fields['last_name'];
if (!empty($fields['extension_name'])) {
    $full_name .= ' ' . $fields['extension_name'];
}

// Prepared statement update
$sql = "UPDATE users SET 
    name = ?, last_name = ?, first_name = ?, middle_name = ?, extension_name = ?,
    gender = ?, date_of_birth = ?, place_of_birth = ?, civil_status = ?,
    citizenship = ?, religion = ?, mobile_number = ?,
    facebook_name = ?, facebook_link = ?,
    course = ?, campus = ?, college = ?, address = ?
    WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

$dob_val = !empty($fields['date_of_birth']) ? $fields['date_of_birth'] : null;

mysqli_stmt_bind_param($stmt, "ssssssssssssssssssi",
    $full_name,
    $fields['last_name'],
    $fields['first_name'],
    $fields['middle_name'],
    $fields['extension_name'],
    $fields['gender'],
    $dob_val,
    $fields['place_of_birth'],
    $fields['civil_status'],
    $fields['citizenship'],
    $fields['religion'],
    $fields['mobile_number'],
    $fields['facebook_name'],
    $fields['facebook_link'],
    $fields['course'],
    $fields['campus'],
    $fields['college'],
    $fields['address'],
    $user_id
);

if (mysqli_stmt_execute($stmt)) {
    // Update session name
    $_SESSION['name'] = $full_name;

    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update profile. Please try again.']);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
