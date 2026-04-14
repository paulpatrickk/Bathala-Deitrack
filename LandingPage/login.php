<?php
session_start();
$error = "";

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - DeiTrack</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<!-- ✅ YOUR CSS -->
<link rel="stylesheet" href="/Bathala/assets/css/login.css">

</head>

<body>

<div class="login-container">

    <!-- LEFT -->
    <div class="left-panel">

        <div class="brand">
            <img src="\Bathala\src\logo.png" alt="Logo">
            <span class="brand-name">DeiTrack: Bathala Tissue</span>
        </div>

        <div class="left-content">
            <h2>Welcome <br><span>Back.</span></h2>
            <p>Login to manage your inventory and<br>deliveries in real-time.</p>

            <div class="feature-check">
                <div><i class="bi bi-geo-alt-fill"></i> Real-time GPS Tracking</div>
                <div><i class="bi bi-shield-lock-fill"></i> Secure Login System</div>
                <div><i class="bi bi-bar-chart-fill"></i> Smart Dashboard & Analytics</div>
            </div>
        </div>

        <div class="left-footer">
            © 2026 Bathala Tissue Supplier. All rights reserved.
        </div>

    </div>

    <!-- RIGHT -->
    <div class="right-panel">

        <h3>Sign In</h3>
        <p class="subtitle">Enter your credentials to continue</p>

        <?php if ($error): ?>
            <div class="alert-custom">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="../backend/login_process.php" method="POST">

            <!-- EMAIL -->
            <label class="form-label">Email Address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
            </div>

            <!-- PASSWORD -->
            <label class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                <span class="input-group-text" onclick="togglePassword()">
                    <i class="bi bi-eye" id="eyeIcon"></i>
                </span>
            </div>

            <button type="submit" class="btn btn-login w-100">
                <span>Login to Dashboard</span>
            </button>

        </form>

        <div class="back-link">
            <a href="/Bathala/LandingPage/index.html">← Back to Landing Page</a>
        </div>

    </div>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- ✅ YOUR JS -->
<script src="/Bathala/assets/js/login.js"></script>

</body>
</html>