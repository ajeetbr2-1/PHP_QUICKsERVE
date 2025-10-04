<?php
/**
 * QuickServe - Database Initialization Script
 * Creates all necessary tables and inserts sample data
 */

$host = "localhost";
$username = "root";
$password = "";
$db_name = "nearbyme_db";

// Create connection
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully or already exists<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select database
$conn->select_db($db_name);

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role ENUM('customer', 'provider', 'admin') NOT NULL DEFAULT 'customer',
    is_active BOOLEAN DEFAULT TRUE,
    rating DECIMAL(3,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
)";

if ($conn->query($sql) === TRUE) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Create services table
$sql = "CREATE TABLE IF NOT EXISTS services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    provider_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    category VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    location VARCHAR(255),
    availability JSON,
    working_hours JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_category (category),
    INDEX idx_location (location),
    INDEX idx_provider (provider_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Services table created successfully<br>";
} else {
    echo "Error creating services table: " . $conn->error . "<br>";
}

// Create bookings table
$sql = "CREATE TABLE IF NOT EXISTS bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    service_id INT NOT NULL,
    provider_id INT NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    total_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_customer (customer_id),
    INDEX idx_provider (provider_id),
    INDEX idx_status (status),
    INDEX idx_date (booking_date)
)";

if ($conn->query($sql) === TRUE) {
    echo "Bookings table created successfully<br>";
} else {
    echo "Error creating bookings table: " . $conn->error . "<br>";
}

// Create reviews table
$sql = "CREATE TABLE IF NOT EXISTS reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewee_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_reviewee (reviewee_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Reviews table created successfully<br>";
} else {
    echo "Error creating reviews table: " . $conn->error . "<br>";
}

// Create provider_profiles table
$sql = "CREATE TABLE IF NOT EXISTS provider_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    provider_id INT NOT NULL UNIQUE,
    bio TEXT,
    profile_image VARCHAR(255),
    experience_years INT DEFAULT 0,
    service_areas JSON,
    certifications JSON,
    languages JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Provider profiles table created successfully<br>";
} else {
    echo "Error creating provider profiles table: " . $conn->error . "<br>";
}

// Create portfolio_items table
$sql = "CREATE TABLE IF NOT EXISTS portfolio_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    provider_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    project_date DATE,
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_provider (provider_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Portfolio items table created successfully<br>";
} else {
    echo "Error creating portfolio items table: " . $conn->error . "<br>";
}

// Create certificates table
$sql = "CREATE TABLE IF NOT EXISTS certificates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    provider_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    issuing_organization VARCHAR(200),
    issue_date DATE,
    expiry_date DATE,
    certificate_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_provider (provider_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Certificates table created successfully<br>";
} else {
    echo "Error creating certificates table: " . $conn->error . "<br>";
}

// Insert default admin user
$admin_password = password_hash('admin123', PASSWORD_BCRYPT);
$sql = "INSERT IGNORE INTO users (email, password, full_name, phone, role) 
        VALUES ('admin@nearbyme.com', '$admin_password', 'System Admin', '9876543210', 'admin')";

if ($conn->query($sql) === TRUE) {
    echo "Admin user created successfully<br>";
}

// Insert sample service provider
$provider_password = password_hash('provider123', PASSWORD_BCRYPT);
$sql = "INSERT IGNORE INTO users (email, password, full_name, phone, role) 
        VALUES ('john.smith@example.com', '$provider_password', 'John Smith', '9876543211', 'provider')";

if ($conn->query($sql) === TRUE) {
    echo "Sample provider created successfully<br>";
}

// Insert sample customer
$customer_password = password_hash('customer123', PASSWORD_BCRYPT);
$sql = "INSERT IGNORE INTO users (email, password, full_name, phone, role) 
        VALUES ('alice@example.com', '$customer_password', 'Alice Johnson', '9876543212', 'customer')";

if ($conn->query($sql) === TRUE) {
    echo "Sample customer created successfully<br>";
}

// Get provider ID for sample services
$result = $conn->query("SELECT id FROM users WHERE email = 'john.smith@example.com'");
if ($result && $row = $result->fetch_assoc()) {
    $provider_id = $row['id'];
    
    // Insert provider profile
    $service_areas = json_encode(['Mumbai', 'Navi Mumbai', 'Thane', 'Pune']);
    $certifications = json_encode(['Licensed Plumber', 'Electrical Safety Certified']);
    $languages = json_encode(['English', 'Hindi', 'Marathi']);
    
    $sql = "INSERT INTO provider_profiles (provider_id, bio, experience_years, service_areas, certifications, languages) 
            VALUES ($provider_id, 'Professional service provider with 10+ years of experience in plumbing and electrical work. Committed to quality and customer satisfaction.', 10, '$service_areas', '$certifications', '$languages')
            ON DUPLICATE KEY UPDATE bio=VALUES(bio)";
    
    if ($conn->query($sql) === TRUE) {
        echo "Provider profile created successfully<br>";
    }
    
    // Insert portfolio items
    $portfolio_items = [
        [
            'title' => 'Residential Plumbing Renovation',
            'description' => 'Complete bathroom and kitchen plumbing renovation for a 3BHK apartment',
            'category' => 'Plumbing',
            'project_date' => '2024-10-15'
        ],
        [
            'title' => 'Commercial Electrical Installation',
            'description' => 'Full electrical setup for a new office space with modern lighting',
            'category' => 'Electrical',
            'project_date' => '2024-08-20'
        ],
        [
            'title' => 'Emergency Pipe Repair',
            'description' => 'Quick response and repair of burst pipe in residential complex',
            'category' => 'Plumbing',
            'project_date' => '2024-12-01'
        ]
    ];
    
    foreach ($portfolio_items as $item) {
        $sql = "INSERT INTO portfolio_items (provider_id, title, description, category, project_date) 
                VALUES ($provider_id, '{$item['title']}', '{$item['description']}', '{$item['category']}', '{$item['project_date']}')
                ON DUPLICATE KEY UPDATE id=id";
        
        if ($conn->query($sql) === TRUE) {
            echo "Portfolio item '{$item['title']}' created successfully<br>";
        }
    }
    
    // Insert certificates
    $certificates = [
        [
            'title' => 'Licensed Master Plumber',
            'issuing_organization' => 'Indian Plumbing Association',
            'issue_date' => '2015-06-01',
            'expiry_date' => NULL
        ],
        [
            'title' => 'Electrical Safety Certification',
            'issuing_organization' => 'Bureau of Indian Standards',
            'issue_date' => '2018-03-15',
            'expiry_date' => '2028-03-15'
        ],
        [
            'title' => 'HVAC Installation Certified',
            'issuing_organization' => 'HVAC Excellence',
            'issue_date' => '2020-01-10',
            'expiry_date' => '2025-01-10'
        ]
    ];
    
    foreach ($certificates as $cert) {
        $expiry = $cert['expiry_date'] ? "'{$cert['expiry_date']}'" : "NULL";
        $sql = "INSERT INTO certificates (provider_id, title, issuing_organization, issue_date, expiry_date) 
                VALUES ($provider_id, '{$cert['title']}', '{$cert['issuing_organization']}', '{$cert['issue_date']}', $expiry)
                ON DUPLICATE KEY UPDATE id=id";
        
        if ($conn->query($sql) === TRUE) {
            echo "Certificate '{$cert['title']}' created successfully<br>";
        }
    }
    
    // Insert sample services
    $services = [
        [
            'title' => 'Professional Plumbing Services',
            'description' => 'Expert plumbing services for all your needs - repairs, installations, and maintenance.',
            'category' => 'Plumbing',
            'price' => 500.00,
            'location' => 'Mumbai, Maharashtra'
        ],
        [
            'title' => 'Electrical Wiring & Installation',
            'description' => 'Licensed electrician providing safe and reliable electrical services.',
            'category' => 'Electrical',
            'price' => 600.00,
            'location' => 'Bangalore, Karnataka'
        ],
        [
            'title' => 'Home Cleaning Services',
            'description' => 'Professional deep cleaning services for homes and offices.',
            'category' => 'Cleaning',
            'price' => 300.00,
            'location' => 'Delhi, NCR'
        ]
    ];

    $availability = json_encode(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']);
    $working_hours = json_encode(['start' => '09:00', 'end' => '18:00']);

    foreach ($services as $service) {
        $sql = "INSERT INTO services (provider_id, title, description, category, price, location, availability, working_hours) 
                VALUES ($provider_id, '{$service['title']}', '{$service['description']}', '{$service['category']}', {$service['price']}, '{$service['location']}', '$availability', '$working_hours')
                ON DUPLICATE KEY UPDATE id=id";
        
        if ($conn->query($sql) === TRUE) {
            echo "Service '{$service['title']}' created successfully<br>";
        }
    }
}

echo "<br><strong>Database initialization completed successfully!</strong><br>";
echo "<br><strong>Demo Credentials:</strong><br>";
echo "Admin: admin@nearbyme.com / admin123<br>";
echo "Provider: john.smith@example.com / provider123<br>";
echo "Customer: alice@example.com / customer123<br>";

$conn->close();
?>
