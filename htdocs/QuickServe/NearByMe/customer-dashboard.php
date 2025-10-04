<?php
/**
 * QuickServe - Customer Dashboard
 * View and manage bookings
 */

require_once 'config/auth.php';
require_once 'config/database.php';

$auth = new Auth();
$auth->requireRole('customer');

$user = $auth->getCurrentUser();
$db = new Database();
$conn = $db->getConnection();

// Get customer's bookings
$user_id = $user['id'];
$sql = "SELECT b.*, s.title as service_title, s.price, u.full_name as provider_name, u.phone as provider_phone
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        JOIN users u ON b.provider_id = u.id
        WHERE b.customer_id = ?
        ORDER BY b.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

// Get statistics
$total_bookings = count($bookings);
$completed_bookings = count(array_filter($bookings, function($b) { return $b['status'] === 'completed'; }));
$pending_bookings = count(array_filter($bookings, function($b) { return $b['status'] === 'pending'; }));
$total_spent = array_sum(array_column($bookings, 'total_amount'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - QuickServe</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>\">
</head>
<body>
    <div class="container">
        <!-- Navigation -->
        <nav class="navbar">
            <div class="navbar-content glass">
                <div class="logo">
                    <span class="logo-icon">🚀</span>
                    <span>QuickServe</span>
                </div>
                <ul class="nav-links">
                    <li><a href="index.php">🏠 Home</a></li>
                    <li><a href="customer-dashboard.php">📊 My Dashboard</a></li>
                    <li><a href="chat.php">💬 Messages</a></li>
                    <li><a href="edit-profile.php">✏️ Profile</a></li>
                    <li><a href="logout.php" class="btn btn-secondary">🚪 Logout</a></li>
                </ul>
            </div>
        </nav>

        <!-- Dashboard Header -->
        <div class="dashboard-header glass" style="padding: 30px; margin-bottom: 30px;">
            <h1>👋 Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
            <p>Manage your bookings and explore new services</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card glass">
                <div class="stat-value"><?php echo $total_bookings; ?></div>
                <div class="stat-label">📅 Total Bookings</div>
            </div>
            <div class="stat-card glass">
                <div class="stat-value"><?php echo $pending_bookings; ?></div>
                <div class="stat-label">⏳ Pending</div>
            </div>
            <div class="stat-card glass">
                <div class="stat-value"><?php echo $completed_bookings; ?></div>
                <div class="stat-label">✅ Completed</div>
            </div>
            <div class="stat-card glass">
                <div class="stat-value">₹<?php echo number_format($total_spent, 2); ?></div>
                <div class="stat-label">💰 Total Spent</div>
            </div>
        </div>

        <!-- Bookings Table -->
        <div class="table-container glass">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>📋 My Bookings</h2>
                <a href="index.php" class="btn btn-primary">➕ Book New Service</a>
            </div>

            <?php if (empty($bookings)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📅</div>
                    <h3>No Bookings Yet</h3>
                    <p>Start by booking your first service!</p>
                    <a href="index.php" class="btn btn-primary" style="margin-top: 20px;">Browse Services</a>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Provider</th>
                            <th>Date & Time</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($booking['service_title']); ?></strong>
                                    <?php if ($booking['notes']): ?>
                                        <br><small style="opacity: 0.7;">Note: <?php echo htmlspecialchars($booking['notes']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($booking['provider_name']); ?></td>
                                <td>
                                    📅 <?php echo date('d M Y', strtotime($booking['booking_date'])); ?>
                                    <br>
                                    🕒 <?php echo date('h:i A', strtotime($booking['booking_time'])); ?>
                                </td>
                                <td><strong>₹<?php echo number_format($booking['total_amount'], 2); ?></strong></td>
                                <td>
                                    <span class="badge badge-<?php echo $booking['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    📞 <?php echo htmlspecialchars($booking['provider_phone']); ?>
                                    <br>
                                    <!-- Chat Button -->
                                    <a href="chat.php?provider_id=<?php echo $booking['provider_id']; ?>&service_id=<?php echo $booking['service_id']; ?>" class="btn btn-primary" style="margin-top: 8px; padding: 6px 15px; font-size: 0.85rem; text-decoration: none; display: inline-block;">
                                        💬 Chat with Provider
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Profile Section -->
        <div class="glass" style="padding: 30px; margin-top: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>👤 My Profile</h2>
                <a href="edit-profile.php" class="btn btn-primary">✏️ Edit Profile</a>
            </div>
            <div style="margin-top: 20px;">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></p>
                <p><strong>Role:</strong> <span class="badge badge-confirmed"><?php echo ucfirst($user['role']); ?></span></p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; 2025 QuickServe - Your Local Service Marketplace</p>
        </div>
    </div>
</body>
</html>

