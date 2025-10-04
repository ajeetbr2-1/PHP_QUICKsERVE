<?php
/**
 * QuickServe - Admin Services Management
 * Full CRUD for managing all services
 */

require_once 'config/auth.php';
require_once 'config/database.php';

$auth = new Auth();
$auth->requireRole('admin');

$user = $auth->getCurrentUser();
$db = new Database();
$conn = $db->getConnection();

$success_message = '';
$error_message = '';

// Handle service status toggle
if (isset($_GET['toggle_status'])) {
    $service_id = intval($_GET['toggle_status']);
    $sql = "UPDATE services SET is_active = NOT is_active WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $service_id);
    if ($stmt->execute()) {
        $success_message = "Service status updated!";
    }
}

// Handle service deletion
if (isset($_GET['delete'])) {
    $service_id = intval($_GET['delete']);
    $sql = "DELETE FROM services WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $service_id);
    if ($stmt->execute()) {
        $success_message = "Service deleted successfully!";
    } else {
        $error_message = "Error deleting service";
    }
}

// Get all services with provider info
$sql = "SELECT s.*, u.full_name as provider_name, u.email as provider_email, u.phone as provider_phone
        FROM services s
        JOIN users u ON s.provider_id = u.id
        ORDER BY s.created_at DESC";
$result = $conn->query($sql);
$services = [];
while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}

// Get statistics
$total_services = count($services);
$active_services = count(array_filter($services, function($s) { return $s['is_active']; }));
$inactive_services = $total_services - $active_services;

// Get all categories
$categories = array_unique(array_column($services, 'category'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services - Admin - QuickServe</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>\">
    <style>
        .filter-bar {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-input {
            flex: 1;
            min-width: 200px;
            padding: 10px 15px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .filter-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .service-row {
            transition: all 0.3s ease;
        }
        
        .service-row:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .badge-active {
            background: rgba(76, 175, 80, 0.2);
            color: #A5D6A7;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            border: 1px solid rgba(76, 175, 80, 0.5);
        }
        
        .badge-inactive {
            background: rgba(158, 158, 158, 0.2);
            color: #BDBDBD;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            border: 1px solid rgba(158, 158, 158, 0.5);
        }
    </style>
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

        <!-- Header -->
        <div class="dashboard-header glass" style="padding: 30px; margin-bottom: 30px;">
            <h1>🛠️ Manage Services</h1>
            <p>View and manage all services on the platform</p>
        </div>

        <?php if ($success_message): ?>
            <div class="glass" style="padding: 15px; margin-bottom: 20px; background: rgba(76, 175, 80, 0.2); border: 1px solid rgba(76, 175, 80, 0.5);">
                ✅ <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="glass" style="padding: 15px; margin-bottom: 20px; background: rgba(244, 67, 54, 0.2); border: 1px solid rgba(244, 67, 54, 0.5);">
                ❌ <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card glass">
                <div class="stat-value"><?php echo $total_services; ?></div>
                <div class="stat-label">📦 Total Services</div>
            </div>
            <div class="stat-card glass">
                <div class="stat-value"><?php echo $active_services; ?></div>
                <div class="stat-label">✅ Active</div>
            </div>
            <div class="stat-card glass">
                <div class="stat-value"><?php echo $inactive_services; ?></div>
                <div class="stat-label">⏸️ Inactive</div>
            </div>
            <div class="stat-card glass">
                <div class="stat-value"><?php echo count($categories); ?></div>
                <div class="stat-label">🏷️ Categories</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-bar glass">
            <input type="text" id="searchInput" class="filter-input" placeholder="🔍 Search services, providers...">
            <select id="categoryFilter" class="filter-input" style="flex: 0 0 200px;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                <?php endforeach; ?>
            </select>
            <select id="statusFilter" class="filter-input" style="flex: 0 0 150px;">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <!-- Services Table -->
        <div class="table-container glass">
            <h2 style="margin-bottom: 20px;">📋 All Services</h2>

            <?php if (empty($services)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🛠️</div>
                    <h3>No Services Yet</h3>
                    <p>Services will appear here once providers add them</p>
                </div>
            <?php else: ?>
                <table class="table" id="servicesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Service</th>
                            <th>Provider</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                            <tr class="service-row" 
                                data-service-name="<?php echo htmlspecialchars(strtolower($service['title'])); ?>"
                                data-provider-name="<?php echo htmlspecialchars(strtolower($service['provider_name'])); ?>"
                                data-category="<?php echo htmlspecialchars($service['category']); ?>"
                                data-status="<?php echo $service['is_active'] ? 'active' : 'inactive'; ?>">
                                <td>#<?php echo $service['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($service['title']); ?></strong>
                                    <br>
                                    <small style="opacity: 0.7;"><?php echo htmlspecialchars(substr($service['description'], 0, 50)) . '...'; ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($service['provider_name']); ?>
                                    <br>
                                    <small style="opacity: 0.7;">📧 <?php echo htmlspecialchars($service['provider_email']); ?></small>
                                </td>
                                <td><span class="badge badge-confirmed"><?php echo htmlspecialchars($service['category']); ?></span></td>
                                <td><strong>₹<?php echo number_format($service['price'], 2); ?></strong></td>
                                <td>📍 <?php echo htmlspecialchars($service['location']); ?></td>
                                <td>
                                    <?php if ($service['is_active']): ?>
                                        <span class="badge-active">✅ Active</span>
                                    <?php else: ?>
                                        <span class="badge-inactive">⏸️ Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d M Y', strtotime($service['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?toggle_status=<?php echo $service['id']; ?>" 
                                           class="btn btn-primary" 
                                           style="padding: 5px 10px; font-size: 0.85rem;"
                                           title="Toggle Status">
                                            <?php echo $service['is_active'] ? '⏸️' : '▶️'; ?>
                                        </a>
                                        <a href="?delete=<?php echo $service['id']; ?>" 
                                           onclick="return confirm('Delete this service? This action cannot be undone!')" 
                                           class="btn btn-secondary" 
                                           style="padding: 5px 10px; font-size: 0.85rem;"
                                           title="Delete">
                                            🗑️
                                        </a>
                                    </div>
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
        </div>
    </div>

    <script>
        // Search and filter functionality
        const searchInput = document.getElementById('searchInput');
        const categoryFilter = document.getElementById('categoryFilter');
        const statusFilter = document.getElementById('statusFilter');
        const rows = document.querySelectorAll('.service-row');

        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedCategory = categoryFilter.value;
            const selectedStatus = statusFilter.value;

            rows.forEach(row => {
                const serviceName = row.dataset.serviceName;
                const providerName = row.dataset.providerName;
                const category = row.dataset.category;
                const status = row.dataset.status;

                const matchesSearch = serviceName.includes(searchTerm) || providerName.includes(searchTerm);
                const matchesCategory = !selectedCategory || category === selectedCategory;
                const matchesStatus = !selectedStatus || status === selectedStatus;

                if (matchesSearch && matchesCategory && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        searchInput.addEventListener('input', filterTable);
        categoryFilter.addEventListener('change', filterTable);
        statusFilter.addEventListener('change', filterTable);
    </script>
</body>
</html>

