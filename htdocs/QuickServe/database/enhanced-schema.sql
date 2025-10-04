-- Enhanced QuickServe Database Schema
-- Professional Features for Admin, Provider, and Customer Management

-- Add new columns to existing users table
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_verified BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_blocked BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255);
ALTER TABLE users ADD COLUMN IF NOT EXISTS bio TEXT;
ALTER TABLE users ADD COLUMN IF NOT EXISTS rating DECIMAL(3,2) DEFAULT 0.00;
ALTER TABLE users ADD COLUMN IF NOT EXISTS total_reviews INT DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS verification_date DATETIME;
ALTER TABLE users ADD COLUMN IF NOT EXISTS blocked_date DATETIME;
ALTER TABLE users ADD COLUMN IF NOT EXISTS blocked_reason TEXT;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_activity DATETIME;
ALTER TABLE users ADD COLUMN IF NOT EXISTS social_links JSON;

-- Provider Profile Details
CREATE TABLE IF NOT EXISTS provider_profiles (
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
    tax_id VARCHAR(50),
    years_in_business INT DEFAULT 0,
    team_size INT DEFAULT 1,
    emergency_services BOOLEAN DEFAULT FALSE,
    free_consultation BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Customer Profile Details
CREATE TABLE IF NOT EXISTS customer_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    pincode VARCHAR(20),
    preferred_contact_method ENUM('phone', 'email', 'whatsapp') DEFAULT 'phone',
    notification_preferences JSON,
    budget_range VARCHAR(50),
    preferred_timings JSON,
    property_type ENUM('apartment', 'house', 'office', 'commercial') DEFAULT 'apartment',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Portfolio Gallery
CREATE TABLE IF NOT EXISTS portfolio_items (
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
    project_duration VARCHAR(100),
    tags JSON,
    is_featured BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Certificates and Awards
CREATE TABLE IF NOT EXISTS certificates (
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
);

-- Work Experience
CREATE TABLE IF NOT EXISTS work_experience (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_name VARCHAR(255),
    position VARCHAR(255),
    start_date DATE,
    end_date DATE,
    is_current BOOLEAN DEFAULT FALSE,
    description TEXT,
    achievements TEXT,
    skills_used JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Business Hours
CREATE TABLE IF NOT EXISTS business_hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'),
    is_open BOOLEAN DEFAULT TRUE,
    open_time TIME,
    close_time TIME,
    break_start_time TIME,
    break_end_time TIME,
    is_24_hours BOOLEAN DEFAULT FALSE,
    special_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_day (user_id, day_of_week)
);

-- Service Areas
CREATE TABLE IF NOT EXISTS service_areas (
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
);

-- Admin Actions Log
CREATE TABLE IF NOT EXISTS admin_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action_type ENUM('block_user', 'unblock_user', 'verify_user', 'unverify_user', 'delete_service', 'approve_certificate', 'reject_certificate', 'send_notification') NOT NULL,
    target_user_id INT,
    target_service_id INT,
    target_certificate_id INT,
    details JSON,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Platform Analytics
CREATE TABLE IF NOT EXISTS platform_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    total_users INT DEFAULT 0,
    new_users INT DEFAULT 0,
    total_providers INT DEFAULT 0,
    new_providers INT DEFAULT 0,
    total_customers INT DEFAULT 0,
    new_customers INT DEFAULT 0,
    total_bookings INT DEFAULT 0,
    new_bookings INT DEFAULT 0,
    revenue DECIMAL(12,2) DEFAULT 0.00,
    avg_booking_value DECIMAL(10,2) DEFAULT 0.00,
    top_services JSON,
    top_locations JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date (date)
);

-- User Reviews and Ratings
CREATE TABLE IF NOT EXISTS user_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reviewer_id INT NOT NULL,
    reviewed_user_id INT NOT NULL,
    booking_id INT,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_title VARCHAR(255),
    review_text TEXT,
    photos JSON,
    is_verified BOOLEAN DEFAULT FALSE,
    helpful_count INT DEFAULT 0,
    response_from_provider TEXT,
    response_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
);

-- Notifications System
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('booking', 'payment', 'profile', 'system', 'promotional') DEFAULT 'system',
    is_read BOOLEAN DEFAULT FALSE,
    action_url VARCHAR(500),
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    expires_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- File Uploads
CREATE TABLE IF NOT EXISTS uploaded_files (
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
);

-- Enhanced Services Table Additions
ALTER TABLE services ADD COLUMN IF NOT EXISTS images JSON;
ALTER TABLE services ADD COLUMN IF NOT EXISTS video_url VARCHAR(500);
ALTER TABLE services ADD COLUMN IF NOT EXISTS tags JSON;
ALTER TABLE services ADD COLUMN IF NOT EXISTS min_duration INT DEFAULT 60;
ALTER TABLE services ADD COLUMN IF NOT EXISTS max_duration INT DEFAULT 120;
ALTER TABLE services ADD COLUMN IF NOT EXISTS advance_booking_days INT DEFAULT 1;
ALTER TABLE services ADD COLUMN IF NOT EXISTS cancellation_policy TEXT;
ALTER TABLE services ADD COLUMN IF NOT EXISTS requirements TEXT;
ALTER TABLE services ADD COLUMN IF NOT EXISTS inclusions JSON;
ALTER TABLE services ADD COLUMN IF NOT EXISTS exclusions JSON;
ALTER TABLE services ADD COLUMN IF NOT EXISTS is_featured BOOLEAN DEFAULT FALSE;
ALTER TABLE services ADD COLUMN IF NOT EXISTS discount_percentage DECIMAL(5,2) DEFAULT 0.00;
ALTER TABLE services ADD COLUMN IF NOT EXISTS is_emergency BOOLEAN DEFAULT FALSE;

-- Enhanced Bookings Table Additions
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS customer_address TEXT;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS special_instructions TEXT;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS estimated_duration INT DEFAULT 60;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS actual_start_time DATETIME;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS actual_end_time DATETIME;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS payment_status ENUM('pending', 'paid', 'refunded', 'partial') DEFAULT 'pending';
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50);
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS discount_applied DECIMAL(10,2) DEFAULT 0.00;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS tax_amount DECIMAL(10,2) DEFAULT 0.00;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS tip_amount DECIMAL(10,2) DEFAULT 0.00;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS cancellation_reason TEXT;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS rescheduled_from_id INT;

-- Create indexes for better performance
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_is_verified ON users(is_verified);
CREATE INDEX idx_users_is_blocked ON users(is_blocked);
CREATE INDEX idx_users_rating ON users(rating);
CREATE INDEX idx_portfolio_user_id ON portfolio_items(user_id);
CREATE INDEX idx_portfolio_featured ON portfolio_items(is_featured);
CREATE INDEX idx_certificates_user_id ON certificates(user_id);
CREATE INDEX idx_certificates_status ON certificates(verification_status);
CREATE INDEX idx_admin_actions_admin_id ON admin_actions(admin_id);
CREATE INDEX idx_admin_actions_type ON admin_actions(action_type);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_read ON notifications(is_read);
CREATE INDEX idx_reviews_reviewed_user ON user_reviews(reviewed_user_id);
CREATE INDEX idx_reviews_rating ON user_reviews(rating);
CREATE INDEX idx_services_featured ON services(is_featured);
CREATE INDEX idx_bookings_payment_status ON bookings(payment_status);