# 📍 Near By Me - Local Service Marketplace Platform

**Near By Me** is a modern, fully-functional service marketplace platform that connects service providers with customers. Built with PHP, MySQL, and modern glassmorphism UI design.

---

## 🚀 Features

### For Customers
- ✨ Browse and search local services
- 🔍 Advanced search with category filters
- 📅 Easy booking system
- 📊 View booking history and status
- ⭐ Rate and review services

### For Service Providers
- 💼 Create and manage service listings
- 📋 Handle booking requests
- 📈 Track earnings and performance
- ⏰ Set availability and working hours

### For Administrators
- ⚙️ Complete user management
- 📊 Platform analytics and insights
- 🔧 Service moderation
- 👥 View all bookings and users

---

## 🛠️ Technology Stack

- **Frontend**: HTML5, CSS3 (Glassmorphism Design), JavaScript
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Server**: Apache (XAMPP/WAMP)
- **Architecture**: MVC Pattern with Object-Oriented PHP

---

## 📋 System Requirements

### Minimum Requirements:
- **Processor**: 1 GHz or faster
- **RAM**: 2 GB minimum
- **Storage**: 100 MB free space
- **OS**: Windows, macOS, or Linux
- **Browser**: Chrome, Firefox, Safari, or Edge (latest versions)

### Development Environment:
- **PHP**: Version 7.4 or higher
- **MySQL**: Version 5.7 or higher
- **Apache**: Version 2.4 or higher
- **XAMPP**: Version 8.0+ (recommended)

---

## 📥 Installation Guide

### Step 1: Install XAMPP

1. Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Install XAMPP on your Windows system
3. Start Apache and MySQL from XAMPP Control Panel

### Step 2: Setup Database

1. Open your browser and go to: `http://localhost/phpmyadmin`
2. You have two options:

**Option A: Automatic Setup (Recommended)**
- Copy the `NearByMe` folder to `C:\xampp\htdocs\`
- Navigate to: `http://localhost/NearByMe/database/init-database.php`
- This will automatically create the database and all tables with sample data

**Option B: Manual Setup**
- Create a new database named `nearbyme_db`
- Import the SQL file (if provided) or run the init script

### Step 3: Configure Database Connection

1. Open `config/database.php`
2. Update the database credentials if needed:
```php
private $host = "localhost";
private $db_name = "nearbyme_db";
private $username = "root";
private $password = "";  // Enter your MySQL password if set
```

### Step 4: Access the Application

Open your browser and navigate to:
```
http://localhost/NearByMe/
```

---

## 👤 Demo Credentials

After running the database initialization script, you can login with these credentials:

### Admin Account
- **Email**: admin@nearbyme.com
- **Password**: admin123
- **Access**: Full admin dashboard with user management and analytics

### Service Provider Account
- **Email**: john.smith@example.com
- **Password**: provider123
- **Access**: Provider dashboard with service management

### Customer Account
- **Email**: alice@example.com
- **Password**: customer123
- **Access**: Customer dashboard for booking services

---

## 📁 Project Structure

```
NearByMe/
│
├── api/                      # API endpoints
├── assets/
│   ├── css/
│   │   └── style.css        # Glassmorphism styling
│   └── js/                   # JavaScript files
│
├── config/
│   ├── auth.php              # Authentication handler
│   └── database.php          # Database configuration
│
├── database/
│   └── init-database.php     # Database initialization script
│
├── index.php                 # Homepage with service listings
├── login.php                 # Login page
├── register.php              # Registration page
├── logout.php                # Logout handler
├── customer-dashboard.php    # Customer dashboard
├── provider-dashboard.php    # Provider dashboard
├── admin-dashboard.php       # Admin panel
└── README.md                 # This file
```

---

## 🔐 Security Features

- ✅ Password hashing using bcrypt
- ✅ Prepared statements for SQL injection prevention
- ✅ Session-based authentication
- ✅ Role-based access control (RBAC)
- ✅ XSS protection through htmlspecialchars()
- ✅ Input validation and sanitization

---

## 🎨 Design Features

- 🌈 Modern glassmorphism UI design
- 📱 Fully responsive layout
- ⚡ Smooth animations and transitions
- 🎯 Intuitive user interface
- 🔄 Real-time form validation
- 💫 Beautiful gradient backgrounds

---

## 📊 Database Schema

### Tables:
1. **users** - User accounts (customers, providers, admins)
2. **services** - Service listings with details
3. **bookings** - Booking records and status
4. **reviews** - Ratings and reviews

### Key Relationships:
- One Provider can have Many Services (1:M)
- One Service can have Many Bookings (1:M)
- One Customer can have Many Bookings (1:M)
- One Booking can have One Review (1:1)

---

## 🚀 Usage Guide

### For Customers:
1. Register or login to your account
2. Browse services or use the search function
3. Click "Book Now" on any service
4. Select date and time for the booking
5. View your bookings in the dashboard

### For Service Providers:
1. Register with role "Service Provider"
2. Login and go to your dashboard
3. Click "Add New Service" to create a listing
4. Set your availability and working hours
5. Manage incoming booking requests

### For Administrators:
1. Login with admin credentials
2. Access the admin dashboard
3. View and manage all users
4. Monitor all services and bookings
5. Access platform analytics

---

## 🔧 Troubleshooting

### Common Issues:

**Problem**: "Connection failed" error
- **Solution**: Make sure XAMPP Apache and MySQL are running
- Check if database credentials in `config/database.php` are correct

**Problem**: "Table doesn't exist" error
- **Solution**: Run the database initialization script: `http://localhost/NearByMe/database/init-database.php`

**Problem**: CSS not loading
- **Solution**: Check if the `assets/css/style.css` file exists
- Clear browser cache (Ctrl + F5)

**Problem**: Login not working
- **Solution**: Ensure database is properly set up
- Verify that sessions are enabled in PHP

---

## 📝 Future Enhancements

Planned features for future versions:

- 🔔 Real-time notifications using WebSockets
- 💳 Payment gateway integration (Razorpay/PayTM)
- 📱 Progressive Web App (PWA) conversion
- 🗺️ Google Maps integration for locations
- ⭐ Complete review and rating system
- 💬 In-app messaging between customers and providers
- 📧 Email notifications for bookings
- 🤖 AI-powered service recommendations
- 🌐 Multi-language support
- 📱 Mobile app (React Native/Flutter)

---

## 👥 Development Team

This project was developed as part of the BCA Semester V project:

- **Ajeet Kumar** - 2305101010016
- **Abhishek Patel** - 2305101010007
- **Kundan Patil** - 2305101010187

**Under the guidance of**: Prof. PARMAR PRATIK  
**Institution**: Parul Institute of Computer Applications, Parul University

---

## 📄 License

This project is developed for educational purposes as part of the BCA curriculum at Parul University.

---

## 📞 Support

For any issues or questions, please contact the development team or raise an issue in the project repository.

---

## 🙏 Acknowledgments

- Parul University for providing the opportunity
- Prof. PARMAR PRATIK for guidance and support
- All contributors and testers

---

## 📚 References

- PHP Official Documentation: https://www.php.net/manual/
- MySQL Documentation: https://dev.mysql.com/doc/
- MDN Web Docs: https://developer.mozilla.org/
- XAMPP Documentation: https://www.apachefriends.org/docs/
- OWASP Security Guidelines: https://owasp.org/

---

**🎉 Thank you for using Near By Me!**

*Connecting local service providers with customers, one booking at a time.*
