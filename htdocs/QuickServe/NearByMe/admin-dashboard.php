<?php
/**
 * QuickServe - Admin Dashboard
 * Complete platform management
 */

require_once 'config/auth.php';
require_once 'config/database.php';

$auth = new Auth();
$auth->requireRole('admin');

$user = $auth->getCurrentUser();
$db = new Database();
$conn = $db->getConnection();

// Get all users
$users_result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
$users = [];
while ($row = $users_result->fetch_assoc()) {
    $users[] = $row;
}

// Get all services
$services_result = $conn->query("SELECT s.*, u.full_name as provider_name FROM services s JOIN users u ON s.provider_id = u.id ORDER BY s.created_at DESC");
$services = [];
while ($row = $services_result->fetch_assoc()) {
    $services[] = $row;
}

// Get all bookings
$bookings_result = $conn->query("SELECT b.*, s.title as service_title, c.full_name as customer_name, p.full_name as provider_name 
                                 FROM bookings b 
                                 JOIN services s ON b.service_id = s.id 
                                 JOIN users c ON b.customer_id = c.id 
                                 JOIN users p ON b.provider_id = p.id 
                                 ORDER BY b.created_at DESC LIMIT 50");
$bookings = [];
while ($row = $bookings_result->fetch_assoc()) {
    $bookings[] = $row;
}

// Get statistics
$total_users = count($users);
$total_customers = count(array_filter($users, function($u) { return $u['role'] === 'customer'; }));
$total_providers = count(array_filter($users, function($u) { return $u['role'] === 'provider'; }));
$total_services = count($services);
$active_services = count(array_filter($services, function($s) { return $s['is_active']; }));
$total_bookings = count($bookings);
$total_revenue = array_sum(array_column($bookings, 'total_amount'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - QuickServe</title>
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
                    <li><a href="admin-dashboard.php">📊 Dashboard</a></li>
                    <li><a href="admin-services.php">🛠️ Services</a></li>
                    <li><a href="admin-bookings.php">📋 Bookings</a></li>
                    <li><a href="logout.php" class="btn btn-secondary">🚪 Logout</a></li>
                </ul>
            </div>
        </nav>

        <!-- Dashboard Header -->
        <div class="dashboard-header glass" style="padding: 30px; margin-bottom: 30px;">
            <h1>⚙️ Admin Dashboard</h1>
            <p>Complete platform management and analytics</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card glass">
                <div class="stat-value"><?php echo $total_users; ?></div>
                <div class="stat-label">👥 Total Users</div>
            </div>
            <div class="stat-card glass">
                <div class="stat-value"><?php echo $total_providers; ?></div>
                <div class="stat-label">👔 Providers</div>
            </div>
            <div class="stat-card glass">
                <div class="stat-value"><?php echo $total_services; ?></div>
                <div class="stat-label">🛠️ Services</div>
            </div>
            <div class="stat-card glass">
                <div class="stat-value"><?php echo $total_bookings; ?></div>
                <div class="stat-label">📅 Bookings</div>
            </div>
        </div>

        <!-- Quick Stats Row -->
        <div class="stats-grid">
            <div class="stat-card glass">
                <div class="stat-value"><?php echo $total_customers; ?></div>
                <div class="stat-label">👤 Customers</div>
            </div>
            <div class="stat-card glass">
                <div class="stat-value"><?php echo $active_services; ?></div>
                <div class="stat-label">✅ Active Services</div>
            </div>
            <div class="stat-card glass">
                <div class="stat-value">₹<?php echo number_format($total_revenue, 2); ?></div>
                <div class="stat-label">💰 Total Revenue</div>
            </div>
            <div class="stat-card glass">
                <div class="stat-value"><?php echo count(array_filter($users, function($u) { return $u['is_active']; })); ?></div>
                <div class="stat-label">🟢 Active Users</div>
            </div>
        </div>

        <!-- Users Management -->
        <div class="glass" style="padding: 30px; margin-top: 30px; margin-bottom: 30px;">
            <h2>👥 User Management</h2>
            
            <!-- Filter Tabs -->
            <div style="display: flex; gap: 10px; margin: 20px 0; flex-wrap: wrap;">
                <button class="btn btn-primary" onclick="filterUsers('all')">All Users (<?php echo $total_users; ?>)</button>
                <button class="btn btn-secondary" onclick="filterUsers('customer')">Customers (<?php echo $total_customers; ?>)</button>
                <button class="btn btn-secondary" onclick="filterUsers('provider')">Providers (<?php echo $total_providers; ?>)</button>
                <button class="btn btn-secondary" onclick="filterUsers('admin')">Admins</button>
            </div>

            <div class="users-grid" id="usersGrid">
                <?php foreach ($users as $user_item): ?>
                    <div class="user-card glass" data-role="<?php echo $user_item['role']; ?>">
                        <div class="user-name">
                            <?php echo htmlspecialchars($user_item['full_name']); ?>
                            <?php if (!$user_item['is_active']): ?>
                                <span class="badge badge-cancelled">Inactive</span>
                            <?php endif; ?>
                        </div>
                        <div class="user-email">
                            📧 <?php echo htmlspecialchars($user_item['email']); ?>
                        </div>
                        <?php if ($user_item['phone']): ?>
                            <div style="opacity: 0.8; margin-top: 5px;">
                                📞 <?php echo htmlspecialchars($user_item['phone']); ?>
                            </div>
                        <?php endif; ?>
                        <div style="margin-top: 10px;">
                            <span class="badge badge-<?php echo $user_item['role'] === 'admin' ? 'in_progress' : 'confirmed'; ?>">
                                <?php echo ucfirst($user_item['role']); ?>
                            </span>
                        </div>
                        <div style="margin-top: 10px; font-size: 0.85rem; opacity: 0.7;">
                            Joined: <?php echo date('d M Y', strtotime($user_item['created_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- All Services -->
        <div class="glass" style="padding: 30px; margin-bottom: 30px;">
            <h2>🛠️ All Services</h2>
            
            <?php if (empty($services)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🛠️</div>
                    <h3>No Services Yet</h3>
                </div>
            <?php else: ?>
                <table class="table" style="margin-top: 20px;">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Provider</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Location</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($service['title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($service['provider_name']); ?></td>
                                <td><span class="badge badge-confirmed"><?php echo htmlspecialchars($service['category']); ?></span></td>
                                <td><strong>₹<?php echo number_format($service['price'], 2); ?></strong></td>
                                <td>📍 <?php echo htmlspecialchars($service['location']); ?></td>
                                <td>
                                    <?php if ($service['is_active']): ?>
                                        <span class="badge badge-completed">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-cancelled">Inactive</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Recent Bookings -->
        <div class="table-container glass">
            <h2>📋 Recent Bookings (Last 50)</h2>

            <?php if (empty($bookings)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📅</div>
                    <h3>No Bookings Yet</h3>
                </div>
            <?php else: ?>
                <table class="table" style="margin-top: 20px;">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Customer</th>
                            <th>Provider</th>
                            <th>Date & Time</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($booking['service_title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
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
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; 2025 QuickServe - Your Local Service Marketplace</p>
            <p>Admin Panel - Complete Platform Management</p>
        </div>
    </div>

    <script>
        function filterUsers(role) {
            const cards = document.querySelectorAll('.user-card');
            cards.forEach(card => {
                if (role === 'all' || card.dataset.role === role) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>

