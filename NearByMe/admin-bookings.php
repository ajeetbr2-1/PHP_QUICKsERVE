<?php
/**
 * Near By Me - Admin Bookings Management
 * Full booking management and status updates
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

// Handle booking status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $booking_id = intval($_POST['booking_id']);
    $new_status = $_POST['status'];
    
    $sql = "UPDATE bookings SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_status, $booking_id);
    
    if ($stmt->execute()) {
        $success_message = "Booking status updated successfully!";
    } else {
        $error_message = "Error updating booking status";
    }
}

// Handle booking deletion
if (isset($_GET['delete'])) {
    $booking_id = intval($_GET['delete']);
    $sql = "DELETE FROM bookings WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $booking_id);
    if ($stmt->execute()) {
        $success_message = "Booking deleted successfully!";
    } else {
        $error_message = "Error deleting booking";
    }
}

// Get all bookings with related info
$sql = "SELECT b.*, 
        s.title as service_title, 
        s.category as service_category,
        u.full_name as customer_name, 
        u.email as customer_email, 
        u.phone as customer_phone,
        p.full_name as provider_name,
        p.email as provider_email,
        p.phone as provider_phone
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        JOIN users u ON b.customer_id = u.id
        JOIN users p ON b.provider_id = p.id
        ORDER BY b.created_at DESC";
$result = $conn->query($sql);
$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

// Get statistics
$total_bookings = count($bookings);
$pending = count(array_filter($bookings, function($b) { return $b['status'] == 'pending'; }));
$confirmed = count(array_filter($bookings, function($b) { return $b['status'] == 'confirmed'; }));
$in_progress = count(array_filter($bookings, function($b) { return $b['status'] == 'in_progress'; }));
$completed = count(array_filter($bookings, function($b) { return $b['status'] == 'completed'; }));
$cancelled = count(array_filter($bookings, function($b) { return $b['status'] == 'cancelled'; }));
$total_revenue = array_sum(array_column($bookings, 'total_amount'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - Admin - Near By Me</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
        
        .booking-row {
            transition: all 0.3s ease;
        }
        
        .booking-row:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .status-update-form {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        
        .status-select {
            padding: 5px 10px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 0.85rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 20px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
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
                    <span>Near By Me</span>
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
            <h1>📋 Manage Bookings</h1>
            <p>View and manage all bookings on the platform</p>
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
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">
            <div class="stat-card glass">
                <div class="stat-value"><?php echo $total_bookings; ?></div>
                <div class="stat-label">📦 Total</div>
            </div>
            <div class="stat-card glass">
                <div class="stat-value"><?php echo $pending; ?></div>
                <div class="stat-label">⏳ Pending</div>
            </div>
            <div class="stat-card glass">
                <div class="stat-value"><?php echo $confirmed; ?></div>
                <div class="stat-label">✅ Confirmed</div>
            </div>
            <div class="stat-card glass">
                <div class="stat-value"><?php echo $in_progress; ?></div>
                <div class="stat-label">🔄 In Progress</div>
            </div>
            <div class="stat-card glass">
                <div class="stat-value"><?php echo $completed; ?></div>
                <div class="stat-label">✔️ Completed</div>
            </div>
            <div class="stat-card glass">
                <div class="stat-value"><?php echo $cancelled; ?></div>
                <div class="stat-label">❌ Cancelled</div>
            </div>
            <div class="stat-card glass">
                <div class="stat-value">₹<?php echo number_format($total_revenue, 0); ?></div>
                <div class="stat-label">💰 Revenue</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-bar glass">
            <input type="text" id="searchInput" class="filter-input" placeholder="🔍 Search bookings, customers, providers...">
            <select id="statusFilter" class="filter-input" style="flex: 0 0 150px;">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <input type="date" id="dateFilter" class="filter-input" style="flex: 0 0 180px;">
        </div>

        <!-- Bookings Table -->
        <div class="table-container glass">
            <h2 style="margin-bottom: 20px;">📋 All Bookings</h2>

            <?php if (empty($bookings)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <h3>No Bookings Yet</h3>
                    <p>Bookings will appear here once customers book services</p>
                </div>
            <?php else: ?>
                <table class="table" id="bookingsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Service</th>
                            <th>Customer</th>
                            <th>Provider</th>
                            <th>Date & Time</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Booked On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr class="booking-row" 
                                data-customer="<?php echo htmlspecialchars(strtolower($booking['customer_name'])); ?>"
                                data-provider="<?php echo htmlspecialchars(strtolower($booking['provider_name'])); ?>"
                                data-service="<?php echo htmlspecialchars(strtolower($booking['service_title'])); ?>"
                                data-status="<?php echo $booking['status']; ?>"
                                data-date="<?php echo $booking['booking_date']; ?>">
                                <td>#<?php echo $booking['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($booking['service_title']); ?></strong>
                                    <br>
                                    <span class="badge badge-confirmed"><?php echo htmlspecialchars($booking['service_category']); ?></span>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($booking['customer_name']); ?>
                                    <br>
                                    <small style="opacity: 0.7;">📞 <?php echo htmlspecialchars($booking['customer_phone']); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($booking['provider_name']); ?>
                                    <br>
                                    <small style="opacity: 0.7;">📞 <?php echo htmlspecialchars($booking['provider_phone']); ?></small>
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
                                <td><?php echo date('d M Y', strtotime($booking['created_at'])); ?></td>
                                <td>
                                    <button onclick="openStatusModal(<?php echo $booking['id']; ?>, '<?php echo $booking['status']; ?>')" 
                                            class="btn btn-primary" 
                                            style="padding: 5px 10px; font-size: 0.85rem; margin-bottom: 5px;">
                                        ✏️ Update
                                    </button>
                                    <br>
                                    <a href="?delete=<?php echo $booking['id']; ?>" 
                                       onclick="return confirm('Delete this booking? This action cannot be undone!')" 
                                       class="btn btn-secondary" 
                                       style="padding: 5px 10px; font-size: 0.85rem;">
                                        🗑️ Delete
                                    </a>
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

    <!-- Status Update Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <h2>Update Booking Status</h2>
            <form method="POST" style="margin-top: 20px;">
                <input type="hidden" name="update_status" value="1">
                <input type="hidden" name="booking_id" id="modalBookingId">
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 10px; font-weight: 600;">Select New Status:</label>
                    <select name="status" id="modalStatus" class="filter-input" required>
                        <option value="pending">⏳ Pending</option>
                        <option value="confirmed">✅ Confirmed</option>
                        <option value="in_progress">🔄 In Progress</option>
                        <option value="completed">✔️ Completed</option>
                        <option value="cancelled">❌ Cancelled</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">💾 Update Status</button>
                    <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="closeStatusModal()">❌ Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function openStatusModal(bookingId, currentStatus) {
            document.getElementById('modalBookingId').value = bookingId;
            document.getElementById('modalStatus').value = currentStatus;
            document.getElementById('statusModal').classList.add('active');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.remove('active');
        }

        // Search and filter functionality
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const dateFilter = document.getElementById('dateFilter');
        const rows = document.querySelectorAll('.booking-row');

        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedStatus = statusFilter.value;
            const selectedDate = dateFilter.value;

            rows.forEach(row => {
                const customer = row.dataset.customer;
                const provider = row.dataset.provider;
                const service = row.dataset.service;
                const status = row.dataset.status;
                const date = row.dataset.date;

                const matchesSearch = customer.includes(searchTerm) || 
                                    provider.includes(searchTerm) || 
                                    service.includes(searchTerm);
                const matchesStatus = !selectedStatus || status === selectedStatus;
                const matchesDate = !selectedDate || date === selectedDate;

                if (matchesSearch && matchesStatus && matchesDate) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        searchInput.addEventListener('input', filterTable);
        statusFilter.addEventListener('change', filterTable);
        dateFilter.addEventListener('change', filterTable);
    </script>
</body>
</html>
