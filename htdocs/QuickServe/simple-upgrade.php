<?php
// Simplified QuickServe Database Enhancement Script
require_once 'api/config.php';

try {
    $conn = getDBConnection();
    echo "<h2>🚀 QuickServe Database Enhancement</h2>";
    echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;'>";
    
    // Add new columns to users table
    $userUpdates = [
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_verified BOOLEAN DEFAULT FALSE",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_blocked BOOLEAN DEFAULT FALSE", 
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255)",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS bio TEXT",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS social_links JSON",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS verification_date DATETIME",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS blocked_date DATETIME",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS blocked_reason TEXT"
    ];
    
    foreach ($userUpdates as $sql) {
        try {
            $conn->query($sql);
            echo "✅ Updated users table<br>";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                echo "⚠️ " . $e->getMessage() . "<br>";
            }
        }
    }
    
    // Create provider profiles table
    $providerProfilesSQL = "CREATE TABLE IF NOT EXISTS provider_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        business_name VARCHAR(255),
        experience_years INT DEFAULT 0,
        hourly_rate DECIMAL(10,2),
        service_radius INT DEFAULT 10,
        languages_spoken JSON,
        specializations JSON,
        business_license VARCHAR(255),
        insurance_details TEXT,
        emergency_services BOOLEAN DEFAULT FALSE,
        free_consultation BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($providerProfilesSQL)) {
        echo "✅ Created provider_profiles table<br>";
    } else {
        echo "ℹ️ Provider profiles table already exists<br>";
    }
    
    // Create portfolio table
    $portfolioSQL = "CREATE TABLE IF NOT EXISTS portfolio_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        image_url VARCHAR(500),
        video_url VARCHAR(500),
        project_date DATE,
        project_location VARCHAR(255),
        project_cost DECIMAL(10,2),
        client_name VARCHAR(255),
        is_featured BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($portfolioSQL)) {
        echo "✅ Created portfolio_items table<br>";
    } else {
        echo "ℹ️ Portfolio table already exists<br>";
    }
    
    // Create certificates table
    $certificatesSQL = "CREATE TABLE IF NOT EXISTS certificates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        issuing_organization VARCHAR(255),
        certificate_url VARCHAR(500),
        issue_date DATE,
        expiry_date DATE,
        certificate_type ENUM('certification', 'award', 'license', 'training') DEFAULT 'certification',
        verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($certificatesSQL)) {
        echo "✅ Created certificates table<br>";
    } else {
        echo "ℹ️ Certificates table already exists<br>";
    }
    
    // Create business hours table
    $businessHoursSQL = "CREATE TABLE IF NOT EXISTS business_hours (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'),
        is_open BOOLEAN DEFAULT TRUE,
        open_time TIME,
        close_time TIME,
        is_24_hours BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_day (user_id, day_of_week)
    )";
    
    if ($conn->query($businessHoursSQL)) {
        echo "✅ Created business_hours table<br>";
    } else {
        echo "ℹ️ Business hours table already exists<br>";
    }
    
    // Create admin actions table
    $adminActionsSQL = "CREATE TABLE IF NOT EXISTS admin_actions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        action_type ENUM('block_user', 'unblock_user', 'verify_user', 'unverify_user', 'delete_service', 'approve_certificate', 'reject_certificate') NOT NULL,
        target_user_id INT,
        target_service_id INT,
        target_certificate_id INT,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    if ($conn->query($adminActionsSQL)) {
        echo "✅ Created admin_actions table<br>";
    } else {
        echo "ℹ️ Admin actions table already exists<br>";
    }
    
    // Create service areas table
    $serviceAreasSQL = "CREATE TABLE IF NOT EXISTS service_areas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        area_name VARCHAR(255),
        city VARCHAR(100),
        state VARCHAR(100),
        pincode VARCHAR(20),
        latitude DECIMAL(10, 8),
        longitude DECIMAL(11, 8),
        service_charge DECIMAL(10,2) DEFAULT 0.00,
        travel_time_minutes INT DEFAULT 0,
        is_primary BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($serviceAreasSQL)) {
        echo "✅ Created service_areas table<br>";
    } else {
        echo "ℹ️ Service areas table already exists<br>";
    }
    
    // Create work experience table
    $workExpSQL = "CREATE TABLE IF NOT EXISTS work_experience (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        company_name VARCHAR(255),
        position VARCHAR(255),
        start_date DATE,
        end_date DATE,
        is_current BOOLEAN DEFAULT FALSE,
        description TEXT,
        achievements TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    if ($conn->query($workExpSQL)) {
        echo "✅ Created work_experience table<br>";
    } else {
        echo "ℹ️ Work experience table already exists<br>";
    }
    
    // Create uploaded files table
    $uploadedFilesSQL = "CREATE TABLE IF NOT EXISTS uploaded_files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_type VARCHAR(100),
        file_size INT,
        purpose ENUM('profile_image', 'portfolio', 'certificate', 'service_image', 'document') NOT NULL,
        reference_id INT,
        is_public BOOLEAN DEFAULT TRUE,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($uploadedFilesSQL)) {
        echo "✅ Created uploaded_files table<br>";
    } else {
        echo "ℹ️ Uploaded files table already exists<br>";
    }
    
    // Create notifications table
    $notificationsSQL = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('booking', 'payment', 'profile', 'system', 'promotional') DEFAULT 'system',
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($notificationsSQL)) {
        echo "✅ Created notifications table<br>";
    } else {
        echo "ℹ️ Notifications table already exists<br>";
    }
    
    // Update existing users
    $conn->query("UPDATE users SET is_verified = TRUE, bio = 'Professional service provider' WHERE role = 'provider'");
    echo "✅ Updated existing provider profiles<br>";
    
    // Add sample data if tables are empty
    $checkProvider = $conn->query("SELECT COUNT(*) as count FROM provider_profiles");
    $count = $checkProvider->fetch_assoc()['count'];
    
    if ($count == 0) {
        // Add sample provider profiles
        $providers = [
            "INSERT INTO provider_profiles (user_id, business_name, experience_years, hourly_rate, service_radius, languages_spoken, specializations, emergency_services, free_consultation) 
             VALUES (11, 'John\\'s Plumbing Services', 8, 500.00, 25, '[\"English\", \"Hindi\"]', '[\"Residential Plumbing\", \"Emergency Repairs\"]', TRUE, TRUE)",
            "INSERT INTO provider_profiles (user_id, business_name, experience_years, hourly_rate, service_radius, languages_spoken, specializations, emergency_services, free_consultation) 
             VALUES (12, 'Maria\\'s Cleaning Co.', 5, 300.00, 15, '[\"English\", \"Spanish\"]', '[\"Deep Cleaning\", \"Regular Maintenance\"]', FALSE, TRUE)",
            "INSERT INTO provider_profiles (user_id, business_name, experience_years, hourly_rate, service_radius, languages_spoken, specializations, emergency_services, free_consultation) 
             VALUES (13, 'David\\'s Electrical Works', 10, 800.00, 30, '[\"English\", \"Hindi\"]', '[\"Home Wiring\", \"Commercial Electrical\"]', TRUE, FALSE)"
        ];
        
        foreach ($providers as $sql) {
            if ($conn->query($sql)) {
                echo "✅ Added provider profile<br>";
            }
        }
        
        // Add sample portfolio items
        $portfolios = [
            "INSERT INTO portfolio_items (user_id, title, description, image_url, project_date, project_location, project_cost, is_featured) 
             VALUES (11, 'Modern Bathroom Renovation', 'Complete bathroom makeover with modern fixtures', 'https://via.placeholder.com/400x300', '2024-01-15', 'Mumbai', 25000, TRUE)",
            "INSERT INTO portfolio_items (user_id, title, description, image_url, project_date, project_location, project_cost, is_featured) 
             VALUES (12, 'Office Deep Cleaning Project', 'Complete office cleaning and sanitization', 'https://via.placeholder.com/400x300', '2024-02-20', 'Delhi', 5000, TRUE)",
            "INSERT INTO portfolio_items (user_id, title, description, image_url, project_date, project_location, project_cost, is_featured) 
             VALUES (13, 'Smart Home Electrical Setup', 'Complete smart home wiring and automation', 'https://via.placeholder.com/400x300', '2024-03-10', 'Pune', 50000, TRUE)"
        ];
        
        foreach ($portfolios as $sql) {
            if ($conn->query($sql)) {
                echo "✅ Added portfolio item<br>";
            }
        }
        
        // Add sample certificates
        $certificates = [
            "INSERT INTO certificates (user_id, title, issuing_organization, issue_date, certificate_type, verification_status) 
             VALUES (11, 'Certified Plumbing Professional', 'Indian Plumbing Association', '2020-06-15', 'certification', 'verified')",
            "INSERT INTO certificates (user_id, title, issuing_organization, issue_date, expiry_date, certificate_type, verification_status) 
             VALUES (12, 'Professional Cleaning Certificate', 'Cleaning Services Institute', '2021-03-20', '2026-03-20', 'certification', 'verified')",
            "INSERT INTO certificates (user_id, title, issuing_organization, issue_date, expiry_date, certificate_type, verification_status) 
             VALUES (13, 'Licensed Electrician', 'Electrical Board of India', '2019-09-10', '2024-09-10', 'license', 'verified')"
        ];
        
        foreach ($certificates as $sql) {
            if ($conn->query($sql)) {
                echo "✅ Added certificate<br>";
            }
        }
        
        // Add business hours
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        foreach ([11, 12, 13] as $userId) {
            foreach ($days as $day) {
                $conn->query("INSERT IGNORE INTO business_hours (user_id, day_of_week, is_open, open_time, close_time) 
                             VALUES ($userId, '$day', TRUE, '09:00:00', '18:00:00')");
            }
            $conn->query("INSERT IGNORE INTO business_hours (user_id, day_of_week, is_open) 
                         VALUES ($userId, 'sunday', FALSE)");
            echo "✅ Added business hours for provider $userId<br>";
        }
    } else {
        echo "ℹ️ Sample data already exists<br>";
    }
    
    echo "<br><h3>🎉 Database Enhancement Complete!</h3>";
    echo "<p><strong>New Features Added:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Provider Profile Management</li>";
    echo "<li>✅ Portfolio & Gallery System</li>";
    echo "<li>✅ Certificate Management</li>";
    echo "<li>✅ Business Hours Tracking</li>";
    echo "<li>✅ Admin Action Logging</li>";
    echo "<li>✅ Notification System</li>";
    echo "<li>✅ User Verification System</li>";
    echo "</ul>";
    
    echo "<p><a href='php-marketplace.html' style='background: #007bff; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;'>🚀 Launch Enhanced App</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; font-weight: bold;'>❌ Error: " . $e->getMessage() . "</div>";
}
?>

<style>
body { background: #f5f5f5; font-family: Arial, sans-serif; }
h2 { color: #007bff; }
h3 { color: #28a745; }
</style>