<?php
require_once '../../api/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    header('Location: ../../index.php');
    exit;
}

$coach_name = $_SESSION['name'];

// Search & filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$year_filter = isset($_GET['year']) ? trim($_GET['year']) : '';

$where_clauses = ["role = 'student'"];
$params = [];
$types = '';

if (!empty($search)) {
    $where_clauses[] = "(name LIKE ? OR student_id LIKE ? OR email LIKE ?)";
    $search_like = "%$search%";
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $types .= 'sss';
}

if (!empty($year_filter)) {
    $where_clauses[] = "year_level = ?";
    $params[] = $year_filter;
    $types .= 's';
}

$where_sql = implode(' AND ', $where_clauses);
$sql = "SELECT id, student_id, name, email, year_level, course, profile_picture, created_at 
        FROM users WHERE $where_sql ORDER BY name ASC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$students = mysqli_stmt_get_result($stmt);

$total_count = mysqli_num_rows($students);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Kasama Support Hub</title>
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
                <a href="dashboard.php" class="back-link">← Dashboard</a>
                <span class="header-title">Student Management</span>
            </div>
            <div class="header-right">
                <?php include '../../includes/profile-dropdown.php'; ?>
            </div>
        </header>

        <main class="page-main">
            <!-- Search & Filter Bar -->
            <div class="filter-bar">
                <form method="GET" class="filter-form" id="filterForm">
                    <div class="search-input-wrap">
                        <span class="search-icon">🔍</span>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search by name, ID, or email..." class="search-input">
                    </div>
                    <select name="year" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                        <option value="">All Year Levels</option>
                        <?php
                        $years = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
                        foreach ($years as $y) {
                            $sel = ($year_filter === $y) ? 'selected' : '';
                            echo "<option value=\"$y\" $sel>$y</option>";
                        }
                        ?>
                    </select>
                    <button type="submit" class="btn-filter">Search</button>
                    <?php if (!empty($search) || !empty($year_filter)): ?>
                        <a href="students.php" class="btn-clear">Clear</a>
                    <?php endif; ?>
                </form>
                <div class="filter-count"><?php echo $total_count; ?> student<?php echo $total_count !== 1 ? 's' : ''; ?> found</div>
            </div>

            <!-- Student Cards Grid -->
            <div class="students-grid">
                <?php if ($total_count > 0): ?>
                    <?php while ($s = mysqli_fetch_assoc($students)): 
                        $s_words = explode(" ", $s['name']);
                        $s_initials = "";
                        foreach ($s_words as $w) { if(!empty($w)) $s_initials .= strtoupper($w[0]); }
                        $s_initials = substr($s_initials, 0, 2);
                    ?>
                    <div class="student-card" onclick="window.location.href='view-student.php?id=<?php echo $s['id']; ?>'">
                        <div class="student-card-top">
                            <?php if (!empty($s['profile_picture']) && file_exists('../../uploads/profiles/' . $s['profile_picture'])): ?>
                                <img src="../../uploads/profiles/<?php echo htmlspecialchars($s['profile_picture']); ?>" class="student-card-avatar-img" alt="">
                            <?php else: ?>
                                <div class="student-card-avatar"><?php echo $s_initials; ?></div>
                            <?php endif; ?>
                            <div class="student-card-info">
                                <div class="student-card-name"><?php echo htmlspecialchars($s['name']); ?></div>
                                <div class="student-card-id"><?php echo htmlspecialchars($s['student_id']); ?></div>
                            </div>
                        </div>
                        <div class="student-card-details">
                            <?php if (!empty($s['year_level'])): ?>
                                <span class="student-tag"><?php echo htmlspecialchars($s['year_level']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($s['course'])): ?>
                                <span class="student-tag course-tag"><?php echo htmlspecialchars($s['course']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="student-card-footer">
                            <span class="student-email"><?php echo htmlspecialchars($s['email']); ?></span>
                            <span class="student-joined">Joined <?php echo date('M Y', strtotime($s['created_at'])); ?></span>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">👥</div>
                        <p>No students found</p>
                        <?php if (!empty($search) || !empty($year_filter)): ?>
                            <a href="students.php" class="btn-clear-empty">Clear Filters</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
