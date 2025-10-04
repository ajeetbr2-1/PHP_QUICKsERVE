#!/bin/bash

# 🚀 PHP_QUICKsERVE - Complete End-to-End Deployment Script
# This script automates the entire deployment process from start to finish

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to check prerequisites
check_prerequisites() {
    print_status "Checking prerequisites..."
    
    # Check Node.js
    if ! command_exists node; then
        print_error "Node.js is not installed. Please install Node.js first."
        exit 1
    fi
    
    # Check npm
    if ! command_exists npm; then
        print_error "npm is not installed. Please install npm first."
        exit 1
    fi
    
    # Check git
    if ! command_exists git; then
        print_error "Git is not installed. Please install Git first."
        exit 1
    fi
    
    print_success "All prerequisites are installed!"
}

# Function to install Vercel CLI
install_vercel_cli() {
    print_status "Installing Vercel CLI..."
    
    if command_exists vercel; then
        print_success "Vercel CLI is already installed!"
    else
        npm install -g vercel
        print_success "Vercel CLI installed successfully!"
    fi
}

# Function to setup Git configuration
setup_git_config() {
    print_status "Setting up Git configuration..."
    
    # Check if git config is already set
    if git config --global user.email >/dev/null 2>&1; then
        print_success "Git configuration already exists!"
    else
        git config --global user.email "ajeetbr2-1@users.noreply.github.com"
        git config --global user.name "Ajeet Kumar"
        print_success "Git configuration set up!"
    fi
}

# Function to create deployment files
create_deployment_files() {
    print_status "Creating deployment files..."
    
    # Create vercel.json
    cat > vercel.json << 'EOF'
{
  "version": 2,
  "outputDirectory": "htdocs/QuickServe",
  "routes": [
    {
      "src": "/(.*)",
      "dest": "/$1"
    }
  ]
}
EOF

    # Create package.json
    cat > package.json << 'EOF'
{
  "name": "php-quickserve",
  "version": "1.0.0",
  "description": "PHP QuickServe - Service Marketplace Platform",
  "main": "index.js",
  "scripts": {
    "build": "echo 'No build step required for PHP'",
    "dev": "php -S localhost:3000 -t htdocs/QuickServe"
  },
  "keywords": ["php", "marketplace", "services", "vercel"],
  "author": "Ajeet Kumar",
  "license": "MIT",
  "engines": {
    "php": ">=7.4"
  }
}
EOF

    # Create index.php
    cat > index.php << 'EOF'
<?php
/**
 * PHP_QUICKsERVE - Main Entry Point
 * Redirects to the main application
 */

// Redirect to the main QuickServe application
header('Location: htdocs/QuickServe/php-marketplace.html');
exit();
?>
EOF

    # Create database schema
    cat > database-schema.sql << 'EOF'
-- PHP_QUICKsERVE Database Schema for Supabase PostgreSQL
-- Run this in Supabase SQL Editor to create all tables

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role VARCHAR(50) DEFAULT 'customer',
    verification_status VARCHAR(50) DEFAULT 'unverified',
    is_active BOOLEAN DEFAULT TRUE,
    profile_completed BOOLEAN DEFAULT FALSE,
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_reviews INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Create services table
CREATE TABLE IF NOT EXISTS services (
    id SERIAL PRIMARY KEY,
    provider_id INTEGER NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    location VARCHAR(255),
    availability JSONB,
    working_hours JSONB,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create bookings table
CREATE TABLE IF NOT EXISTS bookings (
    id SERIAL PRIMARY KEY,
    customer_id INTEGER NOT NULL,
    service_id INTEGER NOT NULL,
    provider_id INTEGER NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    notes TEXT,
    total_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id SERIAL PRIMARY KEY,
    booking_id INTEGER NOT NULL,
    reviewer_id INTEGER NOT NULL,
    reviewee_id INTEGER NOT NULL,
    rating INTEGER CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert sample data
INSERT INTO users (email, password, full_name, role, verification_status, is_active, profile_completed) VALUES
('admin@quickserve.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', 'admin', 'fully_verified', TRUE, TRUE),
('john.plumber@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Smith', 'provider', 'fully_verified', TRUE, TRUE),
('maria.cleaner@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria Rodriguez', 'provider', 'fully_verified', TRUE, TRUE),
('david.electrician@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'David Chen', 'provider', 'fully_verified', TRUE, TRUE),
('alice.customer@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alice Johnson', 'customer', 'phone_verified', TRUE, TRUE),
('bob.customer@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bob Wilson', 'customer', 'phone_verified', TRUE, TRUE);

-- Insert sample services
INSERT INTO services (provider_id, title, description, category, price, location, availability, working_hours) VALUES
(2, 'Professional Plumbing Services', 'Complete plumbing solutions for your home including pipe repairs, faucet installation, and drain cleaning.', 'Plumbing', 150.00, 'Mumbai, India', '{"monday": true, "tuesday": true, "wednesday": true, "thursday": true, "friday": true}', '{"start": "09:00", "end": "18:00"}'),
(3, 'House Cleaning Services', 'Thorough cleaning for your home including deep cleaning, regular maintenance, and post-construction cleanup.', 'Cleaning', 200.00, 'Delhi, India', '{"monday": true, "tuesday": true, "wednesday": true, "thursday": true, "friday": true, "saturday": true}', '{"start": "08:00", "end": "17:00"}'),
(4, 'Electrical Repairs', 'Expert electrical services including wiring, outlet installation, and electrical troubleshooting.', 'Electrical', 300.00, 'Bangalore, India', '{"monday": true, "tuesday": true, "wednesday": true, "thursday": true, "friday": true}', '{"start": "10:00", "end": "19:00"}');

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_services_provider_id ON services(provider_id);
CREATE INDEX IF NOT EXISTS idx_services_category ON services(category);
CREATE INDEX IF NOT EXISTS idx_bookings_customer_id ON bookings(customer_id);
CREATE INDEX IF NOT EXISTS idx_bookings_service_id ON bookings(service_id);
CREATE INDEX IF NOT EXISTS idx_bookings_provider_id ON bookings(provider_id);
CREATE INDEX IF NOT EXISTS idx_reviews_booking_id ON reviews(booking_id);
EOF

    print_success "Deployment files created successfully!"
}

# Function to commit and push to GitHub
commit_and_push() {
    print_status "Committing and pushing to GitHub..."
    
    # Add all files
    git add .
    
    # Check if there are changes to commit
    if git diff --staged --quiet; then
        print_warning "No changes to commit!"
    else
        # Commit changes
        git commit -m "Automated deployment setup - $(date)"
        
        # Push to GitHub
        if [ -n "$GITHUB_TOKEN" ]; then
            git push https://$GITHUB_TOKEN@github.com/ajeetbr2-1/PHP_QUICKsERVE.git main
        else
            print_warning "GITHUB_TOKEN not set. Please push manually or set GITHUB_TOKEN environment variable."
            print_status "Manual push command:"
            echo "git push https://your-token@github.com/ajeetbr2-1/PHP_QUICKsERVE.git main"
        fi
    fi
    
    print_success "Git operations completed!"
}

# Function to deploy to Vercel
deploy_to_vercel() {
    print_status "Deploying to Vercel..."
    
    # Check if user is logged in to Vercel
    if ! vercel whoami >/dev/null 2>&1; then
        print_warning "Not logged in to Vercel. Please login first:"
        vercel login
    fi
    
    # Deploy to Vercel
    print_status "Deploying to production..."
    vercel --prod --yes
    
    print_success "Deployment to Vercel completed!"
}

# Function to display deployment information
show_deployment_info() {
    print_success "🎉 Deployment completed successfully!"
    echo ""
    echo "📋 Next Steps:"
    echo "1. Visit your Vercel dashboard to get the live URL"
    echo "2. Set up Supabase database (optional for full functionality)"
    echo "3. Configure environment variables in Vercel dashboard"
    echo ""
    echo "🔗 Useful Commands:"
    echo "- Check deployment status: vercel ls"
    echo "- View logs: vercel logs"
    echo "- Redeploy: vercel --prod --yes"
    echo ""
    echo "📖 For detailed instructions, see DEPLOYMENT_GUIDE.md"
}

# Main execution
main() {
    echo "🚀 PHP_QUICKsERVE - Complete Deployment Automation"
    echo "=================================================="
    echo ""
    
    # Check prerequisites
    check_prerequisites
    
    # Install Vercel CLI
    install_vercel_cli
    
    # Setup Git configuration
    setup_git_config
    
    # Create deployment files
    create_deployment_files
    
    # Commit and push to GitHub
    commit_and_push
    
    # Deploy to Vercel
    deploy_to_vercel
    
    # Show deployment information
    show_deployment_info
}

# Run main function
main "$@"
