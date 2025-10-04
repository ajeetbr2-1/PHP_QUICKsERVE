<?php
/**
 * QuickServe - Edit Profile
 * Profile editing with image upload
 */

require_once 'config/auth.php';
require_once 'config/database.php';

$auth = new Auth();
$auth->requireLogin();

$user = $auth->getCurrentUser();
$db = new Database();
$conn = $db->getConnection();

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $gender = $_POST['gender'] ?? 'not_specified';
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $profile_image = $_POST['profile_image_url'] ?? '';
    
    // Additional fields for providers
    if ($user['role'] === 'provider') {
        $bio = trim($_POST['bio'] ?? '');
        $experience_years = intval($_POST['experience_years'] ?? 0);
        $service_areas = $_POST['service_areas'] ?? '';
        $languages = $_POST['languages'] ?? '';
        
        // Parse comma-separated values
        $service_areas_array = array_map('trim', explode(',', $service_areas));
        $languages_array = array_map('trim', explode(',', $languages));
        
        // Update user table with gender and address
        $sql = "UPDATE users SET full_name = ?, phone = ?, gender = ?, address = ?, city = ?, pincode = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi", $full_name, $phone, $gender, $address, $city, $pincode, $user['id']);
        
        if ($stmt->execute()) {
            // Update or insert provider profile
            $service_areas_json = json_encode($service_areas_array);
            $languages_json = json_encode($languages_array);
            
            $sql = "INSERT INTO provider_profiles (provider_id, bio, profile_image, experience_years, service_areas, languages) 
                    VALUES (?, ?, ?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    bio = VALUES(bio), 
                    profile_image = VALUES(profile_image), 
                    experience_years = VALUES(experience_years), 
                    service_areas = VALUES(service_areas), 
                    languages = VALUES(languages)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ississ", $user['id'], $bio, $profile_image, $experience_years, $service_areas_json, $languages_json);
            
            if ($stmt->execute()) {
                $success_message = "Profile updated successfully!";
                $_SESSION['user']['full_name'] = $full_name;
                $_SESSION['user']['phone'] = $phone;
            } else {
                $error_message = "Error updating provider profile";
            }
        } else {
            $error_message = "Error updating user information";
        }
    } else {
        // Update regular user with gender and address
        $sql = "UPDATE users SET full_name = ?, phone = ?, gender = ?, address = ?, city = ?, pincode = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi", $full_name, $phone, $gender, $address, $city, $pincode, $user['id']);
        
        if ($stmt->execute()) {
            $success_message = "Profile updated successfully!";
            $_SESSION['user']['full_name'] = $full_name;
            $_SESSION['user']['phone'] = $phone;
        } else {
            $error_message = "Error updating profile";
        }
    }
}

// Get existing provider profile if exists
$provider_profile = null;
if ($user['role'] === 'provider') {
    $sql = "SELECT * FROM provider_profiles WHERE provider_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $provider_profile = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - QuickServe</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>\">
    <style>
        .upload-zone {
            border: 2px dashed rgba(255, 255, 255, 0.3);
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.05);
            margin-bottom: 20px;
        }
        
        .upload-zone:hover {
            border-color: rgba(76, 175, 80, 0.8);
            background: rgba(76, 175, 80, 0.1);
        }
        
        .upload-zone.dragover {
            border-color: #4CAF50;
            background: rgba(76, 175, 80, 0.2);
        }
        
        .upload-zone-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 15px;
            margin: 20px auto;
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .form-input {
            width: 100%;
            padding: 12px 20px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #4CAF50;
            background: rgba(255, 255, 255, 0.15);
        }
        
        textarea.form-input {
            min-height: 100px;
            resize: vertical;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            animation: slideDown 0.3s ease;
        }
        
        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.5);
            color: #A5D6A7;
        }
        
        .alert-error {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid rgba(244, 67, 54, 0.5);
            color: #EF9A9A;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .loading-spinner {
            display: none;
            margin: 20px auto;
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-top: 4px solid #4CAF50;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
                    <li>
                        <?php if ($user['role'] === 'admin'): ?>
                            <a href="admin-dashboard.php">📊 Dashboard</a>
                        <?php elseif ($user['role'] === 'provider'): ?>
                            <a href="provider-dashboard.php">💼 Dashboard</a>
                        <?php else: ?>
                            <a href="customer-dashboard.php">👤 Dashboard</a>
                        <?php endif; ?>
                    </li>
                    <li><a href="logout.php" class="btn btn-secondary">🚪 Logout</a></li>
                </ul>
            </div>
        </nav>

        <!-- Profile Edit Form -->
        <div class="glass" style="padding: 40px; max-width: 800px; margin: 30px auto;">
            <h1 style="margin-bottom: 10px;">✏️ Edit Profile</h1>
            <p style="opacity: 0.8; margin-bottom: 30px;">Update your profile information</p>

            <?php if ($success_message): ?>
                <div class="alert alert-success">✅ <?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">❌ <?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <form method="POST" id="profileForm">
                <!-- Profile Image Upload (for providers only) -->
                <?php if ($user['role'] === 'provider'): ?>
                    <div class="form-group">
                        <label>Profile Image</label>
                        <div class="upload-zone" id="uploadZone">
                            <div class="upload-zone-icon">📷</div>
                            <h3>Drag & Drop Image Here</h3>
                            <p>or click to browse (Max 5MB)</p>
                            <input type="file" id="fileInput" accept="image/*" style="display: none;">
                        </div>
                        <div class="loading-spinner" id="loadingSpinner"></div>
                        <img id="previewImage" class="preview-image" style="display: none;" />
                        <input type="hidden" name="profile_image_url" id="profileImageUrl" 
                               value="<?php echo htmlspecialchars($provider_profile['profile_image'] ?? ''); ?>">
                    </div>
                <?php endif; ?>

                <!-- Basic Information -->
                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" name="full_name" id="full_name" class="form-input" 
                           value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" name="phone" id="phone" class="form-input" 
                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="gender">Gender <?php if ($user['role'] === 'provider'): ?>(Visible to customers)<?php endif; ?></label>
                    <select name="gender" id="gender" class="form-input" style="background: white; color: #111; font-weight: 600;">
                        <option value="not_specified" <?php echo ($user['gender'] ?? 'not_specified') === 'not_specified' ? 'selected' : ''; ?>>Prefer not to say</option>
                        <option value="male" <?php echo ($user['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo ($user['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                        <option value="other" <?php echo ($user['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Email (cannot be changed)</label>
                    <input type="email" class="form-input" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                </div>

                <!-- Address Fields -->
                <h3 style="margin: 30px 0 20px 0; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.2);">📍 Address Information</h3>
                
                <div class="form-group">
                    <label for="address">Complete Address</label>
                    <textarea name="address" id="address" class="form-input" 
                              placeholder="House/Flat No., Street, Area, Landmark..."><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" name="city" id="city" class="form-input" 
                               placeholder="Enter your city"
                               value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="pincode">Pincode</label>
                        <input type="text" name="pincode" id="pincode" class="form-input" 
                               placeholder="6-digit pincode" 
                               pattern="[0-9]{6}"
                               value="<?php echo htmlspecialchars($user['pincode'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Provider-specific fields -->
                <?php if ($user['role'] === 'provider'): ?>
                    <div class="form-group">
                        <label for="bio">Bio / About</label>
                        <textarea name="bio" id="bio" class="form-input" placeholder="Tell customers about yourself..."><?php echo htmlspecialchars($provider_profile['bio'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="experience_years">Years of Experience</label>
                        <input type="number" name="experience_years" id="experience_years" class="form-input" 
                               value="<?php echo htmlspecialchars($provider_profile['experience_years'] ?? 0); ?>" min="0">
                    </div>

                    <div class="form-group">
                        <label for="service_areas">Service Areas (comma-separated)</label>
                        <input type="text" name="service_areas" id="service_areas" class="form-input" 
                               placeholder="e.g. Mumbai, Navi Mumbai, Thane"
                               value="<?php echo htmlspecialchars(implode(', ', json_decode($provider_profile['service_areas'] ?? '[]', true))); ?>">
                    </div>

                    <div class="form-group">
                        <label for="languages">Languages (comma-separated)</label>
                        <input type="text" name="languages" id="languages" class="form-input" 
                               placeholder="e.g. English, Hindi, Marathi"
                               value="<?php echo htmlspecialchars(implode(', ', json_decode($provider_profile['languages'] ?? '[]', true))); ?>">
                    </div>
                <?php endif; ?>

                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">💾 Save Changes</button>
                    <a href="<?php echo $user['role'] === 'admin' ? 'admin-dashboard.php' : ($user['role'] === 'provider' ? 'provider-dashboard.php' : 'customer-dashboard.php'); ?>" 
                       class="btn btn-secondary" style="flex: 1; text-align: center; text-decoration: none;">❌ Cancel</a>
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
        const profileImageUrl = document.getElementById('profileImageUrl');
        const loadingSpinner = document.getElementById('loadingSpinner');

        if (uploadZone) {
            // Click to upload
            uploadZone.addEventListener('click', () => fileInput.click());

            // Drag and drop handlers
            uploadZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadZone.classList.add('dragover');
            });

            uploadZone.addEventListener('dragleave', () => {
                uploadZone.classList.remove('dragover');
            });

            uploadZone.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadZone.classList.remove('dragover');
                
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

            // Show existing image if available
            if (profileImageUrl.value) {
                previewImage.src = profileImageUrl.value;
                previewImage.style.display = 'block';
            }
        }

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
            formData.append('type', 'profile');

            loadingSpinner.style.display = 'block';

            fetch('api/upload.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                loadingSpinner.style.display = 'none';
                
                if (data.success) {
                    profileImageUrl.value = data.url;
                    alert('Image uploaded successfully!');
                } else {
                    alert('Upload failed: ' + data.message);
                }
            })
            .catch(error => {
                loadingSpinner.style.display = 'none';
                alert('Upload error: ' + error.message);
            });
        }
    </script>
</body>
</html>

