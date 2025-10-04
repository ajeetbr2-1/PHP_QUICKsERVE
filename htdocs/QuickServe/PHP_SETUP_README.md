# QuickServe - PHP Backend Setup Guide

## 🚀 XAMPP Setup Instructions

### Step 1: Install XAMPP
1. Download XAMPP from https://www.apachefriends.org/
2. Install XAMPP on your Windows system
3. Make sure Apache and MySQL are running

### Step 2: Database Setup
1. Open XAMPP Control Panel
2. Click "Start" for Apache and MySQL
3. Open phpMyAdmin: http://localhost/phpmyadmin
4. Create a new database named `quickserve_db`

### Step 3: Application Setup
1. Copy the entire `QuickServe` folder to `C:\xampp\htdocs\`
2. Open browser and go to: http://localhost/QuickServe/init-database.php
3. This will automatically create all tables and sample data

### Step 4: Access the Application
- **Main Application**: http://localhost/QuickServe/php-marketplace.html
- **Database Admin**: http://localhost/phpmyadmin

## 👥 Default Accounts

### Admin Account
- **Email**: admin@quickserve.com
- **Password**: admin123
- **Role**: Administrator (can view all users and bookings)

### Sample Service Providers
1. **John Smith** (Plumber)
   - Email: john.plumber@email.com
   - Password: password123

2. **Maria Rodriguez** (Cleaner)
   - Email: maria.cleaner@email.com
   - Password: password123

3. **David Chen** (Electrician)
   - Email: david.electrician@email.com
   - Password: password123

### Sample Customers
1. **Alice Johnson**
   - Email: alice.customer@email.com
   - Password: password123

2. **Bob Wilson**
   - Email: bob.customer@email.com
   - Password: password123

## 📁 File Structure

```
QuickServe/
├── api/                    # PHP API endpoints
│   ├── config.php         # Database configuration
│   ├── auth.php           # Authentication (login/signup)
│   ├── services.php       # Service management
│   └── bookings.php       # Booking management
├── init-database.php      # Database setup script
├── php-marketplace.html   # Main application
└── README.md             # This file
```

## 🔧 API Endpoints

### Authentication
- `POST /api/auth.php?action=login` - User login
- `POST /api/auth.php?action=signup` - User registration
- `POST /api/auth.php?action=logout` - User logout
- `GET /api/auth.php?action=profile` - Get user profile
- `PUT /api/auth.php?action=profile` - Update user profile

### Services
- `GET /api/services.php?action=list` - List all services
- `GET /api/services.php?action=my-services` - Provider's services
- `POST /api/services.php?action=create` - Create new service
- `PUT /api/services.php?action=update` - Update service
- `DELETE /api/services.php?action=delete` - Delete service

### Bookings
- `GET /api/bookings.php?action=list` - List user bookings
- `POST /api/bookings.php?action=create` - Create booking
- `PUT /api/bookings.php?action=update-status` - Update booking status

## 🎯 Features

### For Customers
- ✅ Browse and search services
- ✅ Book services with date/time selection
- ✅ View booking history
- ✅ User profile management

### For Service Providers
- ✅ Create and manage services
- ✅ View bookings and manage status
- ✅ Profile management

### For Admins
- ✅ View all users and bookings
- ✅ System management

## 🔒 Security Features

- Password hashing with bcrypt
- JWT-like token authentication
- Input sanitization
- SQL injection prevention
- CORS headers configured
- Role-based access control

## 🐛 Troubleshooting

### Database Connection Issues
1. Make sure MySQL is running in XAMPP
2. Check database name in `api/config.php`
3. Verify MySQL credentials (default: root, no password)

### API Not Working
1. Check Apache is running
2. Verify file paths in API calls
3. Check browser console for errors
4. Ensure CORS headers are set

### Sample Data Not Loading
1. Run `init-database.php` again
2. Check MySQL user permissions
3. Verify database exists

## 📞 Support

If you encounter issues:
1. Check XAMPP control panel for service status
2. Verify all files are in correct locations
3. Check browser developer tools for errors
4. Ensure PHP and MySQL versions are compatible

## 🔄 Migration from LocalStorage

This PHP backend replaces the localStorage-based system with:
- Real database storage
- User authentication
- Multi-user support
- Data persistence across sessions
- Admin panel capabilities

The frontend (`php-marketplace.html`) automatically detects and uses the PHP backend instead of localStorage.