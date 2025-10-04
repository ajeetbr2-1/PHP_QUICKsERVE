<?php
/**
 * QuickServe - Registration Page
 */

require_once 'config/auth.php';

$auth = new Auth();
$error = '';
$success = '';

// If already logged in, redirect
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $role = $_POST['role'] ?? 'customer';

    $result = $auth->register($email, $password, $full_name, $phone, $role);

    if ($result['success']) {
        $success = $result['message'] . ' Please login to continue.';
    } else {
        $error = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - QuickServe</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>\">
</head>
<body>
    <div class="container">
        <!-- Navigation -->
        <nav class="navbar">
            <div class="navbar-content glass">
                <div class="logo">
                    <span class="logo-icon">📍</span>
                    <span>QuickServe</span>
                </div>
                <ul class="nav-links">
                    <li><a href="index.php">🏠 Home</a></li>
                    <li><a href="login.php">🔐 Login</a></li>
                </ul>
            </div>
        </nav>

        <!-- Registration Form -->
        <div class="auth-container">
            <div class="auth-box glass">
                <h1>Join QuickServe</h1>
                <p>Create your account to get started</p>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success); ?>
                        <br><br>
                        <a href="login.php" class="btn btn-primary">Go to Login</a>
                    </div>
                <?php else: ?>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input 
                                type="text" 
                                id="full_name" 
                                name="full_name" 
                                class="form-control" 
                                placeholder="Enter your full name" 
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-control" 
                                placeholder="your@email.com" 
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone" 
                                class="form-control" 
                                placeholder="Enter your phone number" 
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-control" 
                                placeholder="Create a strong password" 
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="role">Register As</label>
                            <select id="role" name="role" class="form-control" required>
                                <option value="customer">Customer - I want to book services</option>
                                <option value="provider">Service Provider - I want to offer services</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                            ✨ Create Account
                        </button>
                    </form>

                    <p style="margin-top: 20px;">
                        Already have an account? <a href="login.php" style="color: #10b981; font-weight: 600;">Login</a>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; 2025 QuickServe. All rights reserved.</p>
            <p>Developed by Ajeet Kumar, Abhishek Patel, Kundan Patil</p>
        </div>
    </div>
</body>
</html>

