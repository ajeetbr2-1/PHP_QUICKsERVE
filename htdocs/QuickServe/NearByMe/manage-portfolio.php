<?php
/**
 * QuickServe - Manage Portfolio
 * For providers to manage their portfolio items and certificates
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

// Handle portfolio item addition/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_portfolio'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $project_date = $_POST['project_date'];
    $image_url = $_POST['image_url'] ?? '';
    
    $sql = "INSERT INTO portfolio_items (provider_id, title, description, category, project_date, image_url) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssss", $user['id'], $title, $description, $category, $project_date, $image_url);
    
    if ($stmt->execute()) {
        $success_message = "Portfolio item added successfully!";
    } else {
        $error_message = "Error adding portfolio item";
    }
}

// Handle certificate addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_certificate'])) {
    $title = trim($_POST['cert_title']);
    $organization = trim($_POST['organization']);
    $issue_date = $_POST['issue_date'];
    $expiry_date = $_POST['expiry_date'] ?: NULL;
    $cert_url = $_POST['cert_url'] ?? '';
    
    $sql = "INSERT INTO certificates (provider_id, title, issuing_organization, issue_date, expiry_date, certificate_url) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssss", $user['id'], $title, $organization, $issue_date, $expiry_date, $cert_url);
    
    if ($stmt->execute()) {
        $success_message = "Certificate added successfully!";
    } else {
        $error_message = "Error adding certificate";
    }
}

// Handle deletions
if (isset($_GET['delete_portfolio'])) {
    $id = intval($_GET['delete_portfolio']);
    $sql = "DELETE FROM portfolio_items WHERE id = ? AND provider_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $user['id']);
    if ($stmt->execute()) {
        $success_message = "Portfolio item deleted!";
    }
}

if (isset($_GET['delete_certificate'])) {
    $id = intval($_GET['delete_certificate']);
    $sql = "DELETE FROM certificates WHERE id = ? AND provider_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $user['id']);
    if ($stmt->execute()) {
        $success_message = "Certificate deleted!";
    }
}

// Get portfolio items
$sql = "SELECT * FROM portfolio_items WHERE provider_id = ? ORDER BY project_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$portfolio_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get certificates
$sql = "SELECT * FROM certificates WHERE provider_id = ? ORDER BY issue_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$certificates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Portfolio - QuickServe</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>\">
    <style>
        .portfolio-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .portfolio-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .portfolio-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .portfolio-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 15px;
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
            padding: 40px;
            border-radius: 20px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-input {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
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
        
        .upload-zone {
            border: 2px dashed rgba(255, 255, 255, 0.3);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .upload-zone:hover {
            border-color: #4CAF50;
            background: rgba(76, 175, 80, 0.1);
        }
        
        .preview-image {
            max-width: 200px;
            border-radius: 10px;
            margin: 10px auto;
            display: block;
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

        <!-- Header -->
        <div class="dashboard-header glass" style="padding: 30px; margin-bottom: 30px;">
            <h1>📂 Manage Portfolio</h1>
            <p>Showcase your work and certifications</p>
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

        <!-- Portfolio Items -->
        <div class="glass" style="padding: 30px; margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>🎨 Portfolio Items</h2>
                <button class="btn btn-primary" onclick="openPortfolioModal()">➕ Add Portfolio Item</button>
            </div>

            <?php if (empty($portfolio_items)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🎨</div>
                    <h3>No Portfolio Items Yet</h3>
                    <p>Add your first portfolio item to showcase your work!</p>
                </div>
            <?php else: ?>
                <div class="portfolio-grid">
                    <?php foreach ($portfolio_items as $item): ?>
                        <div class="portfolio-card">
                            <?php if ($item['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="portfolio-image" alt="Portfolio">
                            <?php endif; ?>
                            <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                            <span class="badge badge-confirmed"><?php echo htmlspecialchars($item['category']); ?></span>
                            <p style="margin: 10px 0; opacity: 0.8;"><?php echo htmlspecialchars($item['description']); ?></p>
                            <p style="opacity: 0.6; font-size: 0.9rem;">📅 <?php echo date('M Y', strtotime($item['project_date'])); ?></p>
                            <a href="?delete_portfolio=<?php echo $item['id']; ?>" 
                               onclick="return confirm('Delete this portfolio item?')" 
                               class="btn btn-secondary" style="width: 100%; margin-top: 10px;">🗑️ Delete</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Certificates -->
        <div class="glass" style="padding: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>🏆 Certificates</h2>
                <button class="btn btn-primary" onclick="openCertificateModal()">➕ Add Certificate</button>
            </div>

            <?php if (empty($certificates)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🏆</div>
                    <h3>No Certificates Yet</h3>
                    <p>Add your certifications to build trust with customers!</p>
                </div>
            <?php else: ?>
                <table class="table" style="margin-top: 20px;">
                    <thead>
                        <tr>
                            <th>Certificate</th>
                            <th>Issuing Organization</th>
                            <th>Issue Date</th>
                            <th>Expiry</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($certificates as $cert): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($cert['title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($cert['issuing_organization']); ?></td>
                                <td><?php echo date('M Y', strtotime($cert['issue_date'])); ?></td>
                                <td>
                                    <?php if ($cert['expiry_date']): ?>
                                        <?php echo date('M Y', strtotime($cert['expiry_date'])); ?>
                                    <?php else: ?>
                                        <span class="badge badge-completed">No Expiry</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?delete_certificate=<?php echo $cert['id']; ?>" 
                                       onclick="return confirm('Delete this certificate?')" 
                                       class="btn btn-secondary" style="padding: 5px 15px; font-size: 0.85rem;">🗑️ Delete</a>
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

    <!-- Portfolio Modal -->
    <div id="portfolioModal" class="modal">
        <div class="modal-content">
            <h2>➕ Add Portfolio Item</h2>
            <form method="POST" id="portfolioForm">
                <input type="hidden" name="add_portfolio" value="1">
                
                <div class="form-group">
                    <label>Upload Image</label>
                    <div class="upload-zone" id="portfolioUploadZone" onclick="document.getElementById('portfolioFileInput').click()">
                        <div style="font-size: 40px;">📷</div>
                        <p>Click or drag image here</p>
                        <input type="file" id="portfolioFileInput" accept="image/*" style="display: none;">
                    </div>
                    <img id="portfolioPreview" class="preview-image" style="display: none;">
                    <input type="hidden" name="image_url" id="portfolioImageUrl">
                </div>

                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" class="form-input" required>
                </div>

                <div class="form-group">
                    <label>Category *</label>
                    <input type="text" name="category" class="form-input" placeholder="e.g. Plumbing, Electrical" required>
                </div>

                <div class="form-group">
                    <label>Description *</label>
                    <textarea name="description" class="form-input" required></textarea>
                </div>

                <div class="form-group">
                    <label>Project Date *</label>
                    <input type="date" name="project_date" class="form-input" required>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">💾 Add</button>
                    <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="closePortfolioModal()">❌ Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Certificate Modal -->
    <div id="certificateModal" class="modal">
        <div class="modal-content">
            <h2>➕ Add Certificate</h2>
            <form method="POST">
                <input type="hidden" name="add_certificate" value="1">

                <div class="form-group">
                    <label>Upload Certificate (Optional)</label>
                    <div class="upload-zone" id="certUploadZone" onclick="document.getElementById('certFileInput').click()">
                        <div style="font-size: 40px;">📄</div>
                        <p>Click or drag certificate file here</p>
                        <input type="file" id="certFileInput" accept="image/*,application/pdf" style="display: none;">
                    </div>
                    <input type="hidden" name="cert_url" id="certUrl">
                </div>

                <div class="form-group">
                    <label>Certificate Title *</label>
                    <input type="text" name="cert_title" class="form-input" required>
                </div>

                <div class="form-group">
                    <label>Issuing Organization *</label>
                    <input type="text" name="organization" class="form-input" required>
                </div>

                <div class="form-group">
                    <label>Issue Date *</label>
                    <input type="date" name="issue_date" class="form-input" required>
                </div>

                <div class="form-group">
                    <label>Expiry Date (Optional)</label>
                    <input type="date" name="expiry_date" class="form-input">
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">💾 Add</button>
                    <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="closeCertificateModal()">❌ Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openPortfolioModal() {
            document.getElementById('portfolioModal').classList.add('active');
        }

        function closePortfolioModal() {
            document.getElementById('portfolioModal').classList.remove('active');
        }

        function openCertificateModal() {
            document.getElementById('certificateModal').classList.add('active');
        }

        function closeCertificateModal() {
            document.getElementById('certificateModal').classList.remove('active');
        }

        // Portfolio file upload
        document.getElementById('portfolioFileInput').addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                uploadPortfolioFile(e.target.files[0]);
            }
        });

        // Certificate file upload
        document.getElementById('certFileInput').addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                uploadCertificateFile(e.target.files[0]);
            }
        });

        function uploadPortfolioFile(file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', 'portfolio');

            fetch('api/upload.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('portfolioImageUrl').value = data.url;
                    document.getElementById('portfolioPreview').src = data.url;
                    document.getElementById('portfolioPreview').style.display = 'block';
                    alert('Image uploaded successfully!');
                } else {
                    alert('Upload failed: ' + data.message);
                }
            })
            .catch(error => alert('Upload error: ' + error.message));
        }

        function uploadCertificateFile(file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', 'certificate');

            fetch('api/upload.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('certUrl').value = data.url;
                    alert('Certificate uploaded successfully!');
                } else {
                    alert('Upload failed: ' + data.message);
                }
            })
            .catch(error => alert('Upload error: ' + error.message));
        }
    </script>
</body>
</html>

