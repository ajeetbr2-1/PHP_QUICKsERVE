@echo off
REM 🚀 PHP_QUICKsERVE - Complete End-to-End Deployment Script for Windows
REM This script automates the entire deployment process from start to finish

setlocal enabledelayedexpansion

REM Colors for output (Windows doesn't support colors in batch, so using text)
set "INFO=[INFO]"
set "SUCCESS=[SUCCESS]"
set "WARNING=[WARNING]"
set "ERROR=[ERROR]"

echo 🚀 PHP_QUICKsERVE - Complete Deployment Automation
echo ==================================================
echo.

REM Function to check if command exists
:check_command
where %1 >nul 2>&1
if %errorlevel% neq 0 (
    echo %ERROR% %1 is not installed. Please install %1 first.
    exit /b 1
)
exit /b 0

REM Check prerequisites
echo %INFO% Checking prerequisites...

call :check_command node
if %errorlevel% neq 0 exit /b 1

call :check_command npm
if %errorlevel% neq 0 exit /b 1

call :check_command git
if %errorlevel% neq 0 exit /b 1

echo %SUCCESS% All prerequisites are installed!
echo.

REM Install Vercel CLI
echo %INFO% Installing Vercel CLI...
vercel --version >nul 2>&1
if %errorlevel% neq 0 (
    npm install -g vercel
    echo %SUCCESS% Vercel CLI installed successfully!
) else (
    echo %SUCCESS% Vercel CLI is already installed!
)
echo.

REM Setup Git configuration
echo %INFO% Setting up Git configuration...
git config --global user.email "ajeetbr2-1@users.noreply.github.com"
git config --global user.name "Ajeet Kumar"
echo %SUCCESS% Git configuration set up!
echo.

REM Create deployment files
echo %INFO% Creating deployment files...

REM Create vercel.json
(
echo {
echo   "version": 2,
echo   "outputDirectory": "htdocs/QuickServe",
echo   "routes": [
echo     {
echo       "src": "/(.*)",
echo       "dest": "/$1"
echo     }
echo   ]
echo }
) > vercel.json

REM Create package.json
(
echo {
echo   "name": "php-quickserve",
echo   "version": "1.0.0",
echo   "description": "PHP QuickServe - Service Marketplace Platform",
echo   "main": "index.js",
echo   "scripts": {
echo     "build": "echo 'No build step required for PHP'",
echo     "dev": "php -S localhost:3000 -t htdocs/QuickServe"
echo   },
echo   "keywords": ["php", "marketplace", "services", "vercel"],
echo   "author": "Ajeet Kumar",
echo   "license": "MIT",
echo   "engines": {
echo     "php": ">=7.4"
echo   }
echo }
) > package.json

REM Create index.php
(
echo ^<?php
echo /**
echo  * PHP_QUICKsERVE - Main Entry Point
echo  * Redirects to the main application
echo  */
echo.
echo // Redirect to the main QuickServe application
echo header^('Location: htdocs/QuickServe/php-marketplace.html'^);
echo exit^(^);
echo ?^>
) > index.php

echo %SUCCESS% Deployment files created successfully!
echo.

REM Commit and push to GitHub
echo %INFO% Committing and pushing to GitHub...

git add .
git commit -m "Automated deployment setup - %date% %time%"

REM Check if GITHUB_TOKEN is set
if defined GITHUB_TOKEN (
    git push https://%GITHUB_TOKEN%@github.com/ajeetbr2-1/PHP_QUICKsERVE.git main
    echo %SUCCESS% Pushed to GitHub successfully!
) else (
    echo %WARNING% GITHUB_TOKEN not set. Please push manually or set GITHUB_TOKEN environment variable.
    echo %INFO% Manual push command:
    echo git push https://your-token@github.com/ajeetbr2-1/PHP_QUICKsERVE.git main
)

echo %SUCCESS% Git operations completed!
echo.

REM Deploy to Vercel
echo %INFO% Deploying to Vercel...

REM Check if user is logged in to Vercel
vercel whoami >nul 2>&1
if %errorlevel% neq 0 (
    echo %WARNING% Not logged in to Vercel. Please login first:
    vercel login
)

REM Deploy to Vercel
echo %INFO% Deploying to production...
vercel --prod --yes

echo %SUCCESS% Deployment to Vercel completed!
echo.

REM Show deployment information
echo %SUCCESS% 🎉 Deployment completed successfully!
echo.
echo 📋 Next Steps:
echo 1. Visit your Vercel dashboard to get the live URL
echo 2. Set up Supabase database (optional for full functionality)
echo 3. Configure environment variables in Vercel dashboard
echo.
echo 🔗 Useful Commands:
echo - Check deployment status: vercel ls
echo - View logs: vercel logs
echo - Redeploy: vercel --prod --yes
echo.
echo 📖 For detailed instructions, see DEPLOYMENT_GUIDE.md
echo.

pause
