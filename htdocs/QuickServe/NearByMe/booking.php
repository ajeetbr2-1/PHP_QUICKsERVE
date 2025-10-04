<?php
/**
 * QuickServe - Book Service
 * Customer booking with location and preferences
 */

require_once 'config/auth.php';
require_once 'config/database.php';

$auth = new Auth();
$auth->requireRole('customer');

$user = $auth->getCurrentUser();
$db = new Database();
$conn = $db->getConnection();

// Get service ID
if (!isset($_GET['service_id'])) {
    header("Location: index.php");
    exit;
}

$service_id = intval($_GET['service_id']);

// Get service details
$sql = "SELECT s.*, u.full_name as provider_name, u.phone as provider_phone, u.rating as provider_rating
        FROM services s
        JOIN users u ON s.provider_id = u.id
        WHERE s.id = ? AND s.is_active = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $service_id);
$stmt->execute();
$service = $stmt->get_result()->fetch_assoc();

if (!$service) {
    header("Location: index.php");
    exit;
}

$success_message = '';
$error_message = '';

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_date = $_POST['booking_date'];
    $booking_time = $_POST['booking_time'];
    $customer_address = trim($_POST['customer_address']);
    $customer_city = trim($_POST['customer_city']);
    $customer_pincode = trim($_POST['customer_pincode']);
    $customer_phone = trim($_POST['customer_phone']);
    $notes = trim($_POST['notes']);
    $urgency = $_POST['urgency'] ?? 'normal';
    $preferred_time = $_POST['preferred_time'] ?? 'anytime';
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $service_duration = $_POST['service_duration'] ?? 'standard';
    $requires_materials = $_POST['requires_materials'] ?? 'no';
    
    if (empty($booking_date) || empty($booking_time) || empty($customer_address) || empty($customer_city)) {
        $error_message = "Please fill all required fields!";
    } else {
        // Calculate total amount (can add extra charges for urgent bookings)
        $base_price = $service['price'];
        $urgency_charge = $urgency === 'urgent' ? ($base_price * 0.2) : 0; // 20% extra for urgent
        $total_amount = $base_price + $urgency_charge;
        
        // Insert booking
        $sql = "INSERT INTO bookings (customer_id, service_id, provider_id, booking_date, booking_time, 
                status, notes, total_amount) 
                VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiisssd", $user['id'], $service_id, $service['provider_id'], 
                         $booking_date, $booking_time, $notes, $total_amount);
        
        if ($stmt->execute()) {
            $booking_id = mysqli_insert_id($conn);
            
            // Store additional booking details (customer location & preferences)
            $booking_meta = json_encode([
                'address' => $customer_address,
                'city' => $customer_city,
                'pincode' => $customer_pincode,
                'phone' => $customer_phone,
                'urgency' => $urgency,
                'preferred_time' => $preferred_time,
                'urgency_charge' => $urgency_charge,
                'payment_method' => $payment_method,
                'service_duration' => $service_duration,
                'requires_materials' => $requires_materials
            ]);
            
            // Update booking with metadata (store in notes for now, could create separate table)
            $notes_with_meta = $notes . "\n\n[Location: " . $customer_address . ", " . $customer_city . " - " . $customer_pincode . "]";
            $notes_with_meta .= "\n[Phone: " . $customer_phone . "]";
            $notes_with_meta .= "\n[Urgency: " . ucfirst($urgency) . "]";
            $notes_with_meta .= "\n[Preferred Time: " . $preferred_time . "]";
            $notes_with_meta .= "\n[Payment Method: " . ucfirst($payment_method) . "]";
            $notes_with_meta .= "\n[Service Duration: " . ucfirst($service_duration) . "]";
            $notes_with_meta .= "\n[Materials Required: " . ucfirst($requires_materials) . "]";
            
            $sql = "UPDATE bookings SET notes = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $notes_with_meta, $booking_id);
            $stmt->execute();
            
            $success_message = "Booking confirmed! Redirecting...";
            header("refresh:2;url=customer-dashboard.php");
        } else {
            $error_message = "Error creating booking: " . $conn->error;
        }
    }
}

// Parse availability and working hours
$availability = json_decode($service['availability'], true) ?? [];
$working_hours = json_decode($service['working_hours'], true) ?? ['start' => '09:00', 'end' => '18:00'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Service - QuickServe</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>\">
    <style>
        .booking-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
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
            border: 2px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.25);
            color: #1f2937;
            font-size: 1rem;
            font-weight: 500;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #4CAF50;
            background: rgba(255, 255, 255, 0.35);
        }
        
        textarea.form-input {
            min-height: 100px;
            resize: vertical;
        }
        
        .urgency-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .urgency-option {
            padding: 15px;
            border-radius: 12px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.1);
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .urgency-option input[type="radio"] {
            display: none;
        }
        
        .urgency-option input[type="radio"]:checked + label {
            background: rgba(76, 175, 80, 0.3);
            border-color: #4CAF50;
        }
        
        .urgency-option:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: scale(1.02);
        }
        
        @media (max-width: 768px) {
            .booking-container {
                grid-template-columns: 1fr;
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
                    <li><a href="customer-dashboard.php">📊 My Bookings</a></li>
                    <li><a href="logout.php" class="btn btn-secondary">🚪 Logout</a></li>
                </ul>
            </div>
        </nav>

        <div class="booking-container">
            <!-- Service Details -->
            <div class="glass" style="padding: 30px; height: fit-content;">
                <h2 style="margin-bottom: 20px;">📋 Service Details</h2>
                
                <h3 style="margin-bottom: 10px;"><?php echo htmlspecialchars($service['title']); ?></h3>
                <span class="badge badge-confirmed"><?php echo htmlspecialchars($service['category']); ?></span>
                
                <p style="margin: 20px 0; opacity: 0.9;"><?php echo htmlspecialchars($service['description']); ?></p>
                
                <div style="margin: 15px 0;">
                    <strong>Provider:</strong> <?php echo htmlspecialchars($service['provider_name']); ?>
                    <span style="margin-left: 10px;">⭐ <?php echo number_format($service['provider_rating'], 1); ?></span>
                </div>
                
                <div style="margin: 15px 0;">
                    <strong>Location:</strong> 📍 <?php echo htmlspecialchars($service['location']); ?>
                </div>
                
                <div style="margin: 15px 0;">
                    <strong>Price:</strong> <span style="font-size: 1.5rem; color: #4CAF50;">₹<?php echo number_format($service['price'], 2); ?></span>
                </div>
                
                <div style="margin: 15px 0;">
                    <strong>Available Days:</strong><br>
                    <?php foreach ($availability as $day): ?>
                        <span class="badge badge-pending" style="margin: 5px 5px 5px 0;"><?php echo $day; ?></span>
                    <?php endforeach; ?>
                </div>
                
                <div style="margin: 15px 0;">
                    <strong>Working Hours:</strong> 🕐 <?php echo $working_hours['start']; ?> - <?php echo $working_hours['end']; ?>
                </div>
            </div>

            <!-- Booking Form -->
            <div class="glass" style="padding: 30px;">
                <h2 style="margin-bottom: 20px;">📝 Book This Service</h2>

                <?php if ($success_message): ?>
                    <div style="padding: 15px; margin-bottom: 20px; background: rgba(76, 175, 80, 0.2); border: 1px solid rgba(76, 175, 80, 0.5); border-radius: 10px;">
                        ✅ <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div style="padding: 15px; margin-bottom: 20px; background: rgba(244, 67, 54, 0.2); border: 1px solid rgba(244, 67, 54, 0.5); border-radius: 10px;">
                        ❌ <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <!-- Date & Time -->
                    <div class="form-group">
                        <label for="booking_date">Preferred Date *</label>
                        <input type="date" name="booking_date" id="booking_date" class="form-input" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="booking_time">Preferred Time *</label>
                        <input type="time" name="booking_time" id="booking_time" class="form-input" required>
                    </div>

                    <!-- Urgency -->
                    <div class="form-group">
                        <label>Service Urgency</label>
                        <div class="urgency-options">
                            <div class="urgency-option">
                                <input type="radio" name="urgency" id="normal" value="normal" checked>
                                <label for="normal" style="cursor: pointer; display: block;">
                                    <div style="font-size: 1.5rem;">📅</div>
                                    <strong>Normal</strong>
                                    <p style="font-size: 0.85rem; opacity: 0.8; margin-top: 5px;">Standard booking</p>
                                </label>
                            </div>
                            <div class="urgency-option">
                                <input type="radio" name="urgency" id="urgent" value="urgent">
                                <label for="urgent" style="cursor: pointer; display: block;">
                                    <div style="font-size: 1.5rem;">🚨</div>
                                    <strong>Urgent</strong>
                                    <p style="font-size: 0.85rem; opacity: 0.8; margin-top: 5px;">+20% charge</p>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Preferred Time Slot -->
                    <div class="form-group">
                        <label for="preferred_time">Preferred Time Slot</label>
                        <select name="preferred_time" id="preferred_time" class="form-input">
                            <option value="anytime">Anytime</option>
                            <option value="morning">Morning (6 AM - 12 PM)</option>
                            <option value="afternoon">Afternoon (12 PM - 5 PM)</option>
                            <option value="evening">Evening (5 PM - 9 PM)</option>
                        </select>
                    </div>

                    <!-- Location Details -->
                    <div class="form-group">
                        <label for="customer_address">Complete Address *</label>
                        <textarea name="customer_address" id="customer_address" class="form-input" 
                                  placeholder="House/Flat No., Street, Area..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="customer_city">City *</label>
                        <input type="text" name="customer_city" id="customer_city" class="form-input" 
                               placeholder="Enter your city" required>
                    </div>

                    <div class="form-group">
                        <label for="customer_pincode">Pincode</label>
                        <input type="text" name="customer_pincode" id="customer_pincode" class="form-input" 
                               placeholder="Enter pincode" pattern="[0-9]{6}">
                    </div>

                    <!-- Contact -->
                    <div class="form-group">
                        <label for="customer_phone">Contact Phone *</label>
                        <input type="tel" name="customer_phone" id="customer_phone" class="form-input" 
                               placeholder="10-digit mobile number" 
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                               pattern="[0-9]{10}" required>
                    </div>

                    <!-- Service Duration Preference -->
                    <div class="form-group">
                        <label for="service_duration">Service Duration Preference</label>
                        <select name="service_duration" id="service_duration" class="form-input">
                            <option value="standard">Standard Duration</option>
                            <option value="quick">Quick Service (30 min - 1 hour)</option>
                            <option value="detailed">Detailed Service (2+ hours)</option>
                            <option value="full_day">Full Day Service</option>
                        </select>
                    </div>

                    <!-- Materials Required -->
                    <div class="form-group">
                        <label for="requires_materials">Do you need provider to bring materials?</label>
                        <select name="requires_materials" id="requires_materials" class="form-input">
                            <option value="no">No, I have all materials</option>
                            <option value="yes">Yes, please bring materials</option>
                            <option value="discuss">Discuss with provider</option>
                        </select>
                    </div>

                    <!-- Payment Method Preference -->
                    <div class="form-group">
                        <label for="payment_method">Preferred Payment Method</label>
                        <select name="payment_method" id="payment_method" class="form-input">
                            <option value="cash">💵 Cash on Completion</option>
                            <option value="upi">📱 UPI/Digital Payment</option>
                            <option value="card">💳 Card Payment</option>
                            <option value="bank_transfer">🏦 Bank Transfer</option>
                            <option value="discuss">💬 Discuss with Provider</option>
                        </select>
                    </div>

                    <!-- Additional Notes -->
                    <div class="form-group">
                        <label for="notes">Additional Requirements / Notes</label>
                        <textarea name="notes" id="notes" class="form-input" 
                                  placeholder="Any specific requirements or instructions..."></textarea>
                    </div>

                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            📅 Confirm Booking
                        </button>
                        <a href="index.php" class="btn btn-secondary" style="flex: 1; text-align: center; text-decoration: none;">
                            ❌ Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer" style="margin-top: 50px;">
            <p>&copy; 2025 QuickServe - Your Local Service Marketplace</p>
        </div>
    </div>

    <script>
        // Set minimum time based on urgency
        document.querySelectorAll('input[name="urgency"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const dateInput = document.getElementById('booking_date');
                if (this.value === 'urgent') {
                    // Allow booking from today for urgent
                    dateInput.min = new Date().toISOString().split('T')[0];
                } else {
                    // Normal booking - minimum tomorrow
                    const tomorrow = new Date();
                    tomorrow.setDate(tomorrow.getDate() + 1);
                    dateInput.min = tomorrow.toISOString().split('T')[0];
                }
            });
        });
        
        // Auto-fill city if available in service location
        const serviceLocation = '<?php echo $service['location']; ?>';
        const cityInput = document.getElementById('customer_city');
        if (!cityInput.value && serviceLocation) {
            const parts = serviceLocation.split(',');
            if (parts.length > 0) {
                cityInput.value = parts[0].trim();
            }
        }
    </script>
</body>
</html>

