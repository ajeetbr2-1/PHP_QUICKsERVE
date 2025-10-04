<?php
/**
 * Near By Me - Provider Dashboard
 * Manage services and bookings
 */

require_once 'config/auth.php';
require_once 'config/database.php';

$auth = new Auth();
$auth->requireRole('provider');

$user = $auth->getCurrentUser();
$db = new Database();
$conn = $db->getConnection();

// Get provider's services
$user_id = $user['id'];
$sql = "SELECT * FROM services WHERE provider_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$services = [];
while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}

// Get provider's bookings
$sql = "SELECT b.*, s.title as service_title, u.full_name as customer_name, u.phone as customer_phone, u.email as customer_email
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        JOIN users u ON b.customer_id = u.id
        WHERE b.provider_id = ?
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
$total_services = count($services);
$active_services = count(array_filter($services, function($s) { return $s['is_active']; }));
$total_bookings = count($bookings);
$total_earnings = array_sum(array_column($bookings, 'total_amount'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provider Dashboard - Near By Me</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <!-- Navigation -->
        <nav class="navbar">
            <div class="navbar-content glass">
                <div class="logo">
                    <span class="logo-icon">📍</span>
                    <span>Near By Me</span>
                </div>
                <ul class="nav-links">
                    <li><a href="index.php">🏠 Home</a></li>
                    <li><a href="provider-dashboard.php">💼 Dashboard</a></li>
                    <li><a href="manage-portfolio.php">📂 Portfolio</a></li>
                    <li><a href="edit-profile.php">✏️ Edit Profile</a></li>
                    <li><a href="logout.php" class="btn btn-secondary">🚪 Logout</a></li>
                </ul>
            </div>
        </nav>

        <!-- Dashboard Header -->
        <div class="dashboard-header glass" style="padding: 30px; margin-bottom: 30px;">
            <h1>💼 Provider Dashboard</h1>
            <p>Welcome, <?php echo htmlspecialchars($user['full_name']); ?>! Manage your services and bookings</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card glass">
                <div class="stat-value"><?php echo $total_services; ?></div>
                <div class="stat-label">🛠️ Total Services</div>
            </div>
            <div class="stat-card glass">
                <div class="stat-value"><?php echo $active_services; ?></div>
                <div class="stat-label">✅ Active Services</div>
            </div>
            <div class="stat-card glass">
                <div class="stat-value"><?php echo $total_bookings; ?></div>
                <div class="stat-label">📅 Total Bookings</div>
            </div>
            <div class="stat-card glass">
                <div class="stat-value">₹<?php echo number_format($total_earnings, 2); ?></div>
                <div class="stat-label">💰 Total Earnings</div>
            </div>
        </div>

        <!-- My Services -->
        <div class="glass" style="padding: 30px; margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>🛠️ My Services</h2>
                <button class="btn btn-primary" onclick="alert('Add Service feature coming soon!')">➕ Add New Service</button>
            </div>

            <?php if (empty($services)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🛠️</div>
                    <h3>No Services Yet</h3>
                    <p>Start by adding your first service!</p>
                </div>
            <?php else: ?>
                <div class="services-grid" style="margin-top: 20px;">
                    <?php foreach ($services as $service): ?>
                        <div class="service-card glass">
                            <div class="service-header">
                                <div>
                                    <h3 class="service-title"><?php echo htmlspecialchars($service['title']); ?></h3>
                                    <span class="service-category"><?php echo htmlspecialchars($service['category']); ?></span>
                                </div>
                                <div class="service-price">₹<?php echo number_format($service['price'], 2); ?></div>
                            </div>

                            <p class="service-description">
                                <?php echo htmlspecialchars(substr($service['description'], 0, 100)) . '...'; ?>
                            </p>

                            <div class="service-meta">
                                <span>📍 <?php echo htmlspecialchars($service['location']); ?></span>
                                <span>
                                    <?php if ($service['is_active']): ?>
                                        <span class="badge badge-completed">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-cancelled">Inactive</span>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <div style="display: flex; gap: 10px; margin-top: 15px;">
                                <button class="btn btn-secondary" style="flex: 1;" onclick="alert('Edit feature coming soon!')">✏️ Edit</button>
                                <button class="btn btn-secondary" style="flex: 1;" onclick="alert('Delete feature coming soon!')">🗑️ Delete</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Booking Requests -->
        <div class="table-container glass">
            <h2>📋 Booking Requests</h2>

            <?php if (empty($bookings)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📅</div>
                    <h3>No Bookings Yet</h3>
                    <p>You'll see booking requests here once customers book your services</p>
                </div>
            <?php else: ?>
                <table class="table" style="margin-top: 20px;">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Customer</th>
                            <th>Date & Time</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Contact</th>
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
                                <td>
                                    <?php echo htmlspecialchars($booking['customer_name']); ?>
                                    <br><small style="opacity: 0.7;"><?php echo htmlspecialchars($booking['customer_email']); ?></small>
                                </td>
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
                                    📞 <?php echo htmlspecialchars($booking['customer_phone']); ?>
                                    <br>
                                    <button class="btn btn-primary" style="margin-top: 5px; padding: 5px 15px; font-size: 0.85rem;" onclick="alert('Status update coming soon!')">
                                        Update Status
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; 2025 Near By Me - Your Local Service Marketplace</p>
        </div>
    </div>
</body>
</html>
