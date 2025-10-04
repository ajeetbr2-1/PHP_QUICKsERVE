<?php
/**
 * Near By Me - Authentication Handler
 * Handles user login, registration, and session management
 */

session_start();
require_once 'database.php';

class Auth {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    // Register new user
    public function register($email, $password, $full_name, $phone, $role = 'customer') {
        // Validate input
        if (empty($email) || empty($password) || empty($full_name)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }

        // Check if email already exists
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return ['success' => false, 'message' => 'Email already registered'];
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Insert user
        $stmt = $this->conn->prepare("INSERT INTO users (email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $email, $hashed_password, $full_name, $phone, $role);

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Registration successful'];
        } else {
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }

    // Login user
    public function login($email, $password) {
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email and password are required'];
        }

        $stmt = $this->conn->prepare("SELECT id, email, password, full_name, role, is_active FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        $user = $result->fetch_assoc();

        if (!$user['is_active']) {
            return ['success' => false, 'message' => 'Account is deactivated'];
        }

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            return ['success' => true, 'message' => 'Login successful', 'role' => $user['role']];
        } else {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
    }

    // Logout user
    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }

    // Check if user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    // Get current user info
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['email'],
            'full_name' => $_SESSION['full_name'],
            'role' => $_SESSION['role']
        ];
    }

    // Check user role
    public function hasRole($role) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        return $_SESSION['role'] === $role;
    }

    // Require login
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }

    // Require specific role
    public function requireRole($role) {
        $this->requireLogin();
        if (!$this->hasRole($role)) {
            header('Location: index.php');
            exit();
        }
    }
}
?>
