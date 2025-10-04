# 🌟 QuickServe - QuickServe Local Service Platform

Complete service marketplace with provider portfolio management, file uploads, and admin controls!

## ✨ Features Implemented

### 1. **Profile Management** 👤
- Easy-to-use profile edit page for all users
- **Drag-and-drop image upload** for provider profiles
- Bio, experience, service areas, and languages for providers
- Real-time image preview
- File validation (max 5MB, images only)

### 2. **Provider Portfolio Management** 🎨
- Add/Edit/Delete portfolio items with images
- Add/Edit/Delete professional certificates
- Showcase previous work with project details
- Drag-and-drop file upload for portfolio images
- Certificate upload support (PDF & images)

### 3. **Admin Services Management** 🛠️
- View all services on the platform
- Toggle service active/inactive status
- Delete services
- Search and filter by:
  - Service name or provider
  - Category
  - Status (active/inactive)
- Statistics dashboard

### 4. **Admin Bookings Management** 📋
- View all bookings with complete details
- Update booking status with modal interface
- Delete bookings
- Search and filter by:
  - Customer, provider, or service name
  - Status (pending, confirmed, in progress, completed, cancelled)
  - Booking date
- Revenue tracking
- Comprehensive statistics

### 5. **Secure File Upload System** 📁
- Dedicated API endpoint for file uploads
- File type validation
- File size limits (5MB max)
- Automatic file organization by type:
  - `uploads/profiles/` - Profile images
  - `uploads/portfolio/` - Portfolio images
  - `uploads/certificates/` - Certificates
  - `uploads/services/` - Service images
- Unique filename generation to prevent conflicts

### 6. **Demo Data** 🎲
- Sample provider with:
  - Complete profile
  - 3 portfolio items
  - 3 professional certificates
  - 3 services
- Pre-configured service areas and languages
- Ready-to-test demo credentials

---

## 🚀 Setup Instructions

### Step 1: Database Setup

1. **Open phpMyAdmin**: `http://localhost/phpmyadmin/`

2. **Run Reset Script** (to clean existing database):
   - Click on "SQL" tab
   - Copy and paste content from: `database/reset-database.sql`
   - Click "Go"

3. **Initialize Fresh Database**:
   - Open in browser: `http://localhost/QuickServe/NearByMe/database/init-database.php`
   - This will create all tables and insert demo data
   - You should see success messages for:
     - ✅ All tables created
     - ✅ Demo users created
     - ✅ Demo services created
     - ✅ Demo portfolio & certificates created

### Step 2: Verify Setup

Check that these directories exist and are writable:
- `uploads/profiles/`
- `uploads/portfolio/`
- `uploads/certificates/`
- `uploads/services/`

---

## 👤 Demo Credentials

### Admin Account
- **Email**: `admin@nearbyme.com`
- **Password**: `admin123`
- **Access**: Full platform control, manage all services and bookings

### Provider Account
- **Email**: `john.smith@example.com`
- **Password**: `provider123`
- **Access**: Create/manage services, view bookings, manage portfolio

### Customer Account
- **Email**: `alice@example.com`
- **Password**: `customer123`
- **Access**: Browse services, make bookings, view booking history

---

## 🧪 Testing Guide

### Test 1: Profile Edit with Image Upload (Provider)
1. Login as provider: `john.smith@example.com` / `provider123`
2. Click "✏️ Edit Profile" in navigation
3. **Try Drag-and-Drop**:
   - Drag an image file onto the upload zone
   - Watch real-time preview appear
   - Image uploads automatically
4. Fill in profile details:
   - Bio
   - Years of experience
   - Service areas (comma-separated)
   - Languages (comma-separated)
5. Click "💾 Save Changes"
6. Verify profile updated successfully

### Test 2: Portfolio Management (Provider)
1. While logged in as provider, click "📂 Portfolio"
2. **Add Portfolio Item**:
   - Click "➕ Add Portfolio Item"
   - Upload image via drag-and-drop
   - Fill title, category, description
   - Select project date
   - Click "💾 Add"
3. **Add Certificate**:
   - Click "➕ Add Certificate"
   - Optionally upload certificate file
   - Fill certificate details
   - Click "💾 Add"
4. **Delete Items**: Click "🗑️ Delete" on any item
5. Verify all operations work smoothly

### Test 3: Admin Services Management
1. Login as admin: `admin@nearbyme.com` / `admin123`
2. Click "🛠️ Services" in navigation
3. **View Statistics**: Check total, active, inactive services
4. **Search & Filter**:
   - Type in search box (searches services and providers)
   - Filter by category
   - Filter by status
5. **Toggle Status**: Click ⏸️ or ▶️ to activate/deactivate services
6. **Delete Service**: Click 🗑️ to remove (with confirmation)

### Test 4: Admin Bookings Management
1. While logged in as admin, click "📋 Bookings"
2. **View Statistics**: Check bookings by status and total revenue
3. **Search & Filter**:
   - Search by customer, provider, or service name
   - Filter by status
   - Filter by date
4. **Update Status**:
   - Click "✏️ Update" on any booking
   - Select new status from modal
   - Click "💾 Update Status"
5. **Delete Booking**: Click "🗑️ Delete" (with confirmation)

### Test 5: Customer Profile Edit
1. Login as customer: `alice@example.com` / `customer123`
2. Click "✏️ Edit Profile" button on dashboard
3. Update name and phone
4. Click "💾 Save Changes"
5. Verify changes reflected on dashboard

---

## 📁 File Structure

```
NearByMe/
├── api/
│   └── upload.php              # Secure file upload handler
├── assets/
│   └── css/
│       └── style.css           # Application styles
├── config/
│   ├── auth.php                # Authentication handling
│   └── database.php            # Database connection
├── database/
│   ├── init-database.php       # Database initialization
│   └── reset-database.sql      # Database reset script
├── uploads/                    # File upload directory
│   ├── profiles/               # Profile images
│   ├── portfolio/              # Portfolio images
│   ├── certificates/           # Certificates
│   └── services/               # Service images
├── admin-dashboard.php         # Admin main dashboard
├── admin-services.php          # Admin services management
├── admin-bookings.php          # Admin bookings management
├── customer-dashboard.php      # Customer dashboard
├── provider-dashboard.php      # Provider dashboard
├── edit-profile.php            # Profile edit for all users
├── manage-portfolio.php        # Portfolio management for providers
├── index.php                   # Home page
├── login.php                   # Login page
├── register.php                # Registration page
├── logout.php                  # Logout handler
└── README.md                   # This file
```

---

## 🎯 Key Features Summary

✅ **Profile Edit**: Easy UI with drag-and-drop image upload  
✅ **Portfolio Management**: Add/edit/delete portfolio items & certificates  
✅ **Admin Services**: Full CRUD with search & filters  
✅ **Admin Bookings**: Complete booking management with status updates  
✅ **File Uploads**: Secure API with validation & organization  
✅ **Demo Data**: Pre-seeded provider with portfolio & certificates  
✅ **Responsive UI**: Beautiful glass-morphism design  
✅ **Real-time Updates**: Instant feedback on all actions  
✅ **Search & Filters**: Powerful filtering on admin pages  
✅ **Statistics**: Comprehensive dashboards for all roles

---

## 🔧 Technology Stack

- **Backend**: PHP 8+ with MySQLi
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Database**: MySQL 8+
- **Server**: Apache (XAMPP)
- **Design**: Glass-morphism UI, Gradient backgrounds
- **Security**: Prepared statements, file validation, session management

---

## 📞 Support

For any issues or questions:
1. Check database is properly initialized
2. Verify XAMPP Apache and MySQL are running
3. Ensure `uploads/` directory has write permissions
4. Check browser console for JavaScript errors
5. Check PHP error logs at `C:\xampp\php\logs\php_error_log`

---

## 🎉 Success!

All requested features have been implemented:
- ✅ Profile edit with drag-and-drop image upload
- ✅ Provider portfolio management
- ✅ Provider certificates management  
- ✅ Seed demo data for providers
- ✅ Admin services management with full CRUD
- ✅ Admin bookings management with status updates

**Application is ready to test!** 🚀

Open in browser: `http://localhost/QuickServe/NearByMe/`
