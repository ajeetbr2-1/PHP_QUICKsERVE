<?php
/**
 * QuickServe - Add Service
 * Provider can create new services
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $price = floatval($_POST['price']);
    $location = trim($_POST['location']);
    $service_image = $_POST['service_image_url'] ?? '';
    
    // Availability and working hours
    $availability_days = isset($_POST['availability']) ? $_POST['availability'] : [];
    $working_start = $_POST['working_start'] ?? '09:00';
    $working_end = $_POST['working_end'] ?? '18:00';
    
    $availability_json = json_encode($availability_days);
    $working_hours_json = json_encode(['start' => $working_start, 'end' => $working_end]);
    
    if (empty($title) || empty($category) || $price <= 0 || empty($location)) {
        $error_message = "Please fill all required fields!";
    } else {
        $sql = "INSERT INTO services (provider_id, title, description, category, price, location, availability, working_hours, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssdsss", $user['id'], $title, $description, $category, $price, $location, $availability_json, $working_hours_json);
        
        if ($stmt->execute()) {
            $success_message = "Service added successfully! Redirecting...";
            header("refresh:2;url=provider-dashboard.php");
        } else {
            $error_message = "Error adding service: " . $conn->error;
        }
    }
}

// Get common categories for suggestions
$categories = ['Plumbing', 'Electrical', 'Cleaning', 'Carpentry', 'Painting', 'AC Repair', 'Appliance Repair', 'Pest Control', 'Moving & Packing', 'Gardening'];
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Service - QuickServe</title>
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
            transition: all 0.3s ease;
        }
        
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #4CAF50;
            background: rgba(255, 255, 255, 0.15);
        }
        
        textarea.form-input {
            min-height: 100px;
            resize: vertical;
        }
        
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .checkbox-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .checkbox-item input[type="checkbox"] {
            cursor: pointer;
        }
        
        .upload-zone {
            border: 2px dashed rgba(255, 255, 255, 0.3);
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.05);
        }
        
        .upload-zone:hover {
            border-color: rgba(76, 175, 80, 0.8);
            background: rgba(76, 175, 80, 0.1);
        }
        
        .preview-image {
            max-width: 300px;
            max-height: 200px;
            border-radius: 15px;
            margin: 20px auto;
            display: block;
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
                    <li><a href="manage-portfolio.php">📂 Portfolio</a></li>
                    <li><a href="edit-profile.php">✏️ Edit Profile</a></li>
                    <li><a href="logout.php" class="btn btn-secondary">🚪 Logout</a></li>
                </ul>
            </div>
        </nav>

        <!-- Add Service Form -->
        <div class="glass" style="padding: 40px; max-width: 900px; margin: 30px auto;">
            <h1 style="margin-bottom: 10px;">➕ Add New Service</h1>
            <p style="opacity: 0.8; margin-bottom: 30px;">Create a new service to offer to customers</p>

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

            <form method="POST" id="serviceForm">
                <!-- Service Image Upload -->
                <div class="form-group">
                    <label>Service Image (Optional)</label>
                    <div class="upload-zone" id="uploadZone" onclick="document.getElementById('fileInput').click()">
                        <div style="font-size: 48px;">📷</div>
                        <h3>Drag & Drop Image Here</h3>
                        <p>or click to browse (Max 5MB)</p>
                        <input type="file" id="fileInput" accept="image/*" style="display: none;">
                    </div>
                    <img id="previewImage" class="preview-image" style="display: none;">
                    <input type="hidden" name="service_image_url" id="serviceImageUrl">
                </div>

                <!-- Basic Information -->
                <div class="form-group">
                    <label for="title">Service Title *</label>
                    <input type="text" name="title" id="title" class="form-input" 
                           placeholder="e.g. Professional Plumbing Services" required>
                </div>

                <div class="form-group">
                    <label for="category">Category *</label>
                    <input type="text" name="category" id="category" class="form-input" 
                           list="categoryList" placeholder="Select or type category" required>
                    <datalist id="categoryList">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea name="description" id="description" class="form-input" 
                              placeholder="Describe your service in detail..." required></textarea>
                </div>

                <div class="form-group">
                    <label for="price">Price (₹) *</label>
                    <input type="number" name="price" id="price" class="form-input" 
                           min="1" step="0.01" placeholder="500.00" required>
                </div>

                <div class="form-group">
                    <label for="location">Location *</label>
                    <input type="text" name="location" id="location" class="form-input" 
                           placeholder="e.g. Mumbai, Maharashtra" required>
                </div>

                <!-- Availability -->
                <div class="form-group">
                    <label>Available Days</label>
                    <div class="checkbox-group">
                        <?php foreach ($days as $day): ?>
                            <label class="checkbox-item">
                                <input type="checkbox" name="availability[]" value="<?php echo $day; ?>" 
                                       <?php echo in_array($day, ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']) ? 'checked' : ''; ?>>
                                <span><?php echo $day; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Working Hours -->
                <div class="form-group">
                    <label>Working Hours</label>
                    <div class="time-inputs">
                        <div>
                            <label style="font-size: 0.9rem; opacity: 0.8;">Start Time</label>
                            <input type="time" name="working_start" class="form-input" value="09:00">
                        </div>
                        <div>
                            <label style="font-size: 0.9rem; opacity: 0.8;">End Time</label>
                            <input type="time" name="working_end" class="form-input" value="18:00">
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">💾 Create Service</button>
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

    <script>
        // File upload handling
        const uploadZone = document.getElementById('uploadZone');
        const fileInput = document.getElementById('fileInput');
        const previewImage = document.getElementById('previewImage');
        const serviceImageUrl = document.getElementById('serviceImageUrl');

        // Drag and drop handlers
        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.style.borderColor = '#4CAF50';
            uploadZone.style.background = 'rgba(76, 175, 80, 0.2)';
        });

        uploadZone.addEventListener('dragleave', () => {
            uploadZone.style.borderColor = 'rgba(255, 255, 255, 0.3)';
            uploadZone.style.background = 'rgba(255, 255, 255, 0.05)';
        });

        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.style.borderColor = 'rgba(255, 255, 255, 0.3)';
            uploadZone.style.background = 'rgba(255, 255, 255, 0.05)';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFile(files[0]);
            }
        });

        // File input change
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFile(e.target.files[0]);
            }
        });

        function handleFile(file) {
            // Validate file type
            if (!file.type.startsWith('image/')) {
                alert('Please upload an image file');
                return;
            }

            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB');
                return;
            }

            // Show preview
            const reader = new FileReader();
            reader.onload = (e) => {
                previewImage.src = e.target.result;
                previewImage.style.display = 'block';
            };
            reader.readAsDataURL(file);

            // Upload file
            uploadFile(file);
        }

        function uploadFile(file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', 'service');

            // Show loading
            uploadZone.innerHTML = '<div style="font-size: 48px;">⏳</div><p>Uploading...</p>';

            fetch('api/upload.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    serviceImageUrl.value = data.url;
                    uploadZone.innerHTML = '<div style="font-size: 48px;">✅</div><p>Image uploaded successfully!</p>';
                } else {
                    alert('Upload failed: ' + data.message);
                    uploadZone.innerHTML = '<div style="font-size: 48px;">📷</div><h3>Drag & Drop Image Here</h3><p>or click to browse (Max 5MB)</p>';
                }
            })
            .catch(error => {
                alert('Upload error: ' + error.message);
                uploadZone.innerHTML = '<div style="font-size: 48px;">📷</div><h3>Drag & Drop Image Here</h3><p>or click to browse (Max 5MB)</p>';
            });
        }
    </script>
</body>
</html>

