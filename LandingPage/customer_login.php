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
<link rel="stylesheet" href="/Bathala/assets/css/customer_login.css">
</head>
<body>

<div class="auth-wrapper">

    <!-- LEFT PANEL -->
    <div class="left-panel">
        <div class="brand">
            <img src="/Bathala/src/logo.png" alt="Logo">
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
                <i class="bi bi-exclamation-circle-fill"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert-custom success">
                <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- LOGIN FORM -->
        <div class="auth-form" id="loginForm" style="display: <?php echo $tab === 'login' ? 'block' : 'none'; ?>">
            <h3>Welcome Back</h3>
            <p class="subtitle">Sign in to your customer account</p>

            <form action="/Bathala/backend/customer_login_process.php" method="POST">
                <label class="form-label">Email Address</label>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
                </div>

                <label class="form-label">Password</label>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" id="loginPassword" name="password" class="form-control" placeholder="••••••••" required>
                    <span class="input-group-text" onclick="togglePassword('loginPassword', 'loginEye')" style="cursor:pointer;">
                        <i class="bi bi-eye" id="loginEye"></i>
                    </span>
                </div>

                <button type="submit" class="btn-auth w-100">
                    <span>Sign In</span>
                </button>
            </form>

            <div class="auth-switch">
                Don't have an account?
                <a href="#" onclick="switchTab('register')">Create one</a>
            </div>
        </div>
<!-- DIVIDER -->
<div style="display:flex;align-items:center;gap:10px;margin:16px 0;">
    <div style="flex:1;height:1px;background:#e2e8f0;"></div>
    <span style="font-size:0.8rem;color:#94a3b8;">or continue with</span>
    <div style="flex:1;height:1px;background:#e2e8f0;"></div>
</div>

<!-- GOOGLE BUTTON -->
<a href="/Bathala/backend/google_login.php" class="btn-google">
    <img src="https://developers.google.com/identity/images/g-logo.png" width="20">
    Continue with Google
</a>
        <!-- REGISTER FORM -->
        <div class="auth-form" id="registerForm" style="display: <?php echo $tab === 'register' ? 'block' : 'none'; ?>">
            <h3>Create Account</h3>
            <p class="subtitle">Join us and start ordering online</p>

            <form action="/Bathala/backend/customer_register_process.php" method="POST">
                <label class="form-label">Full Name</label>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="name" class="form-control" placeholder="Juan Dela Cruz" required>
                </div>

                <label class="form-label">Email Address</label>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
                </div>

                <label class="form-label">Phone Number</label>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                    <input type="text" name="phone" class="form-control" placeholder="09XX XXX XXXX" required>
                </div>

                <div class="form-row">
    <div class="input-wrap">
        <label class="form-label">Home Address</label>
        <div class="input-group mb-3">
            <span class="input-group-text"><i class="bi bi-house"></i></span>
            <input type="text" name="home_address" class="form-control" placeholder="House No., Street" required>
        </div>
    </div>
    <div class="input-wrap">
        <label class="form-label">Barangay</label>
        <div class="input-group mb-3">
            <span class="input-group-text"><i class="bi bi-geo"></i></span>
            <input type="text" name="barangay" class="form-control" placeholder="Barangay" required>
        </div>
    </div>
</div>

<div class="form-row">
    <div class="input-wrap">
        <label class="form-label">Municipality / City</label>
        <div class="input-group mb-3">
            <span class="input-group-text"><i class="bi bi-building"></i></span>
            <input type="text" name="municipality" class="form-control" placeholder="Municipality or City" required>
        </div>
    </div>
    <div class="input-wrap">
        <label class="form-label">Zip Code</label>
        <div class="input-group mb-3">
            <span class="input-group-text"><i class="bi bi-mailbox"></i></span>
            <input type="text" name="zip_code" class="form-control" placeholder="1234" required>
        </div>
    </div>
</div>

                <label class="form-label">Password</label>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" id="registerPassword" name="password" class="form-control" placeholder="••••••••" required>
                    <span class="input-group-text" onclick="togglePassword('registerPassword', 'registerEye')" style="cursor:pointer;">
                        <i class="bi bi-eye" id="registerEye"></i>
                    </span>
                </div>

                <button type="submit" class="btn-auth w-100">
                    <span>Create Account</span>
                </button>
            </form>

            <div class="auth-switch">
                Already have an account?
                <a href="#" onclick="switchTab('login')">Sign in</a>
            </div>
        </div>

        <div class="back-link">
            <a href="/Bathala/LandingPage/index.html">← Back to Home</a>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/Bathala/assets/js/customer_login.js"></script>
</body>
</html>
