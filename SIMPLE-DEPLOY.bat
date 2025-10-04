@echo off
echo ========================================
echo   PHP_QUICKsERVE - SIMPLE DEPLOY
echo ========================================
echo.

echo Step 1: Checking Node.js...
node --version
if %errorlevel% neq 0 (
    echo ERROR: Node.js not found!
    pause
    exit /b 1
)
echo OK!

echo.
echo Step 2: Checking npm...
npm --version
if %errorlevel% neq 0 (
    echo ERROR: npm not found!
    pause
    exit /b 1
)
echo OK!

echo.
echo Step 3: Checking Git...
git --version
if %errorlevel% neq 0 (
    echo ERROR: Git not found!
    pause
    exit /b 1
)
echo OK!

echo.
echo Step 4: Installing Vercel CLI...
vercel --version >nul 2>&1
if %errorlevel% neq 0 (
    npm install -g vercel
    echo Vercel CLI installed!
) else (
    echo Vercel CLI already installed!
)

echo.
echo Step 5: Setting up Git...
git config --global user.email "ajeetbr2-1@users.noreply.github.com"
git config --global user.name "Ajeet Kumar"
echo Git configured!

echo.
echo Step 6: Creating files...
echo Creating vercel.json...
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

echo Creating package.json...
(
echo {
echo   "name": "php-quickserve",
echo   "version": "1.0.0",
echo   "description": "PHP QuickServe Platform",
echo   "main": "index.js",
echo   "scripts": {
echo     "build": "echo No build step required"
echo   },
echo   "author": "Ajeet Kumar",
echo   "license": "MIT"
echo }
) > package.json

echo Creating index.php...
(
echo ^<?php
echo header^('Location: htdocs/QuickServe/php-marketplace.html'^);
echo exit^(^);
echo ?^>
) > index.php

echo Files created!

echo.
echo Step 7: Git operations...
git add .
git commit -m "Deploy setup - %date% %time%"
echo Committed!

echo.
echo Step 8: Deploying to Vercel...
vercel whoami >nul 2>&1
if %errorlevel% neq 0 (
    echo Please login to Vercel first:
    vercel login
)

vercel --prod --yes

echo.
echo ========================================
echo   DEPLOYMENT COMPLETED!
echo ========================================
echo.
echo Your app is now live on Vercel!
echo Check your Vercel dashboard for the URL.
echo.
pause
