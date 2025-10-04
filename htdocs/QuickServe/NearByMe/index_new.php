<?php
/**
 * QuickServe - Homepage with Advanced Filters
 * Browse and search services with multiple filters
 */

require_once 'config/auth.php';
require_once 'config/database.php';

$auth = new Auth();
$db = new Database();
$conn = $db->getConnection();

$user = $auth->getCurrentUser();

// Get all filter parameters
$search_query = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$price_min = isset($_GET['price_min']) && $_GET['price_min'] !== '' ? floatval($_GET['price_min']) : null;
$price_max = isset($_GET['price_max']) && $_GET['price_max'] !== '' ? floatval($_GET['price_max']) : null;
$gender_filter = $_GET['gender'] ?? '';
$rating_filter = isset($_GET['rating']) && $_GET['rating'] !== '' ? floatval($_GET['rating']) : null;
$sort_by = $_GET['sort'] ?? 'newest';
$location_filter = $_GET['location'] ?? '';

// Build SQL query with JOINs
$sql = "SELECT s.*, u.full_name as provider_name, u.rating as provider_rating, u.gender as provider_gender
        FROM services s 
        JOIN users u ON s.provider_id = u.id 
        WHERE s.is_active = 1";

$params = [];
$types = '';

// Search query
if (!empty($search_query)) {
    $sql .= " AND (s.title LIKE ? OR s.description LIKE ? OR s.location LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = &$search_param;
    $params[] = &$search_param;
    $params[] = &$search_param;
    $types .= 'sss';
}

// Category filter
if (!empty($category_filter)) {
    $sql .= " AND s.category = ?";
    $params[] = &$category_filter;
    $types .= 's';
}

// Price range filter
if ($price_min !== null) {
    $sql .= " AND s.price >= ?";
    $params[] = &$price_min;
    $types .= 'd';
}
if ($price_max !== null) {
    $sql .= " AND s.price <= ?";
    $params[] = &$price_max;
    $types .= 'd';
}

// Gender filter
if (!empty($gender_filter) && $gender_filter !== 'all') {
    $sql .= " AND u.gender = ?";
    $params[] = &$gender_filter;
    $types .= 's';
}

// Rating filter
if ($rating_filter !== null) {
    $sql .= " AND u.rating >= ?";
    $params[] = &$rating_filter;
    $types .= 'd';
}

// Location filter
if (!empty($location_filter)) {
    $sql .= " AND s.location LIKE ?";
    $location_param = "%$location_filter%";
    $params[] = &$location_param;
    $types .= 's';
}

// Sorting
switch ($sort_by) {
    case 'price_low':
        $sql .= " ORDER BY s.price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY s.price DESC";
        break;
    case 'rating':
        $sql .= " ORDER BY u.rating DESC";
        break;
    case 'oldest':
        $sql .= " ORDER BY s.created_at ASC";
        break;
    default: // newest
        $sql .= " ORDER BY s.created_at DESC";
}

$sql .= " LIMIT 50";

// Execute query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$services = [];
while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}

// Get unique categories
$categories_result = $conn->query("SELECT DISTINCT category FROM services WHERE is_active = 1 ORDER BY category");
$categories = [];
if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

// Get unique locations
$locations_result = $conn->query("SELECT DISTINCT location FROM services WHERE is_active = 1 ORDER BY location LIMIT 20");
$locations = [];
if ($locations_result) {
    while ($row = $locations_result->fetch_assoc()) {
        $locations[] = $row['location'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickServe - Find Local Services</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        .filters-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        
        .filters-sidebar {
            position: sticky;
            top: 20px;
            height: fit-content;
        }
        
        .filter-section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .filter-section:last-child {
            border-bottom: none;
        }
        
        .filter-section h3 {
            margin-bottom: 15px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-input-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .filter-input-small {
            flex: 1;
            padding: 8px 12px;
            border-radius: 8px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            background: white;
            color: #111;
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .gender-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .gender-option {
            padding: 10px;
            border-radius: 8px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.1);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .gender-option:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }
        
        .gender-option.active {
            background: rgba(76, 175, 80, 0.3);
            border-color: #4CAF50;
        }
        
        .rating-stars {
            display: flex;
            gap: 8px;
            justify-content: space-between;
        }
        
        .star-option {
            padding: 8px 12px;
            border-radius: 8px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.1);
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            flex: 1;
        }
        
        .star-option:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .star-option.active {
            background: rgba(76, 175, 80, 0.3);
            border-color: #4CAF50;
        }
        
        .sort-select {
            width: 100%;
            padding: 10px 15px;
            border-radius: 10px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            background: white;
            color: #111;
            font-weight: 600;
            cursor: pointer;
        }
        
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
        }
        
        .clear-filters {
            background: rgba(244, 67, 54, 0.2);
            color: #EF9A9A;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 1px solid rgba(244, 67, 54, 0.5);
        }
        
        .clear-filters:hover {
            background: rgba(244, 67, 54, 0.3);
            transform: scale(1.05);
        }
        
        @media (max-width: 768px) {
            .filters-container {
                grid-template-columns: 1fr;
            }
            
            .filters-sidebar {
                position: relative;
                top: 0;
            }
        }
    </style>
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
                <form method="GET" action="index.php" id="filterForm">
                    <div class="search-container">
                        <input 
                            type="text" 
                            name="search" 
                            class="form-control search-input" 
                            placeholder="Search for services..." 
                            value="<?php echo htmlspecialchars($search_query); ?>"
                            style="background: white !important; color: #111 !important; font-weight: 600 !important; border: 3px solid #4CAF50 !important;"
                        >
                        <select name="category" class="form-control" style="background: white !important; color: #111 !important; font-weight: 600 !important; border: 3px solid #4CAF50 !important;">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $cat === $category_filter ? 'selected' : ''; ?> style="color: #111;">
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary" style="background: #10b981 !important; font-size: 1.1rem; padding: 14px 30px;">🔎 Search</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Filters and Results -->
        <div class="filters-container">
            <!-- Advanced Filters Sidebar -->
            <aside class="filters-sidebar glass" style="padding: 25px;">
                <h2 style="margin-bottom: 20px;">🎚️ Advanced Filters</h2>
                
                <form method="GET" action="index.php">
                    <!-- Preserve search and category -->
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
                    
                    <!-- Price Range -->
                    <div class="filter-section">
                        <h3>💰 Price Range</h3>
                        <div class="filter-input-group">
                            <input type="number" name="price_min" placeholder="Min ₹" class="filter-input-small" 
                                   value="<?php echo $price_min !== null ? $price_min : ''; ?>" min="0">
                            <span>-</span>
                            <input type="number" name="price_max" placeholder="Max ₹" class="filter-input-small" 
                                   value="<?php echo $price_max !== null ? $price_max : ''; ?>" min="0">
                        </div>
                    </div>
                    
                    <!-- Provider Gender -->
                    <div class="filter-section">
                        <h3>👤 Provider Gender</h3>
                        <select name="gender" class="filter-input-small" style="width: 100%;">
                            <option value="all" <?php echo $gender_filter === 'all' || empty($gender_filter) ? 'selected' : ''; ?>>All</option>
                            <option value="male" <?php echo $gender_filter === 'male' ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo $gender_filter === 'female' ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo $gender_filter === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <!-- Minimum Rating -->
                    <div class="filter-section">
                        <h3>⭐ Minimum Rating</h3>
                        <select name="rating" class="filter-input-small" style="width: 100%;">
                            <option value="">Any Rating</option>
                            <option value="4.5" <?php echo $rating_filter == 4.5 ? 'selected' : ''; ?>>4.5+ Stars</option>
                            <option value="4.0" <?php echo $rating_filter == 4.0 ? 'selected' : ''; ?>>4.0+ Stars</option>
                            <option value="3.5" <?php echo $rating_filter == 3.5 ? 'selected' : ''; ?>>3.5+ Stars</option>
                            <option value="3.0" <?php echo $rating_filter == 3.0 ? 'selected' : ''; ?>>3.0+ Stars</option>
                        </select>
                    </div>
                    
                    <!-- Location -->
                    <div class="filter-section">
                        <h3>📍 Location</h3>
                        <input type="text" name="location" placeholder="Enter location" class="filter-input-small" 
                               value="<?php echo htmlspecialchars($location_filter); ?>" style="width: 100%;" list="locationList">
                        <datalist id="locationList">
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?php echo htmlspecialchars($loc); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    
                    <!-- Sort By -->
                    <div class="filter-section">
                        <h3>🔃 Sort By</h3>
                        <select name="sort" class="sort-select">
                            <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo $sort_by === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="price_low" <?php echo $sort_by === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort_by === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="rating" <?php echo $sort_by === 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 10px;">
                        ✅ Apply Filters
                    </button>
                    <a href="index.php" class="clear-filters" style="display: block; text-align: center;">
                        🗑️ Clear All Filters
                    </a>
                </form>
            </aside>

            <!-- Services Results -->
            <div>
                <div class="results-header">
                    <div>
                        <strong><?php echo count($services); ?></strong> services found
                    </div>
                    <?php if (!empty($search_query) || !empty($category_filter) || $price_min !== null || $price_max !== null || !empty($gender_filter) || $rating_filter !== null || !empty($location_filter)): ?>
                        <a href="index.php" class="clear-filters">
                            🗑️ Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="services-grid">
                    <?php if (empty($services)): ?>
                        <div class="glass" style="grid-column: 1 / -1;">
                            <div class="empty-state">
                                <div class="empty-state-icon">🔍</div>
                                <h3>No Services Found</h3>
                                <p>Try adjusting your search criteria or filters</p>
                                <a href="index.php" class="btn btn-primary" style="margin-top: 15px;">
                                    🔄 Reset All Filters
                                </a>
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
                                
                                <?php if ($service['provider_gender'] !== 'not_specified'): ?>
                                    <div style="margin: 10px 0; opacity: 0.8; font-size: 0.9rem;">
                                        <span class="badge badge-pending">
                                            <?php 
                                            echo $service['provider_gender'] === 'male' ? '👨 Male Provider' : 
                                                 ($service['provider_gender'] === 'female' ? '👩 Female Provider' : '👤 Other');
                                            ?>
                                        </span>
                                    </div>
                                <?php endif; ?>

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
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; 2025 QuickServe - Your Local Service Marketplace</p>
            <p>Developed by Ajeet Kumar, Abhishek Patel, Kundan Patil</p>
        </div>
    </div>
</body>
</html>
