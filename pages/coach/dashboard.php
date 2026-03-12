    <?php
require_once '../../api/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    header('Location: ../../index.php');
    exit;
}

$coach_name = $_SESSION['name'];

$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'student'"))['count'];
$total_concerns = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM concerns"))['count'];
$pending_concerns = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM concerns WHERE status = 'Pending'"))['count'];
$resolved_concerns = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM concerns WHERE status = 'Resolved'"))['count'];

$query_risk = "SELECT u.name, c.student_id, COUNT(c.id) as pending_count, MAX(c.created_at) as last_date 
                FROM concerns c 
                LEFT JOIN users u ON c.student_id = u.student_id 
                WHERE c.status = 'Pending' 
                GROUP BY c.student_id 
                ORDER BY last_date DESC";
$risk_result = mysqli_query($conn, $query_risk);

$apt_query = "SELECT a.*, u.name as student_name 
              FROM appointments a 
              JOIN users u ON a.student_id = u.student_id
              WHERE a.status IN ('Scheduled', 'Confirmed', 'Reschedule Requested') 
              ORDER BY a.appointment_date ASC";
$appointments_result = mysqli_query($conn, $apt_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coach Dashboard - Kasama Support Hub</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/shared-styles.css">
    <link rel="stylesheet" href="../../css/coach-dashboard-styles.css">
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

    <div class="dashboard-wrapper">
        <header class="dashboard-header">
            <div class="header-left">
                <img src="../../images/phinma-logo.png" alt="Logo" class="header-logo">
                <span class="header-title">Coach Dashboard</span>
            </div>
            <div class="header-right">
                <!-- NOTIFICATION BELL -->
                <div class="notif-wrapper">
                    <button class="notif-bell" id="notifBell" type="button">
                        🔔
                        <span class="notif-badge hidden" id="notifBadge">0</span>
                    </button>
                    <div class="notif-dropdown" id="notifDropdown">
                        <div class="notif-header">
                            <h4>🔔 Notifications</h4>
                            <button class="mark-read-btn" id="markAllBtn" type="button">Mark all read</button>
                        </div>
                        <div class="notif-list" id="notifList">
                            <div class="notif-empty">Loading...</div>
                        </div>
                        <a href="notifications.php" class="notif-footer">
                            View All Notifications →
                        </a>
                    </div>
                </div>
                <!-- END NOTIFICATION BELL -->

                <?php include '../../includes/profile-dropdown.php'; ?>
            </div>
        </header>

        <main class="dashboard-main">
            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <div class="welcome-text">
                    <h1 class="welcome-greeting">Welcome back, Coach <?php echo explode(' ', $coach_name)[0]; ?></h1>
                    <p class="welcome-subtitle"><?php echo date('l, F j, Y'); ?> &middot; Guidance Coach</p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number green"><?php echo $total_students; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number blue"><?php echo $total_concerns; ?></div>
                    <div class="stat-label">Total Concerns</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number orange"><?php echo $pending_concerns; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number teal"><?php echo $resolved_concerns; ?></div>
                    <div class="stat-label">Resolved</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="section-card">
                <h2 class="section-card-header">Quick Actions</h2>
                <div class="action-bar">
                    <button class="btn-submit btn-primary-action" onclick="window.location.href='../shared/concerns-table.php'">View All Concerns</button>
                    <button class="btn-submit" onclick="window.location.href='appointments.php'">Manage Appointments</button>
                    <button class="btn-submit" onclick="window.location.href='faq-manager.php'">Manage FAQ Hub</button>
                    <button class="btn-submit" onclick="window.location.href='students.php'">Manage Students</button>
                    <button class="btn-submit" onclick="window.location.href='../../analytics.php'">View Analytics</button>
                    <button class="btn-submit" onclick="window.location.href='calendar.php'">Manage Calendar</button>
                    <button class="btn-submit" onclick="window.location.href='notifications.php'">All Notifications</button>
                </div>
            </div>

            <!-- Two-Column Grid for Appointments & At-Risk Students -->
            <div class="dashboard-grid">
                <!-- Appointments Section -->
                <div class="section-card">
                    <h2 class="section-card-header">📅 Upcoming Appointments</h2>

                    <!-- Mobile Cards (shown on mobile) -->
                    <div class="mobile-card-list">
                        <?php if ($appointments_result && mysqli_num_rows($appointments_result) > 0): ?>
                            <?php while ($apt = mysqli_fetch_assoc($appointments_result)):
                                $status_class = ($apt['status'] == 'Confirmed') ? 'status-confirmed' : (($apt['status'] == 'Reschedule Requested') ? 'status-reschedule' : 'status-scheduled');
                            ?>
                            <div class="mobile-card">
                                <div class="mobile-card-info">
                                    <div class="mobile-card-title"><?php echo htmlspecialchars($apt['student_name']); ?></div>
                                    <div class="mobile-card-subtitle"><?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?> · <?php echo $apt['appointment_time']; ?></div>
                                </div>
                                <div class="mobile-card-right">
                                    <span class="mobile-card-badge <?php echo $status_class; ?>"><?php echo $apt['status']; ?></span>
                                    <div class="mobile-card-actions">
                                        <?php if ($apt['status'] !== 'Confirmed'): ?>
                                            <button class="btn-confirm-sm" onclick="event.stopPropagation(); updateAptStatus(<?php echo $apt['id']; ?>, 'Confirmed')">✓</button>
                                        <?php endif; ?>
                                        <button class="btn-resched-sm" onclick="event.stopPropagation(); openReschedModal(<?php echo $apt['id']; ?>)">↻</button>
                                        <button class="btn-done-sm" onclick="event.stopPropagation(); updateAptStatus(<?php echo $apt['id']; ?>, 'Completed')">✔</button>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">📅</div>
                                <p>No upcoming appointments</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Desktop Table -->
                    <div class="desktop-table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($appointments_result) mysqli_data_seek($appointments_result, 0);
                                if ($appointments_result && mysqli_num_rows($appointments_result) > 0): ?>
                                    <?php while ($apt = mysqli_fetch_assoc($appointments_result)):
                                        $status_class = ($apt['status'] == 'Confirmed') ? 'status-confirmed' : (($apt['status'] == 'Reschedule Requested') ? 'status-reschedule' : 'status-scheduled');
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($apt['student_name']); ?></strong></td>
                                        <td>
                                            <span class="apt-date"><?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?></span>
                                            <strong class="apt-time">| <?php echo $apt['appointment_time']; ?></strong>
                                        </td>
                                        <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $apt['status']; ?></span></td>
                                        <td>
                                            <div class="apt-actions">
                                                <?php if ($apt['status'] !== 'Confirmed'): ?>
                                                    <button class="btn-confirm" onclick="updateAptStatus(<?php echo $apt['id']; ?>, 'Confirmed')">Confirm</button>
                                                <?php endif; ?>
                                                <button class="btn-resched" onclick="openReschedModal(<?php echo $apt['id']; ?>)">Reschedule</button>
                                                <button class="btn-done" onclick="updateAptStatus(<?php echo $apt['id']; ?>, 'Completed')">Done</button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="no-data-row">No upcoming appointments</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Students Needing Attention Section -->
                <div class="section-card">
                    <h2 class="section-card-header">⚠️ Students Needing Attention</h2>

                    <!-- Mobile Cards -->
                    <div class="mobile-card-list">
                        <?php if (mysqli_num_rows($risk_result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($risk_result)): ?>
                            <div class="mobile-card" onclick="window.location.href='../shared/concerns-table.php'">
                                <div class="mobile-card-info">
                                    <div class="mobile-card-title"><?php echo htmlspecialchars($row['name']); ?></div>
                                    <div class="mobile-card-subtitle"><?php echo htmlspecialchars($row['student_id']); ?> · <?php echo date('M d', strtotime($row['last_date'])); ?></div>
                                </div>
                                <span class="mobile-card-badge badge-pending"><?php echo $row['pending_count']; ?> pending</span>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">✅</div>
                                <p>No students requiring attention</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Desktop Table -->
                    <div class="desktop-table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>ID</th>
                                    <th>Pending</th>
                                    <th>Last Submitted</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                mysqli_data_seek($risk_result, 0);
                                if (mysqli_num_rows($risk_result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($risk_result)): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                                        <td><span class="risk-count"><?php echo $row['pending_count']; ?></span></td>
                                        <td><?php echo date('M d, Y', strtotime($row['last_date'])); ?></td>
                                        <td><button class="btn-review" onclick="window.location.href='../shared/concerns-table.php'">Review</button></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="no-data-row">No students requiring attention</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div id="reschedModal" class="modal">
        <div class="modal-content">
            <h3>💬 Reschedule Request</h3>
            <p class="modal-hint">Send a message to the student regarding the schedule change.</p>
            <input type="hidden" id="modal_apt_id">
            <textarea id="modal_message" class="modal-textarea" placeholder="Hi! I cannot make it today, can we move it to..."></textarea>
            <div class="modal-footer">
                <button class="modal-cancel" onclick="closeModal()">Cancel</button>
                <button class="modal-submit" onclick="submitReschedule()">Send & Request Reschedule</button>
            </div>
        </div>
    </div>


    <script src="../../js/notifications.js"></script>
    <script src="../../js/coach-dashboard.js"></script>
</body>
</html>