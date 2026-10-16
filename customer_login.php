<?php
session_start();

$error = "";
$success = "";

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Default tab
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Portal - DeiTrack</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link rel="stylesheet" href="/assets/css/customer_login.css?v=<?php echo filemtime(__DIR__ . '/assets/css/customer_login.css'); ?>">
</head>
<body>

<div class="auth-wrapper">

    <!-- LEFT PANEL -->
    <div class="left-panel">
        <div class="brand">
            <img src="/src/logo.png" alt="Logo">
            <span class="brand-name">DeiTrack</span>
        </div>

        <div class="left-content">
            <h2>Your Tissue <br><span>Supplier.</span></h2>
            <p>Order quality tissue and paper products online and have them delivered to your door.</p>

            <div class="feature-check">
                <div><i class="bi bi-bag-check-fill"></i> Order products online</div>
                <div><i class="bi bi-geo-alt-fill"></i> Track your delivery</div>
                <div><i class="bi bi-clock-history"></i> View order history</div>
            </div>
        </div>

        <div class="left-footer">
            © 2026 Bathala Tissue Supplier. All rights reserved.
        </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="right-panel">

        <!-- TABS -->
        <div class="auth-tabs">
            <button class="auth-tab <?php echo $tab === 'login' ? 'active' : ''; ?>"
                    onclick="switchTab('login')">
                Sign In
            </button>
            <button class="auth-tab <?php echo $tab === 'register' ? 'active' : ''; ?>"
                    onclick="switchTab('register')">
                Create Account
            </button>
        </div>

        <!-- ERROR / SUCCESS -->
        <?php if ($error): ?>
            <div class="alert-custom error">
                <i class="bi bi-exclamation-circle-fill"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert-custom success">
                <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- LOGIN FORM -->
        <div class="auth-form" id="loginForm" style="display: <?php echo $tab === 'login' ? 'block' : 'none'; ?>">
            <h3>Welcome Back</h3>
            <p class="subtitle">Sign in to your customer account</p>

            <form action="/backend/customer_login_process.php" method="POST">
                <label class="form-label" for="loginEmail">Email Address</label>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" id="loginEmail" name="email" class="form-control" placeholder="you@example.com" autocomplete="email" required>
                </div>

                <label class="form-label" for="loginPassword">Password</label>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" id="loginPassword" name="password" class="form-control" placeholder="Your password" autocomplete="current-password" required>
                    <span class="input-group-text" onclick="togglePassword('loginPassword', 'loginEye')" title="Show password">
                        <i class="bi bi-eye" id="loginEye"></i>
                    </span>
                </div>

                <button type="submit" class="btn-auth">Sign In</button>
            </form>

            <div class="or-divider"><span>or</span></div>

            <a href="/backend/google_login.php" class="btn-google">
                <img src="https://developers.google.com/identity/images/g-logo.png" width="18" height="18" alt="">
                Continue with Google
            </a>

            <div class="auth-switch">
                Don't have an account?
                <a href="#" onclick="switchTab('register'); return false;">Create one</a>
            </div>
        </div>

        <!-- REGISTER FORM — just the basics; address + map pin come next on complete_profile.php -->
        <div class="auth-form" id="registerForm" style="display: <?php echo $tab === 'register' ? 'block' : 'none'; ?>">
            <h3>Create Account</h3>
            <p class="subtitle">Join us and start ordering online</p>

            <form action="/backend/customer_register_process.php" method="POST">
                <label class="form-label" for="regName">Full Name</label>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" id="regName" name="name" class="form-control" placeholder="Juan Dela Cruz" autocomplete="name" required>
                </div>

                <label class="form-label" for="regEmail">Email Address</label>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" id="regEmail" name="email" class="form-control" placeholder="you@example.com" autocomplete="email" required>
                </div>

                <label class="form-label" for="regPhone">Phone Number</label>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                    <input type="tel" id="regPhone" name="phone" class="form-control" placeholder="09XX XXX XXXX" autocomplete="tel" inputmode="tel" required>
                </div>

                <label class="form-label" for="registerPassword">Password</label>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" id="registerPassword" name="password" class="form-control" placeholder="At least 8 characters" minlength="8" autocomplete="new-password" required>
                    <span class="input-group-text" onclick="togglePassword('registerPassword', 'registerEye')" title="Show password">
                        <i class="bi bi-eye" id="registerEye"></i>
                    </span>
                </div>

                <p class="form-hint"><i class="bi bi-geo-alt"></i> You'll add your delivery address on the next step.</p>

                <button type="submit" class="btn-auth">Create Account</button>
            </form>

            <div class="or-divider"><span>or</span></div>

            <a href="/backend/google_login.php" class="btn-google">
                <img src="https://developers.google.com/identity/images/g-logo.png" width="18" height="18" alt="">
                Continue with Google
            </a>

            <div class="auth-switch">
                Already have an account?
                <a href="#" onclick="switchTab('login'); return false;">Sign in</a>
            </div>
        </div>

        <div class="back-link">
            <a href="/index.html">← Back to Home</a>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/customer_login.js"></script>
</body>
</html>
