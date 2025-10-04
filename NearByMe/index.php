<?php
/**
 * Near By Me - Homepage
 * Browse and search services
 */

require_once 'config/auth.php';
require_once 'config/database.php';

$auth = new Auth();
$db = new Database();
$conn = $db->getConnection();

$user = $auth->getCurrentUser();

// Get search parameters
$search_query = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

// Build SQL query
$sql = "SELECT s.*, u.full_name as provider_name, u.rating as provider_rating 
        FROM services s 
        JOIN users u ON s.provider_id = u.id 
        WHERE s.is_active = 1";

if (!empty($search_query)) {
    $sql .= " AND (s.title LIKE '%$search_query%' OR s.description LIKE '%$search_query%' OR s.location LIKE '%$search_query%')";
}

if (!empty($category_filter)) {
    $sql .= " AND s.category = '$category_filter'";
}

$sql .= " ORDER BY s.created_at DESC LIMIT 50";

$result = $conn->query($sql);
$services = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
}

// Get unique categories
$categories_result = $conn->query("SELECT DISTINCT category FROM services WHERE is_active = 1 ORDER BY category");
$categories = [];
if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Near By Me - Find Local Services</title>
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
                    <?php if ($user): ?>
                        <?php if ($user['role'] === 'customer'): ?>
                            <li><a href="customer-dashboard.php">📊 My Bookings</a></li>
                        <?php elseif ($user['role'] === 'provider'): ?>
                            <li><a href="provider-dashboard.php">💼 Dashboard</a></li>
                        <?php elseif ($user['role'] === 'admin'): ?>
                            <li><a href="admin-dashboard.php">⚙️ Admin Panel</a></li>
                        <?php endif; ?>
                        <li><a href="logout.php" class="btn btn-secondary">🚪 Logout (<?php echo htmlspecialchars($user['full_name']); ?>)</a></li>
                    <?php else: ?>
                        <li><a href="login.php" class="btn btn-primary">🔐 Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>

        <!-- Search Section -->
        <div class="search-section">
            <div class="search-box glass">
                <h2 style="margin-bottom: 20px;">🔍 Find Services Near You</h2>
                <form method="GET" action="index.php">
                    <div class="search-container">
                        <input 
                            type="text" 
                            name="search" 
                            class="form-control search-input" 
                            placeholder="Search for services, location..." 
                            value="<?php echo htmlspecialchars($search_query); ?>"
                        >
                        <select name="category" class="form-control">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $cat === $category_filter ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary">🔎 Search</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Services Grid -->
        <div class="services-grid">
            <?php if (empty($services)): ?>
                <div class="glass" style="grid-column: 1 / -1;">
                    <div class="empty-state">
                        <div class="empty-state-icon">🔍</div>
                        <h3>No Services Found</h3>
                        <p>Try adjusting your search criteria</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($services as $service): ?>
                    <div class="service-card glass">
                        <div class="service-header">
                            <div>
                                <h3 class="service-title"><?php echo htmlspecialchars($service['title']); ?></h3>
                                <span class="service-category">
                                    <?php echo htmlspecialchars($service['category']); ?>
                                </span>
                            </div>
                            <div class="service-price">
                                ₹<?php echo number_format($service['price'], 2); ?>
                            </div>
                        </div>

                        <p class="service-description">
                            <?php echo htmlspecialchars(substr($service['description'], 0, 120)) . '...'; ?>
                        </p>

                        <div class="service-meta">
                            <span>👤 <?php echo htmlspecialchars($service['provider_name']); ?></span>
                            <span>⭐ <?php echo number_format($service['provider_rating'], 1); ?></span>
                            <span>📍 <?php echo htmlspecialchars($service['location']); ?></span>
                        </div>

                        <?php if ($user && $user['role'] === 'customer'): ?>
                            <a href="booking.php?service_id=<?php echo $service['id']; ?>" class="btn btn-primary" style="width: 100%;">
                                📅 Book Now
                            </a>
                        <?php elseif (!$user): ?>
                            <a href="login.php" class="btn btn-secondary" style="width: 100%;">
                                🔐 Login to Book
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; 2025 Near By Me - Your Local Service Marketplace</p>
            <p>Developed by Ajeet Kumar, Abhishek Patel, Kundan Patil</p>
        </div>
    </div>
</body>
</html>
