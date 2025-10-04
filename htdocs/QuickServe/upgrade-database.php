<?php
// Enhanced QuickServe Database Upgrade Script
require_once 'api/config.php';

try {
    // Get database connection
    $conn = getDBConnection();
    
    // Read and execute the enhanced schema
    $schemaSQL = file_get_contents(__DIR__ . '/database/enhanced-schema.sql');
    
    // Split SQL commands by semicolon
    $queries = array_filter(array_map('trim', explode(';', $schemaSQL)));
    
    $successCount = 0;
    $errors = [];
    
    echo "🚀 Starting QuickServe Database Enhancement...<br><br>";
    
    foreach ($queries as $query) {
        if (empty($query)) continue;
        
        try {
            $conn->query($query);
            $successCount++;
            
            // Extract table/operation name for display
            if (preg_match('/CREATE TABLE.*?(\w+)\s*\(/i', $query, $matches)) {
                echo "✅ Created table: " . $matches[1] . "<br>";
            } elseif (preg_match('/ALTER TABLE\s+(\w+)/i', $query, $matches)) {
                echo "✅ Enhanced table: " . $matches[1] . "<br>";
            } elseif (preg_match('/CREATE INDEX\s+(\w+)/i', $query, $matches)) {
                echo "✅ Created index: " . $matches[1] . "<br>";
            }
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate column') === false) {
                $errors[] = $e->getMessage();
                echo "❌ Error: " . $e->getMessage() . "<br>";
            } else {
                echo "ℹ️ Skipped existing: " . (preg_match('/CREATE TABLE.*?(\w+)\s*\(/i', $query, $matches) ? $matches[1] : 'item') . "<br>";
            }
        }
    }
    
    // Insert sample enhanced data
    echo "<br>📊 Adding Enhanced Sample Data...<br><br>";
    
    // Update existing users with new fields
    $conn->query("UPDATE users SET 
        is_verified = TRUE, 
        rating = 4.5, 
        total_reviews = 15, 
        bio = 'Experienced professional with excellent customer service',
        social_links = JSON_OBJECT('facebook', 'https://facebook.com/user', 'instagram', 'https://instagram.com/user')
        WHERE role = 'provider' AND id <= 15");
    echo "✅ Enhanced existing provider profiles<br>";
    
    // Add provider profiles
    $providerProfiles = [
        [11, 'John\'s Plumbing Services', 8, 500.00, 25, '["English", "Hindi"]', '["Residential Plumbing", "Emergency Repairs"]'],
        [12, 'Maria\'s Cleaning Co.', 5, 300.00, 15, '["English", "Spanish"]', '["Deep Cleaning", "Regular Maintenance"]'],
        [13, 'David\'s Electrical Works', 10, 800.00, 30, '["English", "Hindi"]', '["Home Wiring", "Commercial Electrical")']
    ];
    
    foreach ($providerProfiles as $profile) {
        $stmt = $conn->prepare("INSERT IGNORE INTO provider_profiles 
            (user_id, business_name, experience_years, hourly_rate, service_radius, languages_spoken, specializations, emergency_services, free_consultation) 
            VALUES (?, ?, ?, ?, ?, ?, ?, TRUE, TRUE)");
        $stmt->bind_param('isidiss', $profile[0], $profile[1], $profile[2], $profile[3], $profile[4], $profile[5], $profile[6]);
        $stmt->execute();
        echo "✅ Added provider profile for user ID: {$profile[0]}<br>";
    }
    
    // Add customer profiles
    $customerProfiles = [
        [14, '123 Main Street, Apartment 4B', 'Mumbai', 'Maharashtra', '400001', 'phone', 'apartment'],
        [15, '456 Park Avenue, House 12', 'Delhi', 'Delhi', '110001', 'email', 'house']
    ];
    
    foreach ($customerProfiles as $profile) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO customer_profiles 
            (user_id, address, city, state, pincode, preferred_contact_method, property_type) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute($profile);
        echo "✅ Added customer profile for user ID: {$profile[0]}<br>";
    }
    
    // Add sample portfolio items
    $portfolioItems = [
        [11, 'Modern Bathroom Renovation', 'Complete bathroom makeover with modern fixtures', 'https://via.placeholder.com/400x300', '2024-01-15', 'Mumbai', 25000],
        [12, 'Office Deep Cleaning Project', 'Complete office cleaning and sanitization', 'https://via.placeholder.com/400x300', '2024-02-20', 'Delhi', 5000],
        [13, 'Smart Home Electrical Setup', 'Complete smart home wiring and automation', 'https://via.placeholder.com/400x300', '2024-03-10', 'Pune', 50000]
    ];
    
    foreach ($portfolioItems as $item) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO portfolio_items 
            (user_id, title, description, image_url, project_date, project_location, project_cost, is_featured) 
            VALUES (?, ?, ?, ?, ?, ?, ?, TRUE)");
        $stmt->execute($item);
        echo "✅ Added portfolio item: {$item[1]}<br>";
    }
    
    // Add sample certificates
    $certificates = [
        [11, 'Certified Plumbing Professional', 'Indian Plumbing Association', '2020-06-15', NULL, 'certification'],
        [12, 'Professional Cleaning Certificate', 'Cleaning Services Institute', '2021-03-20', '2026-03-20', 'certification'],
        [13, 'Licensed Electrician', 'Electrical Board of India', '2019-09-10', '2024-09-10', 'license']
    ];
    
    foreach ($certificates as $cert) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO certificates 
            (user_id, title, issuing_organization, issue_date, expiry_date, certificate_type, verification_status) 
            VALUES (?, ?, ?, ?, ?, ?, 'verified')");
        $stmt->execute($cert);
        echo "✅ Added certificate: {$cert[1]}<br>";
    }
    
    // Add business hours for providers
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    foreach ([11, 12, 13] as $userId) {
        foreach ($days as $day) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO business_hours 
                (user_id, day_of_week, is_open, open_time, close_time) 
                VALUES (?, ?, TRUE, '09:00:00', '18:00:00')");
            $stmt->execute([$userId, $day]);
        }
        // Sunday closed
        $stmt = $pdo->prepare("INSERT IGNORE INTO business_hours 
            (user_id, day_of_week, is_open) 
            VALUES (?, 'sunday', FALSE)");
        $stmt->execute([$userId]);
        echo "✅ Added business hours for provider ID: {$userId}<br>";
    }
    
    // Add service areas
    $serviceAreas = [
        [11, 'Central Mumbai', 'Mumbai', 'Maharashtra', '400001', 19.0760, 72.8777, 100.00, 30, TRUE],
        [12, 'Central Delhi', 'Delhi', 'Delhi', '110001', 28.6139, 77.2090, 50.00, 45, TRUE],
        [13, 'Pune City', 'Pune', 'Maharashtra', '411001', 18.5204, 73.8567, 150.00, 60, TRUE]
    ];
    
    foreach ($serviceAreas as $area) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO service_areas 
            (user_id, area_name, city, state, pincode, latitude, longitude, service_charge, travel_time_minutes, is_primary) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute($area);
        echo "✅ Added service area: {$area[1]}<br>";
    }
    
    // Add sample reviews
    $reviews = [
        [14, 11, 5, 'Excellent Plumbing Service', 'John did an amazing job fixing our bathroom. Very professional and clean work.'],
        [15, 12, 4, 'Great Cleaning Service', 'Maria and her team cleaned our office thoroughly. Will definitely book again.'],
        [14, 13, 5, 'Perfect Electrical Work', 'David installed our smart home system perfectly. Highly recommended!']
    ];
    
    foreach ($reviews as $review) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO user_reviews 
            (reviewer_id, reviewed_user_id, rating, review_title, review_text, is_verified) 
            VALUES (?, ?, ?, ?, ?, TRUE)");
        $stmt->execute($review);
        echo "✅ Added review: {$review[3]}<br>";
    }
    
    // Update user ratings based on reviews
    $pdo->exec("UPDATE users u SET 
        rating = (SELECT AVG(rating) FROM user_reviews WHERE reviewed_user_id = u.id),
        total_reviews = (SELECT COUNT(*) FROM user_reviews WHERE reviewed_user_id = u.id)
        WHERE u.role = 'provider'");
    echo "✅ Updated provider ratings<br>";
    
    // Add sample notifications
    $notifications = [
        [11, 'Profile Enhancement Complete', 'Your provider profile has been enhanced with new features!', 'profile'],
        [12, 'New Review Received', 'You received a 4-star review from a customer.', 'booking'],
        [13, 'Certificate Verified', 'Your electrical license has been verified by admin.', 'profile']
    ];
    
    foreach ($notifications as $notif) {
        $stmt = $pdo->prepare("INSERT INTO notifications 
            (user_id, title, message, type) 
            VALUES (?, ?, ?, ?)");
        $stmt->execute($notif);
        echo "✅ Added notification for user ID: {$notif[0]}<br>";
    }
    
    echo "<br>🎉 <strong>Database Enhancement Completed Successfully!</strong><br><br>";
    echo "📈 <strong>Summary:</strong><br>";
    echo "- Successfully executed {$successCount} database operations<br>";
    echo "- Enhanced existing user profiles<br>";
    echo "- Added professional portfolio system<br>";
    echo "- Created certificate management<br>";
    echo "- Implemented business hours tracking<br>";
    echo "- Added service area coverage<br>";
    echo "- Created review and rating system<br>";
    echo "- Added notification system<br>";
    echo "- Enhanced booking system<br>";
    echo "- Added admin action logging<br>";
    
    if (!empty($errors)) {
        echo "<br>⚠️ <strong>Errors encountered:</strong><br>";
        foreach ($errors as $error) {
            echo "- " . htmlspecialchars($error) . "<br>";
        }
    }
    
    echo "<br>🚀 <strong>Your QuickServe platform now has professional-level features!</strong><br>";
    echo "<br>🔗 <strong>Next Steps:</strong><br>";
    echo "1. Access your enhanced application: <a href='php-marketplace.html'>QuickServe App</a><br>";
    echo "2. Login with admin credentials to test new features<br>";
    echo "3. Check provider profiles with portfolios and certificates<br>";
    echo "4. Test the enhanced booking system<br>";
    
} catch (Exception $e) {
    echo "❌ <strong>Critical Error:</strong> " . $e->getMessage() . "<br>";
    echo "Please check your database connection and try again.<br>";
}
?>

<style>
body { 
    font-family: Arial, sans-serif; 
    max-width: 800px; 
    margin: 0 auto; 
    padding: 20px; 
    background: #f5f5f5; 
}
</style>