<?php
require_once 'api/config.php';

$conn = getDBConnection();

echo "<h2>🌱 Seeding Demo Data for Providers</h2>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;'>";

// Get existing provider IDs
$result = $conn->query("SELECT id, full_name FROM users WHERE role = 'provider' LIMIT 5");
$providers = [];
while ($row = $result->fetch_assoc()) {
    $providers[] = $row;
}

if (empty($providers)) {
    echo "❌ No providers found. Please create provider accounts first.<br>";
    exit;
}

foreach ($providers as $provider) {
    $userId = $provider['id'];
    $name = $provider['full_name'];
    
    echo "<h3>👤 Seeding data for: $name (ID: $userId)</h3>";
    
    // Add Portfolio Items
    $portfolios = [
        ["Premium Bathroom Renovation", "Complete bathroom makeover with modern fixtures and tiles", "https://images.unsplash.com/photo-1584622650111-993a426fbf0a?w=800", "2024-03-15", "Mumbai", 45000],
        ["Kitchen Remodeling Project", "Modern kitchen with island and custom cabinets", "https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=800", "2024-02-20", "Delhi", 85000],
        ["Office Space Design", "Contemporary office design with ergonomic furniture", "https://images.unsplash.com/photo-1497366216548-37526070297c?w=800", "2024-01-10", "Bangalore", 120000],
        ["Living Room Makeover", "Cozy living space with smart home integration", "https://images.unsplash.com/photo-1565182999561-18d7dc61c393?w=800", "2023-12-05", "Pune", 55000],
        ["Garden Landscaping", "Beautiful outdoor space with lighting", "https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800", "2023-11-15", "Chennai", 35000]
    ];
    
    $count = 0;
    foreach (array_slice($portfolios, 0, 3) as $p) {
        $stmt = $conn->prepare("INSERT IGNORE INTO portfolio_items (user_id, title, description, image_url, project_date, project_location, project_cost, is_featured, video_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $featured = $count == 0 ? 1 : 0;
        $videoUrl = $count == 0 ? "https://www.youtube.com/embed/dQw4w9WgXcQ" : "";
        $stmt->bind_param("issssssis", $userId, $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $featured, $videoUrl);
        if ($stmt->execute()) {
            echo "✅ Added portfolio: {$p[0]}<br>";
        }
        $count++;
    }
    
    // Add Certificates
    $certificates = [
        ["Professional Certification", "Industry Standards Board", "2023-06-15", "2028-06-15", "certification", "https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf"],
        ["Safety Training Certificate", "National Safety Council", "2023-03-20", "2025-03-20", "training", ""],
        ["Excellence Award 2023", "Service Provider Association", "2023-12-01", null, "award", ""],
        ["Business License", "Municipal Corporation", "2022-01-15", "2027-01-15", "license", "https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf"],
        ["ISO 9001 Certification", "Quality Standards Org", "2023-08-10", "2026-08-10", "certification", ""]
    ];
    
    foreach (array_slice($certificates, 0, 3) as $c) {
        $stmt = $conn->prepare("INSERT IGNORE INTO certificates (user_id, title, issuing_organization, issue_date, expiry_date, certificate_type, certificate_url, verification_status, description) VALUES (?, ?, ?, ?, ?, ?, ?, 'verified', 'Professional qualification')");
        $stmt->bind_param("issssss", $userId, $c[0], $c[1], $c[2], $c[3], $c[4], $c[5]);
        if ($stmt->execute()) {
            echo "✅ Added certificate: {$c[0]}<br>";
        }
    }
    
    // Add Service Areas
    $serviceAreas = [
        ["Central Business District", "Mumbai", "Maharashtra", "400001", 19.0760, 72.8777, 200, 30, 1],
        ["Suburban Area", "Mumbai", "Maharashtra", "400050", 19.1136, 72.8697, 150, 45, 0],
        ["Airport Zone", "Mumbai", "Maharashtra", "400099", 19.0896, 72.8656, 300, 60, 0],
        ["Coastal Region", "Mumbai", "Maharashtra", "400706", 19.0458, 72.8207, 250, 50, 0],
        ["Tech Park Area", "Pune", "Maharashtra", "411001", 18.5204, 73.8567, 180, 40, 0]
    ];
    
    // Clear existing service areas for this provider
    $conn->query("DELETE FROM service_areas WHERE user_id = $userId");
    
    foreach (array_slice($serviceAreas, 0, 3) as $sa) {
        $stmt = $conn->prepare("INSERT INTO service_areas (user_id, area_name, city, state, pincode, latitude, longitude, service_charge, travel_time_minutes, is_primary) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssdddii", $userId, $sa[0], $sa[1], $sa[2], $sa[3], $sa[4], $sa[5], $sa[6], $sa[7], $sa[8]);
        if ($stmt->execute()) {
            echo "✅ Added service area: {$sa[0]}<br>";
        }
    }
    
    // Add Work Experience
    $workExp = [
        ["TechCorp Solutions", "Senior Service Manager", "2020-01-15", null, 1, "Leading service operations team", "Improved customer satisfaction by 40%"],
        ["GlobalServ Inc", "Service Specialist", "2017-06-01", "2019-12-31", 0, "Handled premium client accounts", "Managed 50+ high-value projects"],
        ["StartUp Services", "Junior Associate", "2015-03-10", "2017-05-30", 0, "Customer service and support", "Learned industry best practices"],
        ["Freelance", "Independent Contractor", "2013-01-01", "2015-02-28", 0, "Self-employed service provider", "Built initial client base"]
    ];
    
    foreach (array_slice($workExp, 0, 2) as $we) {
        $stmt = $conn->prepare("INSERT IGNORE INTO work_experience (user_id, company_name, position, start_date, end_date, is_current, description, achievements) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss", $userId, $we[0], $we[1], $we[2], $we[3], $we[4], $we[5], $we[6]);
        if ($stmt->execute()) {
            echo "✅ Added work experience: {$we[1]} at {$we[0]}<br>";
        }
    }
    
    // Add/Update Business Hours
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    foreach ($days as $index => $day) {
        $isOpen = ($day === 'sunday') ? 0 : 1;
        $openTime = $isOpen ? '09:00:00' : null;
        $closeTime = $isOpen ? ($day === 'saturday' ? '14:00:00' : '18:00:00') : null;
        
        $stmt = $conn->prepare("INSERT INTO business_hours (user_id, day_of_week, is_open, open_time, close_time) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE is_open = VALUES(is_open), open_time = VALUES(open_time), close_time = VALUES(close_time)");
        $stmt->bind_param("issss", $userId, $day, $isOpen, $openTime, $closeTime);
        $stmt->execute();
    }
    echo "✅ Set business hours (Mon-Sat, closed Sunday)<br>";
    
    // Update/Create Provider Profile
    $businessNames = ["Pro Services Ltd", "Expert Solutions", "Premium Care Services", "Quality First Services", "Trusted Professionals"];
    $languages = json_encode(["English", "Hindi", "Marathi"]);
    $specializations = json_encode(["Emergency Services", "Residential", "Commercial", "Premium Support"]);
    
    // Check if profile exists
    $check = $conn->query("SELECT id FROM provider_profiles WHERE user_id = $userId");
    if ($check->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE provider_profiles SET business_name = ?, experience_years = ?, hourly_rate = ?, service_radius = ?, languages_spoken = ?, specializations = ?, emergency_services = 1, free_consultation = 1 WHERE user_id = ?");
        $businessName = $businessNames[array_rand($businessNames)];
        $expYears = rand(5, 15);
        $hourlyRate = rand(500, 2000);
        $radius = rand(10, 50);
        $stmt->bind_param("sidissi", $businessName, $expYears, $hourlyRate, $radius, $languages, $specializations, $userId);
    } else {
        $stmt = $conn->prepare("INSERT INTO provider_profiles (user_id, business_name, experience_years, hourly_rate, service_radius, languages_spoken, specializations, emergency_services, free_consultation) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1)");
        $businessName = $businessNames[array_rand($businessNames)];
        $expYears = rand(5, 15);
        $hourlyRate = rand(500, 2000);
        $radius = rand(10, 50);
        $stmt->bind_param("isidiss", $userId, $businessName, $expYears, $hourlyRate, $radius, $languages, $specializations);
    }
    
    if ($stmt->execute()) {
        echo "✅ Updated provider business profile<br>";
    }
    
    // Update user profile
    $bios = [
        "Experienced professional with a passion for quality service delivery",
        "Dedicated service provider committed to customer satisfaction",
        "Expert in the field with proven track record of excellence",
        "Reliable and trustworthy professional serving the community",
        "Your trusted partner for all service needs"
    ];
    
    $socialLinks = json_encode([
        'facebook' => 'https://facebook.com/provider' . $userId,
        'instagram' => 'https://instagram.com/provider' . $userId,
        'linkedin' => 'https://linkedin.com/in/provider' . $userId,
        'twitter' => 'https://twitter.com/provider' . $userId
    ]);
    
    $bio = $bios[array_rand($bios)];
    $profileImage = "https://ui-avatars.com/api/?name=" . urlencode($name) . "&size=200&background=667eea&color=fff";
    
    $stmt = $conn->prepare("UPDATE users SET bio = ?, profile_image = ?, social_links = ?, is_verified = 1, rating = ?, total_reviews = ? WHERE id = ?");
    $rating = round(3.5 + (rand(0, 15) / 10), 1);
    $reviews = rand(10, 50);
    $stmt->bind_param("sssdii", $bio, $profileImage, $socialLinks, $rating, $reviews, $userId);
    
    if ($stmt->execute()) {
        echo "✅ Updated user profile with bio and social links<br>";
    }
    
    // Add notifications
    $notifs = [
        ["Welcome!", "Your provider profile has been enhanced with new features", "profile"],
        ["New Booking", "You have received a new booking request", "booking"],
        ["Profile Views", "Your profile was viewed 25 times this week", "profile"]
    ];
    
    foreach ($notifs as $n) {
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, is_read) VALUES (?, ?, ?, ?, 0)");
        $stmt->bind_param("isss", $userId, $n[0], $n[1], $n[2]);
        $stmt->execute();
    }
    echo "✅ Added notifications<br>";
    
    echo "<hr>";
}

// Add some sample admin actions
$admins = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
if ($admin = $admins->fetch_assoc()) {
    $adminId = $admin['id'];
    
    $actions = [
        ['verify_user', $providers[0]['id'], "Verified after document review"],
        ['approve_certificate', null, "Certificate verified and approved"],
        ['block_user', null, "Temporary block for policy violation"],
        ['unblock_user', null, "Block removed after review"]
    ];
    
    foreach ($actions as $a) {
        $stmt = $conn->prepare("INSERT INTO admin_actions (admin_id, action_type, target_user_id, notes) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isis", $adminId, $a[0], $a[1], $a[2]);
        $stmt->execute();
    }
    echo "<br>✅ Added sample admin actions<br>";
}

echo "<br><h3>🎉 Demo Data Seeding Complete!</h3>";
echo "<p>All providers now have:</p>";
echo "<ul>";
echo "<li>✅ Portfolio items with images</li>";
echo "<li>✅ Verified certificates</li>";
echo "<li>✅ Service areas with charges</li>";
echo "<li>✅ Work experience</li>";
echo "<li>✅ Business hours</li>";
echo "<li>✅ Complete business profiles</li>";
echo "<li>✅ Profile images and social links</li>";
echo "<li>✅ Ratings and reviews</li>";
echo "<li>✅ Notifications</li>";
echo "</ul>";

echo "<p><a href='enhanced-quickserve.html' style='background: #007bff; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;'>🚀 Open Enhanced App</a></p>";
echo "</div>";

?>
<style>
body { background: #f5f5f5; font-family: Arial, sans-serif; }
h2 { color: #007bff; }
h3 { color: #28a745; }
hr { border: 1px solid #ddd; margin: 20px 0; }
</style>