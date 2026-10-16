<?php
session_start();
if (!isset($_SESSION['customer'])) {
    header("Location: /customer_login.php");
    exit;
}
$customer = $_SESSION['customer'];
$viaGoogle = !empty($customer['google_id']);

$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Complete Your Profile - DeiTrack</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link rel="stylesheet" href="/assets/css/customer_login.css">
<style>
/* Let this page scroll (the shared login stylesheet locks body height) */
html, body { height: auto !important; min-height: 100%; overflow-y: auto !important; overflow-x: hidden; }
body { display: block !important; padding: 0 !important; }

.complete-wrapper {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #00a8c6;
    padding: 40px 16px;
    position: relative;
    overflow: visible;
}

.complete-wrapper::before {
    content: '';
    position: fixed;
    top: -30%;
    left: -10%;
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    pointer-events: none;
}

.complete-wrapper::after {
    content: '';
    position: fixed;
    bottom: -20%;
    right: -10%;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(0,100,255,0.15) 0%, transparent 70%);
    pointer-events: none;
}

.complete-card {
    background: white;
    border-radius: 24px;
    padding: 40px;
    width: 560px;
    max-width: 95%;
    box-shadow: 0 40px 80px rgba(0,0,0,0.25);
    animation: fadeUp 0.7s cubic-bezier(0.16, 1, 0.3, 1) both;
    position: relative;
    z-index: 1;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(30px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.complete-header {
    text-align: center;
    margin-bottom: 32px;
}

.complete-header img {
    width: 60px;
    height: 60px;
    object-fit: contain;
    margin-bottom: 16px;
}

.complete-title {
    font-family: 'DM Serif Display', serif;
    font-size: 1.8rem;
    font-weight: 400;
    color: #0d1b2a;
    margin-bottom: 8px;
}

.complete-subtitle {
    font-size: 0.88rem;
    color: #94a3b8;
    line-height: 1.5;
}

.google-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #f0f4f8;
    border-radius: 20px;
    padding: 6px 14px;
    font-size: 0.82rem;
    font-weight: 600;
    color: #0d1b2a;
    margin-bottom: 24px;
}

.google-badge img {
    width: 16px;
    height: 16px;
}

.step-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 28px;
}

.step {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.78rem;
    font-weight: 600;
    color: #94a3b8;
}

.step.active { color: #00a8c6; }
.step.done { color: #22c55e; }

.step-dot {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: 2px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 700;
}

.step.active .step-dot {
    border-color: #00a8c6;
    background: #00a8c6;
    color: white;
}

.step.done .step-dot {
    border-color: #22c55e;
    background: #22c55e;
    color: white;
}

.step-line {
    flex: 1;
    height: 2px;
    background: #e2e8f0;
    border-radius: 1px;
}

.section-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 14px;
    margin-top: 24px;
    padding-bottom: 8px;
    border-bottom: 1px solid #e2e8f0;
}

.section-label:first-of-type { margin-top: 0; }

.form-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.field-group {
    margin-bottom: 14px;
}

.field-group label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}

.field-input {
    width: 100%;
    border: 1.5px solid #e8ecf4;
    border-radius: 12px;
    padding: 11px 14px;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.9rem;
    color: #0d1b2a;
    outline: none;
    transition: 0.2s;
    background: #f5f7fb;
}

.field-input:focus {
    border-color: #00a8c6;
    background: white;
}

.field-input:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    background: #f0f4f8;
}

.field-input::placeholder { color: #b8bfcc; }

.submit-btn {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #00a8c6 0%, #0064ff 100%);
    color: white;
    border-radius: 12px;
    border: none;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.95rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
    cursor: pointer;
    margin-top: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 30px rgba(0,100,255,0.3);
}

.skip-link {
    text-align: center;
    margin-top: 16px;
    font-size: 0.82rem;
    color: #94a3b8;
}

.skip-link a {
    color: #94a3b8;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}

.skip-link a:hover { color: #00a8c6; }

.alert-msg {
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 0.87rem;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
}

.alert-msg.error { background: #fff1f1; color: #c0392b; border: 1px solid #ffd5d5; }
.alert-msg.success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
</style>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>
<body>

<div class="complete-wrapper">
    <div class="complete-card">

        <!-- HEADER -->
        <div class="complete-header">
            <img src="/src/logo.png" alt="Logo">
            <div class="complete-title">Complete Your Profile</div>
            <div class="complete-subtitle">
                <?php echo $viaGoogle ? 'You signed in with Google. ' : ''; ?>Please fill in your delivery address<br>
                so we can deliver your orders.
            </div>
        </div>

        <!-- GOOGLE BADGE -->
        <div style="text-align:center;">
            <div class="google-badge">
                <?php if ($viaGoogle): ?>
                <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google">
                <?php else: ?>
                <i class="bi bi-person-check-fill" style="color:#00a8c6;"></i>
                <?php endif; ?>
                Signed in as <?php echo htmlspecialchars($customer['email']); ?>
            </div>
        </div>

        <!-- STEP INDICATOR -->
        <div class="step-indicator">
            <div class="step done">
                <div class="step-dot"><i class="bi bi-check"></i></div>
                <?php echo $viaGoogle ? 'Google Login' : 'Account'; ?>
            </div>
            <div class="step-line"></div>
            <div class="step active">
                <div class="step-dot">2</div>
                Your Info
            </div>
            <div class="step-line"></div>
            <div class="step">
                <div class="step-dot">3</div>
                Start Shopping
            </div>
        </div>

        <!-- ALERTS -->
        <?php if ($error): ?>
        <div class="alert-msg error">
            <i class="bi bi-exclamation-circle-fill"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert-msg success">
            <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
        </div>
        <?php endif; ?>

        <!-- FORM -->
        <form action="/backend/complete_profile_process.php" method="POST">

            <!-- PERSONAL INFO -->
            <div class="section-label">Personal Information</div>

            <div class="form-row-2">
                <div class="field-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" class="field-input"
                           value="<?php echo htmlspecialchars($customer['name'] ?? ''); ?>"
                           placeholder="Juan Dela Cruz" required>
                </div>
                <div class="field-group">
                    <label>Phone Number *</label>
                    <input type="text" name="phone" class="field-input"
                           value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>"
                           placeholder="09XX XXX XXXX" required>
                </div>
            </div>

            <div class="field-group">
                <label>Email Address</label>
                <input type="email" class="field-input"
                       value="<?php echo htmlspecialchars($customer['email']); ?>"
                       disabled>
            </div>

            <!-- DELIVERY ADDRESS -->
            <div class="section-label">Delivery Address</div>

            <div class="field-group">
                <label>Home Address *</label>
                <input type="text" name="home_address" class="field-input"
                       value="<?php echo htmlspecialchars($customer['home_address'] ?? ''); ?>"
                       placeholder="House No., Street Name" required>
            </div>

            <div class="form-row-2">
                <div class="field-group">
                    <label>Region *</label>
                    <select name="region" id="addr_region" class="field-input"
                            data-initial="<?php echo htmlspecialchars($customer['region'] ?? ''); ?>" disabled required>
                        <option value="">Loading...</option>
                    </select>
                </div>
                <div class="field-group">
                    <label>Province *</label>
                    <select name="province" id="addr_province" class="field-input"
                            data-initial="<?php echo htmlspecialchars($customer['province'] ?? ''); ?>" disabled required>
                        <option value="">Select region first</option>
                    </select>
                </div>
            </div>

            <div class="form-row-2">
                <div class="field-group">
                    <label>Municipality / City *</label>
                    <select name="municipality" id="addr_city" class="field-input"
                            data-initial="<?php echo htmlspecialchars($customer['municipality'] ?? ''); ?>" disabled required>
                        <option value="">Select province first</option>
                    </select>
                </div>
                <div class="field-group">
                    <label>Barangay *</label>
                    <select name="barangay" id="addr_barangay" class="field-input"
                            data-initial="<?php echo htmlspecialchars($customer['barangay'] ?? ''); ?>" disabled required>
                        <option value="">Select city first</option>
                    </select>
                </div>
            </div>

            <div class="form-row-2">
                <div class="field-group">
                    <label>Zip Code *</label>
                    <input type="text" name="zip_code" class="field-input"
                           value="<?php echo htmlspecialchars($customer['zip_code'] ?? ''); ?>"
                           placeholder="1234" required>
                </div>
            </div>

            <div class="field-group">
                <label>Pin Your Exact Location *</label>
                <div id="addr_map" style="height:260px;border-radius:12px;overflow:hidden;border:1.5px solid #e2e8f0;"></div>
                <button type="button" id="addr_locate_btn" class="field-input"
                        style="margin-top:10px;cursor:pointer;text-align:center;">
                    <i class="bi bi-crosshair"></i> Use My Current Location
                </button>
                <input type="hidden" name="latitude" id="addr_lat" value="<?php echo htmlspecialchars($customer['latitude'] ?? ''); ?>">
                <input type="hidden" name="longitude" id="addr_lng" value="<?php echo htmlspecialchars($customer['longitude'] ?? ''); ?>">
            </div>

            <button type="submit" class="submit-btn">
                <i class="bi bi-arrow-right-circle"></i>
                Save & Start Shopping
            </button>

        </form>

        <div class="skip-link">
            <a href="/customer/dashboard.php">Skip for now →</a>
        </div>

    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="/assets/js/address_picker.js"></script>
</body>
</html>