<?php
require_once '../../api/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch full user profile
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$student_name = $user['name'];
$words = explode(" ", $student_name);
$initials = "";
foreach ($words as $w) { if(!empty($w)) $initials .= strtoupper($w[0]); }
$display_initials = substr($initials, 0, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>My Profile - Kasama Support Hub</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/student-profile-styles.css">
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
                <?php include '../../includes/notification-bell.php'; ?>
                <a href="../../api/logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="profile-wrapper">
        <header class="profile-header-bar">
            <div class="header-left">
                <a href="dashboard.php" class="back-link">← Back</a>
                <span class="header-title">My Profile</span>
            </div>
            <div class="header-right">
                <div class="user-profile">
                    <div class="user-avatar"><?php echo $display_initials; ?></div>
                    <span class="user-name"><?php echo htmlspecialchars($student_name); ?></span>
                </div>
            </div>
        </header>

        <main class="profile-main">
            <!-- Profile Banner -->
            <div class="profile-banner">
                <div class="profile-avatar-container">
                    <?php if (!empty($user['profile_picture']) && file_exists('../../uploads/profiles/' . $user['profile_picture'])): ?>
                        <img src="../../uploads/profiles/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile" class="profile-avatar-img" id="avatarImg">
                    <?php else: ?>
                        <div class="profile-avatar-large" id="avatarInitials"><?php echo $display_initials; ?></div>
                        <img src="" alt="Profile" class="profile-avatar-img" id="avatarImg" style="display:none;">
                    <?php endif; ?>
                    <label for="profilePicInput" class="avatar-upload-btn" title="Change photo">📷</label>
                    <input type="file" id="profilePicInput" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;">
                </div>
                <div class="profile-banner-info">
                    <h1 class="profile-display-name"><?php echo htmlspecialchars($student_name); ?></h1>
                    <p class="profile-display-meta"><?php echo htmlspecialchars($user['student_id']); ?> · <?php echo htmlspecialchars($user['year_level'] ?? '—'); ?></p>
                    <p class="profile-display-email"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <div id="profileMessage" class="profile-message" style="display:none;"></div>

            <form id="profileForm" autocomplete="off">
                <!-- Academic Information -->
                <div class="profile-section">
                    <h2 class="profile-section-title">Academic Information</h2>
                    <div class="profile-info-grid">
                        <div class="info-item">
                            <span class="info-label">Student ID</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['student_id']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Year Level</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['year_level'] ?? '—'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Course</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['course'] ?: '—'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Campus</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['campus'] ?: '—'); ?></span>
                        </div>
                        <div class="info-item info-item-wide">
                            <span class="info-label">College</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['college'] ?: '—'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Personal Details -->
                <div class="profile-section">
                    <h2 class="profile-section-title">Personal Details</h2>
                    <div class="profile-form-grid">
                        <div class="form-group">
                            <label for="last_name"><span class="required">*</span>Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="first_name"><span class="required">*</span>First Name</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="middle_name">Middle Name</label>
                            <input type="text" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($user['middle_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="extension_name">Extension Name</label>
                            <select id="extension_name" name="extension_name">
                                <option value="">Please Select</option>
                                <?php
                                $extensions = ['Jr.', 'Sr.', 'II', 'III', 'IV', 'V'];
                                foreach ($extensions as $ext) {
                                    $selected = ($user['extension_name'] ?? '') === $ext ? 'selected' : '';
                                    echo "<option value=\"$ext\" $selected>$ext</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="profile-form-grid">
                        <div class="form-group">
                            <label for="gender"><span class="required">*</span>Gender</label>
                            <select id="gender" name="gender" required>
                                <option value="">Please Select</option>
                                <?php
                                $genders = ['Male', 'Female', 'Other', 'Prefer not to say'];
                                foreach ($genders as $g) {
                                    $selected = ($user['gender'] ?? '') === $g ? 'selected' : '';
                                    echo "<option value=\"$g\" $selected>$g</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="date_of_birth"><span class="required">*</span>Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="place_of_birth"><span class="required">*</span>Place of Birth</label>
                            <input type="text" id="place_of_birth" name="place_of_birth" value="<?php echo htmlspecialchars($user['place_of_birth'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="civil_status"><span class="required">*</span>Civil Status</label>
                            <select id="civil_status" name="civil_status" required>
                                <option value="">Please Select</option>
                                <?php
                                $statuses = ['Single', 'Married', 'Widowed', 'Separated', 'Divorced'];
                                foreach ($statuses as $s) {
                                    $selected = ($user['civil_status'] ?? '') === $s ? 'selected' : '';
                                    echo "<option value=\"$s\" $selected>$s</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="profile-form-grid">
                        <div class="form-group">
                            <label for="citizenship"><span class="required">*</span>Citizenship</label>
                            <input type="text" id="citizenship" name="citizenship" value="<?php echo htmlspecialchars($user['citizenship'] ?? ''); ?>" placeholder="e.g. Filipino" required>
                        </div>
                        <div class="form-group">
                            <label for="religion">Religion</label>
                            <input type="text" id="religion" name="religion" value="<?php echo htmlspecialchars($user['religion'] ?? ''); ?>" placeholder="e.g. Catholic">
                        </div>
                        <div class="form-group">
                            <label for="mobile_number"><span class="required">*</span>Student's Mobile No.</label>
                            <input type="tel" id="mobile_number" name="mobile_number" value="<?php echo htmlspecialchars($user['mobile_number'] ?? ''); ?>" placeholder="e.g. 09171234567" required>
                        </div>
                    </div>
                </div>

                <!-- Contact & Social -->
                <div class="profile-section">
                    <h2 class="profile-section-title">Contact & Social</h2>
                    <div class="profile-form-grid">
                        <div class="form-group">
                            <label for="email_display"><span class="required">*</span>Email</label>
                            <input type="email" id="email_display" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            <span class="form-hint">Email cannot be changed</span>
                        </div>
                        <div class="form-group">
                            <label for="facebook_name">Facebook Name</label>
                            <input type="text" id="facebook_name" name="facebook_name" value="<?php echo htmlspecialchars($user['facebook_name'] ?? ''); ?>" placeholder="e.g. Juan Dela Cruz">
                        </div>
                        <div class="form-group">
                            <label for="facebook_link">Facebook Link</label>
                            <input type="url" id="facebook_link" name="facebook_link" value="<?php echo htmlspecialchars($user['facebook_link'] ?? ''); ?>" placeholder="https://www.facebook.com/...">
                        </div>
                    </div>
                </div>

                <!-- Academic Details (Editable) -->
                <div class="profile-section">
                    <h2 class="profile-section-title">Academic Details</h2>
                    <div class="profile-form-grid">
                        <div class="form-group">
                            <label for="course">Course</label>
                            <input type="text" id="course" name="course" value="<?php echo htmlspecialchars($user['course'] ?? ''); ?>" placeholder="e.g. Bachelor of Science in Computer Engineering">
                        </div>
                        <div class="form-group">
                            <label for="campus">Campus</label>
                            <input type="text" id="campus" name="campus" value="<?php echo htmlspecialchars($user['campus'] ?? ''); ?>" placeholder="e.g. Carmen Campus">
                        </div>
                        <div class="form-group">
                            <label for="college">College</label>
                            <input type="text" id="college" name="college" value="<?php echo htmlspecialchars($user['college'] ?? ''); ?>" placeholder="e.g. College of Engineering and Architecture">
                        </div>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" placeholder="Complete home address">
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="profile-actions">
                    <button type="submit" class="btn-save-profile" id="btnSave">Save Changes</button>
                </div>
            </form>
        </main>
    </div>

    <script>
    document.getElementById('profileForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const btn = document.getElementById('btnSave');
        const msgDiv = document.getElementById('profileMessage');
        btn.disabled = true;
        btn.textContent = 'Saving...';

        const formData = new FormData(this);

        try {
            const response = await fetch('../../api/update-student-profile.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            msgDiv.style.display = 'block';
            if (result.success) {
                msgDiv.className = 'profile-message success';
                msgDiv.textContent = result.message;
                // Update the displayed name if name parts changed
                const first = formData.get('first_name') || '';
                const last = formData.get('last_name') || '';
                if (first && last) {
                    const fullName = first + ' ' + last;
                    document.querySelector('.profile-display-name').textContent = fullName;
                }
            } else {
                msgDiv.className = 'profile-message error';
                msgDiv.textContent = result.message;
            }

            window.scrollTo({ top: 0, behavior: 'smooth' });
        } catch (err) {
            msgDiv.style.display = 'block';
            msgDiv.className = 'profile-message error';
            msgDiv.textContent = 'Something went wrong. Please try again.';
        }

        btn.disabled = false;
        btn.textContent = 'Save Changes';
    });

    // Profile picture upload
    document.getElementById('profilePicInput').addEventListener('change', async function() {
        const file = this.files[0];
        if (!file) return;

        if (file.size > 5 * 1024 * 1024) {
            alert('File too large. Maximum size is 5MB.');
            return;
        }

        const formData = new FormData();
        formData.append('profile_picture', file);

        const msgDiv = document.getElementById('profileMessage');

        try {
            const response = await fetch('../../api/upload-profile-picture.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                // Show the uploaded image
                const avatarImg = document.getElementById('avatarImg');
                const avatarInitials = document.getElementById('avatarInitials');
                avatarImg.src = '../../uploads/profiles/' + result.filename + '?t=' + Date.now();
                avatarImg.style.display = 'block';
                if (avatarInitials) avatarInitials.style.display = 'none';

                msgDiv.style.display = 'block';
                msgDiv.className = 'profile-message success';
                msgDiv.textContent = 'Profile picture updated!';
            } else {
                msgDiv.style.display = 'block';
                msgDiv.className = 'profile-message error';
                msgDiv.textContent = result.message;
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } catch (err) {
            msgDiv.style.display = 'block';
            msgDiv.className = 'profile-message error';
            msgDiv.textContent = 'Failed to upload picture.';
        }
    });
    </script>
</body>
</html>
