<?php
/**
 * QuickServe - Edit Service
 * Provider can edit existing service
 */

require_once 'config/auth.php';
require_once 'config/database.php';

$auth = new Auth();
$auth->requireRole('provider');

$user = $auth->getCurrentUser();
$db = new Database();
$conn = $db->getConnection();

$success_message = '';
$error_message = '';

// Get service ID
if (!isset($_GET['id'])) {
    header("Location: provider-dashboard.php");
    exit;
}

$service_id = intval($_GET['id']);

// Get service details
$sql = "SELECT * FROM services WHERE id = ? AND provider_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $service_id, $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$service = $result->fetch_assoc();

if (!$service) {
    header("Location: provider-dashboard.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $price = floatval($_POST['price']);
    $location = trim($_POST['location']);
    
    // Availability and working hours
    $availability_days = isset($_POST['availability']) ? $_POST['availability'] : [];
    $working_start = $_POST['working_start'] ?? '09:00';
    $working_end = $_POST['working_end'] ?? '18:00';
    
    $availability_json = json_encode($availability_days);
    $working_hours_json = json_encode(['start' => $working_start, 'end' => $working_end]);
    
    if (empty($title) || empty($category) || $price <= 0 || empty($location)) {
        $error_message = "Please fill all required fields!";
    } else {
        $sql = "UPDATE services SET title = ?, description = ?, category = ?, price = ?, location = ?, 
                availability = ?, working_hours = ? WHERE id = ? AND provider_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssdsssii", $title, $description, $category, $price, $location, 
                         $availability_json, $working_hours_json, $service_id, $user['id']);
        
        if ($stmt->execute()) {
            $success_message = "Service updated successfully! Redirecting...";
            header("refresh:2;url=provider-dashboard.php");
        } else {
            $error_message = "Error updating service: " . $conn->error;
        }
    }
}

// Parse JSON data
$availability = json_decode($service['availability'], true) ?? [];
$working_hours = json_decode($service['working_hours'], true) ?? ['start' => '09:00', 'end' => '18:00'];

$categories = ['Plumbing', 'Electrical', 'Cleaning', 'Carpentry', 'Painting', 'AC Repair', 'Appliance Repair', 'Pest Control', 'Moving & Packing', 'Gardening'];
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Service - QuickServe</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>\">
    <style>
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .form-input, .form-select {
            width: 100%;
            padding: 12px 20px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 16px;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #4CAF50;
        }
        
        textarea.form-input {
            min-height: 100px;
            resize: vertical;
        }
        
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
        }
        
        .time-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
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
                    <li><a href="provider-dashboard.php">💼 Dashboard</a></li>
                    <li><a href="logout.php" class="btn btn-secondary">🚪 Logout</a></li>
                </ul>
            </div>
        </nav>

        <!-- Edit Service Form -->
        <div class="glass" style="padding: 40px; max-width: 900px; margin: 30px auto;">
            <h1 style="margin-bottom: 10px;">✏️ Edit Service</h1>
            <p style="opacity: 0.8; margin-bottom: 30px;">Update your service details</p>

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

            <form method="POST">
                <div class="form-group">
                    <label for="title">Service Title *</label>
                    <input type="text" name="title" id="title" class="form-input" 
                           value="<?php echo htmlspecialchars($service['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="category">Category *</label>
                    <input type="text" name="category" id="category" class="form-input" 
                           list="categoryList" value="<?php echo htmlspecialchars($service['category']); ?>" required>
                    <datalist id="categoryList">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea name="description" id="description" class="form-input" required><?php echo htmlspecialchars($service['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="price">Price (₹) *</label>
                    <input type="number" name="price" id="price" class="form-input" 
                           min="1" step="0.01" value="<?php echo $service['price']; ?>" required>
                </div>

                <div class="form-group">
                    <label for="location">Location *</label>
                    <input type="text" name="location" id="location" class="form-input" 
                           value="<?php echo htmlspecialchars($service['location']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Available Days</label>
                    <div class="checkbox-group">
                        <?php foreach ($days as $day): ?>
                            <label class="checkbox-item">
                                <input type="checkbox" name="availability[]" value="<?php echo $day; ?>" 
                                       <?php echo in_array($day, $availability) ? 'checked' : ''; ?>>
                                <span><?php echo $day; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>Working Hours</label>
                    <div class="time-inputs">
                        <div>
                            <label style="font-size: 0.9rem; opacity: 0.8;">Start Time</label>
                            <input type="time" name="working_start" class="form-input" 
                                   value="<?php echo $working_hours['start']; ?>">
                        </div>
                        <div>
                            <label style="font-size: 0.9rem; opacity: 0.8;">End Time</label>
                            <input type="time" name="working_end" class="form-input" 
                                   value="<?php echo $working_hours['end']; ?>">
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">💾 Update Service</button>
                    <a href="provider-dashboard.php" class="btn btn-secondary" 
                       style="flex: 1; text-align: center; text-decoration: none;">❌ Cancel</a>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; 2025 QuickServe - Your Local Service Marketplace</p>
        </div>
    </div>
</body>
</html>

