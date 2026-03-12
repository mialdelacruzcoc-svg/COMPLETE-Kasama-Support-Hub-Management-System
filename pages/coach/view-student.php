<?php
require_once '../../api/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    header('Location: ../../index.php');
    exit;
}

$student_db_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($student_db_id <= 0) {
    header('Location: students.php');
    exit;
}

// Fetch student
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ? AND role = 'student'");
mysqli_stmt_bind_param($stmt, "i", $student_db_id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$student) {
    header('Location: students.php');
    exit;
}

$s_name = $student['name'];
$words = explode(" ", $s_name);
$initials = "";
foreach ($words as $w) { if(!empty($w)) $initials .= strtoupper($w[0]); }
$display_initials = substr($initials, 0, 2);

// Get concern stats
$sid = $student['student_id'];
$stats_stmt = mysqli_prepare($conn, "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved
    FROM concerns WHERE student_id = ?");
mysqli_stmt_bind_param($stats_stmt, "s", $sid);
mysqli_stmt_execute($stats_stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stats_stmt));

// Get recent concerns
$concerns_stmt = mysqli_prepare($conn, "SELECT tracking_id, subject, status, created_at FROM concerns WHERE student_id = ? ORDER BY created_at DESC LIMIT 5");
mysqli_stmt_bind_param($concerns_stmt, "s", $sid);
mysqli_stmt_execute($concerns_stmt);
$concerns = mysqli_stmt_get_result($concerns_stmt);

// Get recent appointments
$apt_stmt = mysqli_prepare($conn, "SELECT appointment_date, appointment_time, status FROM appointments WHERE student_id = ? ORDER BY appointment_date DESC LIMIT 5");
mysqli_stmt_bind_param($apt_stmt, "s", $sid);
mysqli_stmt_execute($apt_stmt);
$appointments = mysqli_stmt_get_result($apt_stmt);

$coach_name = $_SESSION['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($s_name); ?> - Student Profile</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/shared-styles.css">
    <link rel="stylesheet" href="../../css/coach-students-styles.css">
    <link rel="stylesheet" href="../../css/dark-mode.css">
    <script src="../../js/dark-mode.js"></script>
</head>
<body>
    <nav class="top-nav">
        <div class="nav-content">
            <div class="nav-left">
                <span class="nav-title">Kasama Support Hub</span>
            </div>
            <div class="nav-right">
                <a href="../../api/logout.php" class="nav-logout-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="page-wrapper">
        <header class="page-header-bar">
            <div class="header-left">
                <a href="students.php" class="back-link">← Students</a>
                <span class="header-title">Student Profile</span>
            </div>
            <div class="header-right">
                <?php include '../../includes/profile-dropdown.php'; ?>
            </div>
        </header>

        <main class="page-main">
            <!-- Student Banner -->
            <div class="view-profile-banner">
                <div class="view-avatar-section">
                    <?php if (!empty($student['profile_picture']) && file_exists('../../uploads/profiles/' . $student['profile_picture'])): ?>
                        <img src="../../uploads/profiles/<?php echo htmlspecialchars($student['profile_picture']); ?>" class="view-avatar-img" alt="">
                    <?php else: ?>
                        <div class="view-avatar-initials"><?php echo $display_initials; ?></div>
                    <?php endif; ?>
                </div>
                <div class="view-profile-info">
                    <h1 class="view-profile-name"><?php echo htmlspecialchars($s_name); ?></h1>
                    <p class="view-profile-meta"><?php echo htmlspecialchars($student['student_id']); ?> · <?php echo htmlspecialchars($student['year_level'] ?? '—'); ?></p>
                    <p class="view-profile-email"><?php echo htmlspecialchars($student['email']); ?></p>
                </div>
                <div class="view-profile-actions">
                    <button class="btn-remove-student" onclick="confirmRemoveStudent(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars(addslashes($s_name)); ?>')">Remove Student</button>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="view-stats-row">
                <div class="view-stat">
                    <div class="view-stat-num"><?php echo $stats['total'] ?? 0; ?></div>
                    <div class="view-stat-label">Total Concerns</div>
                </div>
                <div class="view-stat">
                    <div class="view-stat-num orange"><?php echo $stats['pending'] ?? 0; ?></div>
                    <div class="view-stat-label">Pending</div>
                </div>
                <div class="view-stat">
                    <div class="view-stat-num green"><?php echo $stats['resolved'] ?? 0; ?></div>
                    <div class="view-stat-label">Resolved</div>
                </div>
            </div>

            <div class="view-grid">
                <!-- Personal Information -->
                <div class="view-section">
                    <h2 class="view-section-title">Personal Details</h2>
                    <div class="view-info-list">
                        <div class="view-info-row">
                            <span class="view-info-label">Full Name</span>
                            <span class="view-info-value"><?php echo htmlspecialchars($s_name); ?></span>
                        </div>
                        <div class="view-info-row">
                            <span class="view-info-label">Gender</span>
                            <span class="view-info-value"><?php echo htmlspecialchars($student['gender'] ?: '—'); ?></span>
                        </div>
                        <div class="view-info-row">
                            <span class="view-info-label">Date of Birth</span>
                            <span class="view-info-value"><?php echo !empty($student['date_of_birth']) ? date('M d, Y', strtotime($student['date_of_birth'])) : '—'; ?></span>
                        </div>
                        <div class="view-info-row">
                            <span class="view-info-label">Place of Birth</span>
                            <span class="view-info-value"><?php echo htmlspecialchars($student['place_of_birth'] ?: '—'); ?></span>
                        </div>
                        <div class="view-info-row">
                            <span class="view-info-label">Civil Status</span>
                            <span class="view-info-value"><?php echo htmlspecialchars($student['civil_status'] ?: '—'); ?></span>
                        </div>
                        <div class="view-info-row">
                            <span class="view-info-label">Citizenship</span>
                            <span class="view-info-value"><?php echo htmlspecialchars($student['citizenship'] ?: '—'); ?></span>
                        </div>
                        <div class="view-info-row">
                            <span class="view-info-label">Religion</span>
                            <span class="view-info-value"><?php echo htmlspecialchars($student['religion'] ?: '—'); ?></span>
                        </div>
                        <div class="view-info-row">
                            <span class="view-info-label">Mobile No.</span>
                            <span class="view-info-value"><?php echo htmlspecialchars($student['mobile_number'] ?: '—'); ?></span>
                        </div>
                        <div class="view-info-row">
                            <span class="view-info-label">Address</span>
                            <span class="view-info-value"><?php echo htmlspecialchars($student['address'] ?: '—'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Academic & Contact Information -->
                <div class="view-section">
                    <h2 class="view-section-title">Academic & Contact</h2>
                    <div class="view-info-list">
                        <div class="view-info-row">
                            <span class="view-info-label">Student ID</span>
                            <span class="view-info-value highlight"><?php echo htmlspecialchars($student['student_id']); ?></span>
                        </div>
                        <div class="view-info-row">
                            <span class="view-info-label">Year Level</span>
                            <span class="view-info-value"><?php echo htmlspecialchars($student['year_level'] ?: '—'); ?></span>
                        </div>
                        <div class="view-info-row">
                            <span class="view-info-label">Course</span>
                            <span class="view-info-value"><?php echo htmlspecialchars($student['course'] ?: '—'); ?></span>
                        </div>
                        <div class="view-info-row">
                            <span class="view-info-label">Campus</span>
                            <span class="view-info-value"><?php echo htmlspecialchars($student['campus'] ?: '—'); ?></span>
                        </div>
                        <div class="view-info-row">
                            <span class="view-info-label">College</span>
                            <span class="view-info-value"><?php echo htmlspecialchars($student['college'] ?: '—'); ?></span>
                        </div>
                        <div class="view-info-row">
                            <span class="view-info-label">Email</span>
                            <span class="view-info-value"><?php echo htmlspecialchars($student['email']); ?></span>
                        </div>
                        <div class="view-info-row">
                            <span class="view-info-label">Facebook</span>
                            <span class="view-info-value">
                                <?php if (!empty($student['facebook_link'])): ?>
                                    <a href="<?php echo htmlspecialchars($student['facebook_link']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($student['facebook_name'] ?: $student['facebook_link']); ?></a>
                                <?php elseif (!empty($student['facebook_name'])): ?>
                                    <?php echo htmlspecialchars($student['facebook_name']); ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="view-info-row">
                            <span class="view-info-label">Joined</span>
                            <span class="view-info-value"><?php echo date('M d, Y', strtotime($student['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity: Concerns & Appointments side-by-side -->
            <div class="view-grid">
                <!-- Recent Concerns -->
                <div class="view-section">
                    <h2 class="view-section-title">Recent Concerns</h2>
                    <?php if (mysqli_num_rows($concerns) > 0): ?>
                    <div class="view-activity-list">
                        <?php while ($c = mysqli_fetch_assoc($concerns)): 
                            $status_class = strtolower(str_replace(' ', '-', $c['status']));
                        ?>
                        <a href="../shared/concern-details.php?id=<?php echo $c['tracking_id']; ?>" class="view-activity-item">
                            <div class="view-activity-info">
                                <div class="view-activity-title"><?php echo htmlspecialchars($c['subject']); ?></div>
                                <div class="view-activity-sub">#<?php echo substr($c['tracking_id'], -6); ?> · <?php echo date('M d, Y', strtotime($c['created_at'])); ?></div>
                            </div>
                            <span class="view-badge badge-<?php echo $status_class; ?>"><?php echo $c['status']; ?></span>
                        </a>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <div class="view-empty">No concerns submitted</div>
                    <?php endif; ?>
                </div>

                <!-- Recent Appointments -->
                <div class="view-section">
                    <h2 class="view-section-title">Recent Appointments</h2>
                    <?php if (mysqli_num_rows($appointments) > 0): ?>
                    <div class="view-activity-list">
                        <?php while ($a = mysqli_fetch_assoc($appointments)): 
                            $a_status_class = strtolower(str_replace(' ', '-', $a['status']));
                        ?>
                        <div class="view-activity-item">
                            <div class="view-activity-info">
                                <div class="view-activity-title"><?php echo date('M d, Y', strtotime($a['appointment_date'])); ?></div>
                                <div class="view-activity-sub"><?php echo $a['appointment_time']; ?></div>
                            </div>
                            <span class="view-badge badge-<?php echo $a_status_class; ?>"><?php echo $a['status']; ?></span>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <div class="view-empty">No appointments</div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Remove Student Modal -->
    <div class="modal" id="removeModal">
        <div class="modal-content">
            <h3>⚠️ Remove Student</h3>
            <p>Are you sure you want to remove <strong id="removeStudentName"></strong>?</p>
            <p style="color:#c62828; font-size:13px; margin-top:8px;">This will permanently delete all their data including concerns, appointments, and notifications. This action cannot be undone.</p>
            <input type="hidden" id="removeStudentId">
            <div class="modal-footer">
                <button class="modal-cancel" onclick="closeRemoveModal()">Cancel</button>
                <button class="modal-submit btn-danger" id="confirmRemoveBtn" onclick="executeRemoveStudent()">Yes, Remove Student</button>
            </div>
        </div>
    </div>

    <script>
    function confirmRemoveStudent(id, name) {
        document.getElementById('removeStudentId').value = id;
        document.getElementById('removeStudentName').textContent = name;
        document.getElementById('removeModal').style.display = 'block';
    }

    function closeRemoveModal() {
        document.getElementById('removeModal').style.display = 'none';
    }

    async function executeRemoveStudent() {
        const id = document.getElementById('removeStudentId').value;
        const btn = document.getElementById('confirmRemoveBtn');
        btn.disabled = true;
        btn.textContent = 'Removing...';

        try {
            const formData = new FormData();
            formData.append('student_id', id);

            const response = await fetch('../../api/delete-student.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                alert(result.message);
                window.location.href = 'students.php';
            } else {
                alert(result.message);
                btn.disabled = false;
                btn.textContent = 'Yes, Remove Student';
            }
        } catch (err) {
            alert('Something went wrong. Please try again.');
            btn.disabled = false;
            btn.textContent = 'Yes, Remove Student';
        }
    }

    // Close modal on backdrop click
    document.getElementById('removeModal').addEventListener('click', function(e) {
        if (e.target === this) closeRemoveModal();
    });
    </script>
</body>
</html>
