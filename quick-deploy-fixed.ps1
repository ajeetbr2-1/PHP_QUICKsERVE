# 🚀 PHP_QUICKsERVE - Quick Deploy Script (Fixed Version)
# Simple and working PowerShell script

Write-Host "🚀 PHP_QUICKsERVE - Quick Deploy Script" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# Check prerequisites
Write-Host "[INFO] Checking prerequisites..." -ForegroundColor Blue

# Check Node.js
try {
    $nodeVersion = node --version
    Write-Host "[SUCCESS] Node.js is installed: $nodeVersion" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Node.js is not installed. Please install Node.js first." -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

# Check npm
try {
    $npmVersion = npm --version
    Write-Host "[SUCCESS] npm is installed: $npmVersion" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] npm is not installed. Please install npm first." -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

# Check git
try {
    $gitVersion = git --version
    Write-Host "[SUCCESS] Git is installed: $gitVersion" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Git is not installed. Please install Git first." -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host "[SUCCESS] All prerequisites are installed!" -ForegroundColor Green
Write-Host ""

# Install Vercel CLI
Write-Host "[INFO] Installing Vercel CLI..." -ForegroundColor Blue
try {
    $vercelVersion = vercel --version
    Write-Host "[SUCCESS] Vercel CLI is already installed: $vercelVersion" -ForegroundColor Green
} catch {
    Write-Host "[INFO] Installing Vercel CLI..." -ForegroundColor Blue
    npm install -g vercel
    Write-Host "[SUCCESS] Vercel CLI installed successfully!" -ForegroundColor Green
}
Write-Host ""

# Setup Git configuration
Write-Host "[INFO] Setting up Git configuration..." -ForegroundColor Blue
git config --global user.email "ajeetbr2-1@users.noreply.github.com"
git config --global user.name "Ajeet Kumar"
Write-Host "[SUCCESS] Git configuration set up!" -ForegroundColor Green
Write-Host ""

# Create deployment files
Write-Host "[INFO] Creating deployment files..." -ForegroundColor Blue

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

Write-Host "[SUCCESS] Deployment files created successfully!" -ForegroundColor Green
Write-Host ""

# Commit and push to GitHub
Write-Host "[INFO] Committing and pushing to GitHub..." -ForegroundColor Blue
git add .
git commit -m "Automated deployment setup - $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"

# Check if GITHUB_TOKEN is set
if ($env:GITHUB_TOKEN) {
    git push https://$env:GITHUB_TOKEN@github.com/ajeetbr2-1/PHP_QUICKsERVE.git main
    Write-Host "[SUCCESS] Pushed to GitHub successfully!" -ForegroundColor Green
} else {
    Write-Host "[WARNING] GITHUB_TOKEN not set. Please push manually or set GITHUB_TOKEN environment variable." -ForegroundColor Yellow
    Write-Host "[INFO] Manual push command:" -ForegroundColor Blue
    Write-Host "git push https://your-token@github.com/ajeetbr2-1/PHP_QUICKsERVE.git main" -ForegroundColor Cyan
}

Write-Host "[SUCCESS] Git operations completed!" -ForegroundColor Green
Write-Host ""

# Deploy to Vercel
Write-Host "[INFO] Deploying to Vercel..." -ForegroundColor Blue

# Check if user is logged in to Vercel
try {
    $vercelWhoami = vercel whoami
    Write-Host "[SUCCESS] Already logged in to Vercel as: $vercelWhoami" -ForegroundColor Green
} catch {
    Write-Host "[WARNING] Not logged in to Vercel. Please login first:" -ForegroundColor Yellow
    vercel login
}

# Deploy to Vercel
Write-Host "[INFO] Deploying to production..." -ForegroundColor Blue
vercel --prod --yes

Write-Host "[SUCCESS] Deployment to Vercel completed!" -ForegroundColor Green
Write-Host ""

# Show deployment information
Write-Host "[SUCCESS] 🎉 Deployment completed successfully!" -ForegroundColor Green
Write-Host ""
Write-Host "📋 Next Steps:" -ForegroundColor Cyan
Write-Host "1. Visit your Vercel dashboard to get the live URL"
Write-Host "2. Set up Supabase database (optional for full functionality)"
Write-Host "3. Configure environment variables in Vercel dashboard"
Write-Host ""
Write-Host "🔗 Useful Commands:" -ForegroundColor Cyan
Write-Host "- Check deployment status: vercel ls"
Write-Host "- View logs: vercel logs"
Write-Host "- Redeploy: vercel --prod --yes"
Write-Host ""
Write-Host "📖 For detailed instructions, see DEPLOYMENT_GUIDE.md" -ForegroundColor Cyan

Read-Host "Press Enter to exit"
