<?php
/**
 * QuickServe - Login Page
 */

require_once 'config/auth.php';

$auth = new Auth();
$error = '';
$success = '';

// If already logged in, redirect based on role
if ($auth->isLoggedIn()) {
    $user = $auth->getCurrentUser();
    switch ($user['role']) {
        case 'admin':
            header('Location: admin-dashboard.php');
            break;
        case 'provider':
            header('Location: provider-dashboard.php');
            break;
        default:
            header('Location: customer-dashboard.php');
            break;
    }
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $result = $auth->login($email, $password);

    if ($result['success']) {
        // Redirect based on role
        switch ($result['role']) {
            case 'admin':
                header('Location: admin-dashboard.php');
                break;
            case 'provider':
                header('Location: provider-dashboard.php');
                break;
            default:
                header('Location: customer-dashboard.php');
                break;
        }
        exit();
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
    <title>Login - QuickServe</title>
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
                    <li><a href="register.php">✨ Sign Up</a></li>
                </ul>
            </div>
        </nav>

        <!-- Login Form -->
        <div class="auth-container">
            <div class="auth-box glass">
                <h1>Welcome Back!</h1>
                <p>Please login to continue</p>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
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
                        <label for="password">Password</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control" 
                            placeholder="Enter your password" 
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                        🔐 Login
                    </button>
                </form>

                <p style="margin-top: 20px;">
                    Don't have an account? <a href="register.php" style="color: #10b981; font-weight: 600;">Register</a>
                </p>

                <div class="glass" style="margin-top: 30px; padding: 20px; font-size: 0.9rem;">
                    <strong>Demo Credentials:</strong><br>
                    <strong>Admin:</strong> admin@nearbyme.com / admin123<br>
                    <strong>Provider:</strong> john.smith@example.com / provider123<br>
                    <strong>Customer:</strong> alice@example.com / customer123
                </div>
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

