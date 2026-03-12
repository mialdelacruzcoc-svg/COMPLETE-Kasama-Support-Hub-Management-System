<?php
require_once '../../api/config.php';

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coach') { 
    header('Location: ../../index.php'); 
    exit; 
}

$faqs = mysqli_query($conn, "SELECT * FROM faqs ORDER BY id DESC");
$faq_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM faqs"))['cnt'] ?? 0;
$faq_academic = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM faqs WHERE category = 'Academic'"))['cnt'] ?? 0;
$faq_enrollment = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM faqs WHERE category = 'Enrollment'"))['cnt'] ?? 0;
$faq_other = $faq_total - $faq_academic - $faq_enrollment;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage FAQ - Coach Hannah</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/shared-styles.css">
    <link rel="stylesheet" href="../../css/coach-faq-manager-styles.css">
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
                <span class="header-title">FAQ Manager</span>
            </div>
            <div class="header-right">
                <a href="dashboard.php" class="btn-back-header">← Dashboard</a>
                <?php include '../../includes/profile-dropdown.php'; ?>
            </div>
        </header>

        <main class="dashboard-main">
            <div class="welcome-banner">
                <h1 class="welcome-title">FAQ Control Center</h1>
                <p class="welcome-subtitle">Manage student-facing questions and answers from one place.</p>
            </div>

            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $faq_total; ?></div>
                    <div class="stat-label">Total FAQs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $faq_academic; ?></div>
                    <div class="stat-label">Academic</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $faq_enrollment; ?></div>
                    <div class="stat-label">Enrollment</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $faq_other; ?></div>
                    <div class="stat-label">Other Topics</div>
                </div>
            </div>

            <div class="faq-grid">
                <section class="section-card add-faq-card">
                    <h2 class="section-card-header">Add New FAQ</h2>
                    <form id="addFaqForm" class="faq-form">
                        <input type="hidden" name="action" value="add">

                        <div class="input-group">
                            <label for="category">Category</label>
                            <select id="category" name="category" required>
                                <option value="Academic">Academic</option>
                                <option value="Enrollment">Enrollment</option>
                                <option value="Financial">Financial</option>
                                <option value="Personal Support">Personal Support</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>

                        <div class="input-group">
                            <label for="question">Question</label>
                            <input id="question" type="text" name="question" required placeholder="Enter the question">
                        </div>

                        <div class="input-group">
                            <label for="answer">Answer</label>
                            <textarea id="answer" name="answer" required rows="6" placeholder="Type the answer here"></textarea>
                        </div>

                        <button type="submit" class="btn-submit btn-publish">Publish FAQ</button>
                    </form>
                </section>

                <section class="section-card list-faq-card">
                    <div class="faq-list-header">
                        <h2 class="section-card-header">Current FAQs</h2>
                        <span class="faq-count"><?php echo $faq_total; ?> total</span>
                    </div>
                    <div class="table-wrap">
                        <table class="faq-table">
                            <thead>
                                <tr>
                                    <th>Question</th>
                                    <th>Category</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($faqs) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($faqs)): ?>
                                    <tr>
                                        <td class="question-cell"><?php echo htmlspecialchars($row['question']); ?></td>
                                        <td>
                                            <span class="category-badge"><?php echo htmlspecialchars($row['category']); ?></span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button
                                                    class="btn-edit"
                                                    onclick="openEditFaqModal(this)"
                                                    data-id="<?php echo (int) $row['id']; ?>"
                                                    data-question="<?php echo htmlspecialchars($row['question'], ENT_QUOTES); ?>"
                                                    data-answer="<?php echo htmlspecialchars($row['answer'], ENT_QUOTES); ?>"
                                                    data-category="<?php echo htmlspecialchars($row['category'], ENT_QUOTES); ?>"
                                                >
                                                    Edit
                                                </button>
                                                <button class="btn-delete" onclick="deleteFaq(<?php echo (int) $row['id']; ?>)">Delete</button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="no-data-cell">No FAQs yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <div class="modal" id="editFaqModal" aria-hidden="true">
        <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="editFaqTitle">
            <div class="modal-header">
                <h3 id="editFaqTitle">Edit Published FAQ</h3>
                <button type="button" class="modal-close" onclick="closeEditFaqModal()">×</button>
            </div>

            <form id="editFaqForm" class="faq-form modal-form">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editFaqId">

                <div class="input-group">
                    <label for="editCategory">Category</label>
                    <select id="editCategory" name="category" required>
                        <option value="Academic">Academic</option>
                        <option value="Enrollment">Enrollment</option>
                        <option value="Financial">Financial</option>
                        <option value="Personal Support">Personal Support</option>
                        <option value="Others">Others</option>
                    </select>
                </div>

                <div class="input-group">
                    <label for="editQuestion">Question</label>
                    <input id="editQuestion" type="text" name="question" required>
                </div>

                <div class="input-group">
                    <label for="editAnswer">Answer</label>
                    <textarea id="editAnswer" name="answer" rows="6" required></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-modal-cancel" onclick="closeEditFaqModal()">Cancel</button>
                    <button type="submit" class="btn-submit btn-publish" id="editFaqSubmitBtn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>


    <script src="../../js/coach-faq-manager.js"></script>
</body>
</html>