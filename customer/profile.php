<?php
session_start();
if (!isset($_SESSION['customer'])) {
    header("Location: /Bathala/LandingPage/customer_login.php");
    exit;
}
$customer = $_SESSION['customer'];

$conn = new mysqli("localhost", "root", "", "smartpos");
if ($conn->connect_error) die("Connection failed");

$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$pending_count = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile - DeiTrack</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="/Bathala/assets/css/customer.css">
</head>
<body>

<div class="customer-layout">
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="top-navbar">
            <div>
                <div class="page-title">My Profile</div>
                <div class="page-subtitle">Manage your account information</div>
            </div>
        </div>

        <div class="page-content">

            <?php if ($success): ?>
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:12px 16px;margin-bottom:20px;color:#16a34a;font-size:0.88rem;display:flex;align-items:center;gap:8px;">
                <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div style="background:#fff1f1;border:1px solid #ffd5d5;border-radius:12px;padding:12px 16px;margin-bottom:20px;color:#c0392b;font-size:0.88rem;display:flex;align-items:center;gap:8px;">
                <i class="bi bi-exclamation-circle-fill"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <div class="profile-card">

                <!-- PROFILE HEADER -->
                <div class="profile-header">
                    <form action="/Bathala/backend/update_photo.php" method="POST" enctype="multipart/form-data" id="photoForm">
                        <div class="profile-avatar" onclick="document.getElementById('photoInput').click()">
                            <?php if (!empty($customer['photo'])): ?>
                                <img src="/Bathala/assets/images/customers/<?php echo htmlspecialchars($customer['photo']); ?>" alt="">
                            <?php else: ?>
                                <?php echo strtoupper(substr($customer['name'], 0, 1)); ?>
                            <?php endif; ?>
                            <div class="profile-avatar-overlay"><i class="bi bi-camera-fill"></i></div>
                        </div>
                        <input type="file" id="photoInput" name="photo" accept="image/*" style="display:none;" onchange="document.getElementById('photoForm').submit()">
                    </form>
                    <div class="profile-header-info">
                        <div class="profile-header-name"><?php echo htmlspecialchars($customer['name']); ?></div>
                        <div class="profile-header-email"><?php echo htmlspecialchars($customer['email']); ?></div>
                        <div class="profile-header-joined">Member since <?php echo date('F Y', strtotime($customer['created_at'])); ?></div>
                    </div>
                </div>

                <!-- PROFILE BODY -->
                <div class="profile-body">
                    <form action="/Bathala/backend/update_profile.php" method="POST">

                        <!-- PERSONAL INFO -->
                        <div class="profile-section-title">Personal Information</div>

                        <div class="profile-row">
                            <div class="profile-field">
                                <label>Full Name</label>
                                <input type="text" name="name" class="profile-input"
                                       value="<?php echo htmlspecialchars($customer['name']); ?>" required>
                            </div>
                            <div class="profile-field">
                                <label>Phone Number</label>
                                <input type="text" name="phone" class="profile-input"
                                       value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="profile-field">
                            <label>Email Address</label>
                            <input type="email" class="profile-input"
                                   value="<?php echo htmlspecialchars($customer['email']); ?>" disabled
                                   style="opacity:0.6;cursor:not-allowed;">
                        </div>

                        <!-- ADDRESS -->
                        <div class="profile-section-title">Delivery Address</div>

                        <div class="profile-field">
                            <label>Home Address</label>
                            <input type="text" name="home_address" class="profile-input"
                                   value="<?php echo htmlspecialchars($customer['home_address'] ?? ''); ?>"
                                   placeholder="House No., Street">
                        </div>

                        <div class="profile-row">
                            <div class="profile-field">
                                <label>Barangay</label>
                                <input type="text" name="barangay" class="profile-input"
                                       value="<?php echo htmlspecialchars($customer['barangay'] ?? ''); ?>"
                                       placeholder="Barangay">
                            </div>
                            <div class="profile-field">
                                <label>Municipality / City</label>
                                <input type="text" name="municipality" class="profile-input"
                                       value="<?php echo htmlspecialchars($customer['municipality'] ?? ''); ?>"
                                       placeholder="Municipality or City">
                            </div>
                        </div>

                        <div class="profile-row">
                            <div class="profile-field">
                                <label>Zip Code</label>
                                <input type="text" name="zip_code" class="profile-input"
                                       value="<?php echo htmlspecialchars($customer['zip_code'] ?? ''); ?>"
                                       placeholder="1234">
                            </div>
                        </div>

                        <!-- CHANGE PASSWORD -->
                        <div class="profile-section-title">Change Password</div>

                        <div class="profile-row">
                            <div class="profile-field">
                                <label>New Password</label>
                                <input type="password" name="new_password" class="profile-input" placeholder="Leave blank to keep current">
                            </div>
                            <div class="profile-field">
                                <label>Confirm Password</label>
                                <input type="password" name="confirm_password" class="profile-input" placeholder="Repeat new password">
                            </div>
                        </div>

                        <button type="submit" class="btn-save">
                            <i class="bi bi-check-lg"></i> Save Changes
                        </button>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="toast" id="toast">
    <i class="bi bi-check-circle-fill"></i>
    <span id="toastMsg"></span>
</div>

</body>
</html>
