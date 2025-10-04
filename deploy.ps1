# 🚀 PHP_QUICKsERVE - Complete End-to-End Deployment Script for PowerShell
# This script automates the entire deployment process from start to finish

param(
    [string]$GitHubToken = $env:GITHUB_TOKEN,
    [switch]$SkipVercelLogin = $false
)

# Colors for output
$Red = "Red"
$Green = "Green"
$Yellow = "Yellow"
$Blue = "Blue"
$Cyan = "Cyan"

# Function to print colored output
function Write-Info {
    param([string]$Message)
    Write-Host "[INFO] $Message" -ForegroundColor $Blue
}

function Write-Success {
    param([string]$Message)
    Write-Host "[SUCCESS] $Message" -ForegroundColor $Green
}

function Write-Warning {
    param([string]$Message)
    Write-Host "[WARNING] $Message" -ForegroundColor $Yellow
}

function Write-Error {
    param([string]$Message)
    Write-Host "[ERROR] $Message" -ForegroundColor $Red
}

# Function to check if command exists
function Test-Command {
    param([string]$Command)
    try {
        Get-Command $Command -ErrorAction Stop | Out-Null
        return $true
    }
    catch {
        return $false
    }
}

# Function to check prerequisites
function Test-Prerequisites {
    Write-Info "Checking prerequisites..."
    
    $commands = @("node", "npm", "git")
    foreach ($cmd in $commands) {
        if (-not (Test-Command $cmd)) {
            Write-Error "$cmd is not installed. Please install $cmd first."
            exit 1
        }
    }
    
    Write-Success "All prerequisites are installed!"
}

# Function to install Vercel CLI
function Install-VercelCLI {
    Write-Info "Installing Vercel CLI..."
    
    if (Test-Command "vercel") {
        Write-Success "Vercel CLI is already installed!"
    }
    else {
        try {
            npm install -g vercel
            Write-Success "Vercel CLI installed successfully!"
        }
        catch {
            Write-Error "Failed to install Vercel CLI: $_"
            exit 1
        }
    }
}

# Function to setup Git configuration
function Set-GitConfig {
    Write-Info "Setting up Git configuration..."
    
    try {
        git config --global user.email "ajeetbr2-1@users.noreply.github.com"
        git config --global user.name "Ajeet Kumar"
        Write-Success "Git configuration set up!"
    }
    catch {
        Write-Error "Failed to setup Git configuration: $_"
        exit 1
    }
}

# Function to create deployment files
function New-DeploymentFiles {
    Write-Info "Creating deployment files..."
    
    # Create vercel.json
    $vercelConfig = @'
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
'@
    $vercelConfig | Out-File -FilePath "vercel.json" -Encoding UTF8
    
    # Create package.json
    $packageConfig = @'
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
'@
    $packageConfig | Out-File -FilePath "package.json" -Encoding UTF8
    
    # Create index.php
    $indexPhp = @'
<?php
/**
 * PHP_QUICKsERVE - Main Entry Point
 * Redirects to the main application
 */

// Redirect to the main QuickServe application
header('Location: htdocs/QuickServe/php-marketplace.html');
exit();
?>
'@
    $indexPhp | Out-File -FilePath "index.php" -Encoding UTF8
    
    # Create database schema
    $dbSchema = @'
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
'@
    $dbSchema | Out-File -FilePath "database-schema.sql" -Encoding UTF8
    
    Write-Success "Deployment files created successfully!"
}

# Function to commit and push to GitHub
function Invoke-GitOperations {
    Write-Info "Committing and pushing to GitHub..."
    
    try {
        git add .
        
        # Check if there are changes to commit
        $changes = git diff --staged --name-only
        if ($changes.Count -eq 0) {
            Write-Warning "No changes to commit!"
        }
        else {
            # Commit changes
            $commitMessage = "Automated deployment setup - $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
            git commit -m $commitMessage
            
            # Push to GitHub
            if ($GitHubToken) {
                $pushUrl = "https://$GitHubToken@github.com/ajeetbr2-1/PHP_QUICKsERVE.git main"
                git push $pushUrl
                Write-Success "Pushed to GitHub successfully!"
            }
            else {
                Write-Warning "GITHUB_TOKEN not set. Please push manually or set GITHUB_TOKEN environment variable."
                Write-Info "Manual push command:"
                Write-Host "git push https://your-token@github.com/ajeetbr2-1/PHP_QUICKsERVE.git main" -ForegroundColor $Cyan
            }
        }
        
        Write-Success "Git operations completed!"
    }
    catch {
        Write-Error "Git operations failed: $_"
        exit 1
    }
}

# Function to deploy to Vercel
function Deploy-Vercel {
    Write-Info "Deploying to Vercel..."
    
    try {
        # Check if user is logged in to Vercel
        if (-not $SkipVercelLogin) {
            $vercelWhoami = vercel whoami 2>&1
            if ($LASTEXITCODE -ne 0) {
                Write-Warning "Not logged in to Vercel. Please login first:"
                vercel login
            }
        }
        
        # Deploy to Vercel
        Write-Info "Deploying to production..."
        vercel --prod --yes
        
        Write-Success "Deployment to Vercel completed!"
    }
    catch {
        Write-Error "Vercel deployment failed: $_"
        exit 1
    }
}

# Function to display deployment information
function Show-DeploymentInfo {
    Write-Success "🎉 Deployment completed successfully!"
    Write-Host ""
    Write-Host "📋 Next Steps:" -ForegroundColor $Cyan
    Write-Host "1. Visit your Vercel dashboard to get the live URL"
    Write-Host "2. Set up Supabase database (optional for full functionality)"
    Write-Host "3. Configure environment variables in Vercel dashboard"
    Write-Host ""
    Write-Host "🔗 Useful Commands:" -ForegroundColor $Cyan
    Write-Host "- Check deployment status: vercel ls"
    Write-Host "- View logs: vercel logs"
    Write-Host "- Redeploy: vercel --prod --yes"
    Write-Host ""
    Write-Host "📖 For detailed instructions, see DEPLOYMENT_GUIDE.md" -ForegroundColor $Cyan
}

# Main execution
function Main {
    Write-Host "🚀 PHP_QUICKsERVE - Complete Deployment Automation" -ForegroundColor $Cyan
    Write-Host "==================================================" -ForegroundColor $Cyan
    Write-Host ""
    
    # Check prerequisites
    Test-Prerequisites
    
    # Install Vercel CLI
    Install-VercelCLI
    
    # Setup Git configuration
    Set-GitConfig
    
    # Create deployment files
    New-DeploymentFiles
    
    # Commit and push to GitHub
    Invoke-GitOperations
    
    # Deploy to Vercel
    Deploy-Vercel
    
    # Show deployment information
    Show-DeploymentInfo
}

# Run main function
Main
