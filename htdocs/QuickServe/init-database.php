<?php
// Database Initialization Script for QuickServe
// Run this file once to set up the database and tables

require_once 'api/config.php';

// Initialize database
$result = initializeDatabase();

header('Content-Type: application/json');

if ($result['success']) {
    // Create some sample data
    createSampleData();
    echo json_encode([
        'success' => true,
        'message' => 'Database initialized successfully with sample data!',
        'admin_credentials' => [
            'email' => 'admin@quickserve.com',
            'password' => 'admin123'
        ]
    ]);
} else {
    echo json_encode($result);
}

function createSampleData() {
    $conn = getDBConnection();

    try {
        // Create sample service providers
        $providers = [
            [
                'email' => 'john.plumber@email.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'full_name' => 'John Smith',
                'phone' => '+91-9876543210',
                'role' => 'provider'
            ],
            [
                'email' => 'maria.cleaner@email.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'full_name' => 'Maria Rodriguez',
                'phone' => '+91-9876543211',
                'role' => 'provider'
            ],
            [
                'email' => 'david.electrician@email.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'full_name' => 'David Chen',
                'phone' => '+91-9876543212',
                'role' => 'provider'
            ]
        ];

        $providerIds = [];
        foreach ($providers as $provider) {
            $stmt = $conn->prepare("INSERT INTO users (email, password, full_name, phone, role, verification_status, is_active, profile_completed) VALUES (?, ?, ?, ?, ?, 'fully_verified', TRUE, TRUE)");
            $stmt->bind_param("sssss", $provider['email'], $provider['password'], $provider['full_name'], $provider['phone'], $provider['role']);
            $stmt->execute();
            $providerIds[] = $conn->insert_id;
        }

        // Create sample customers
        $customers = [
            [
                'email' => 'alice.customer@email.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'full_name' => 'Alice Johnson',
                'phone' => '+91-9876543213',
                'role' => 'customer'
            ],
            [
                'email' => 'bob.customer@email.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'full_name' => 'Bob Wilson',
                'phone' => '+91-9876543214',
                'role' => 'customer'
            ]
        ];

        foreach ($customers as $customer) {
            $stmt = $conn->prepare("INSERT INTO users (email, password, full_name, phone, role, verification_status, is_active, profile_completed) VALUES (?, ?, ?, ?, ?, 'fully_verified', TRUE, TRUE)");
            $stmt->bind_param("sssss", $customer['email'], $customer['password'], $customer['full_name'], $customer['phone'], $customer['role']);
            $stmt->execute();
        }

        // Create sample services
        $services = [
            [
                'provider_id' => $providerIds[0], // John Smith
                'title' => 'Professional Plumbing Services',
                'description' => 'Expert plumbing services including pipe repair, leak fixing, drain cleaning, and bathroom fixture installation. 10+ years of experience.',
                'category' => 'Plumbing',
                'price' => 500.00,
                'location' => 'Mumbai, Maharashtra'
            ],
            [
                'provider_id' => $providerIds[0], // John Smith
                'title' => 'Emergency Pipe Repair',
                'description' => '24/7 emergency plumbing services. Fast response for burst pipes, major leaks, and water damage prevention.',
                'category' => 'Plumbing',
                'price' => 800.00,
                'location' => 'Mumbai, Maharashtra'
            ],
            [
                'provider_id' => $providerIds[1], // Maria Rodriguez
                'title' => 'Deep House Cleaning',
                'description' => 'Comprehensive house cleaning service including dusting, vacuuming, bathroom cleaning, kitchen cleaning, and floor mopping.',
                'category' => 'Cleaning',
                'price' => 300.00,
                'location' => 'Delhi, NCR'
            ],
            [
                'provider_id' => $providerIds[1], // Maria Rodriguez
                'title' => 'Office Cleaning Services',
                'description' => 'Professional office cleaning for small to medium businesses. Regular maintenance cleaning and deep cleaning options available.',
                'category' => 'Cleaning',
                'price' => 400.00,
                'location' => 'Delhi, NCR'
            ],
            [
                'provider_id' => $providerIds[2], // David Chen
                'title' => 'Electrical Wiring & Installation',
                'description' => 'Licensed electrician providing wiring, outlet installation, circuit breaker repair, and electrical safety inspections.',
                'category' => 'Electrical',
                'price' => 600.00,
                'location' => 'Bangalore, Karnataka'
            ],
            [
                'provider_id' => $providerIds[2], // David Chen
                'title' => 'Smart Home Installation',
                'description' => 'Installation and setup of smart home devices including lighting, security cameras, and home automation systems.',
                'category' => 'Electrical',
                'price' => 1000.00,
                'location' => 'Bangalore, Karnataka'
            ]
        ];

        foreach ($services as $service) {
            $availability = json_encode([
                'monday' => true,
                'tuesday' => true,
                'wednesday' => true,
                'thursday' => true,
                'friday' => true,
                'saturday' => true,
                'sunday' => false
            ]);

            $workingHours = json_encode([
                'start' => '09:00',
                'end' => '18:00'
            ]);

            $stmt = $conn->prepare("INSERT INTO services (provider_id, title, description, category, price, location, availability, working_hours) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssdsss", $service['provider_id'], $service['title'], $service['description'], $service['category'], $service['price'], $service['location'], $availability, $workingHours);
            $stmt->execute();
        }

        return true;
    } catch (Exception $e) {
        error_log('Error creating sample data: ' . $e->getMessage());
        return false;
    }
}
?>