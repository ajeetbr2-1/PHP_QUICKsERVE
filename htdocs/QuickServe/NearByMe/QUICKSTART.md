# 🚀 Quick Start Guide - QuickServe

## 5-Minute Setup Instructions

Follow these simple steps to get your **QuickServe** application running:

---

## ✅ Step 1: Install XAMPP (if not already installed)

1. Download XAMPP from: **https://www.apachefriends.org/**
2. Run the installer and install it (default location: `C:\xampp`)
3. Open **XAMPP Control Panel**
4. Click **Start** for both **Apache** and **MySQL**

---

## ✅ Step 2: Copy Project Files

1. Copy the entire `NearByMe` folder
2. Paste it into: **`C:\xampp\htdocs\`**
3. The final path should be: **`C:\xampp\htdocs\NearByMe\`**

---

## ✅ Step 3: Setup Database

1. Open your web browser
2. Go to: **`http://localhost/NearByMe/database/init-database.php`**
3. You will see messages confirming:
   - Database created ✅
   - All tables created ✅
   - Sample data inserted ✅
   - Demo credentials displayed ✅

**That's it! Your database is ready!**

---

## ✅ Step 4: Access the Application

Open your browser and go to:

```
http://localhost/NearByMe/
```

You should see the beautiful homepage with service listings!

---

## 👤 Login to Test

You can now login with these demo accounts:

### 🔐 Admin Login
```
Email: admin@nearbyme.com
Password: admin123
```

### 👔 Service Provider Login
```
Email: john.smith@example.com
Password: provider123
```

### 👤 Customer Login
```
Email: alice@example.com
Password: customer123
```

---

## 📱 What You Can Do Now

### As Customer:
1. Browse services on the homepage
2. Search for specific services or locations
3. Filter by category
4. View service details
5. Book services (mock booking - full implementation in dashboards)

### As Service Provider:
1. Login and access your dashboard
2. View your listed services
3. See incoming bookings

### As Admin:
1. Login to admin panel
2. View all users, services, and bookings
3. Access platform analytics

---

## ❗ Troubleshooting

### Problem: "localhost refused to connect"
**Solution**: Make sure Apache and MySQL are running in XAMPP Control Panel

### Problem: "Connection failed" error
**Solution**: Check if MySQL is running in XAMPP

### Problem: "Database doesn't exist"
**Solution**: Run the database setup script again:
`http://localhost/NearByMe/database/init-database.php`

### Problem: Page shows without styling
**Solution**: 
- Clear browser cache (Ctrl + Shift + Del)
- Check if `assets/css/style.css` file exists
- Make sure the path is correct: `C:\xampp\htdocs\NearByMe\`

---

## 🎯 Next Steps

1. **Explore the Homepage**: Browse services and use search
2. **Try Different Logins**: Test Customer, Provider, and Admin roles
3. **Customize**: Modify colors, add more categories, etc.
4. **Add Your Own Services**: Login as provider and create services
5. **Read Full Documentation**: Check `README.md` for complete details

---

## 📞 Need Help?

If you encounter any issues:
1. Check the troubleshooting section above
2. Read the complete `README.md` file
3. Verify all steps were followed correctly
4. Ensure XAMPP Apache and MySQL are running

---

## 🎉 Congratulations!

Your **QuickServe** service marketplace platform is now up and running!

Start exploring the features and customizing it to your needs.

---

**Project Team**: Ajeet Kumar, Abhishek Patel, Kundan Patil  
**Institution**: Parul Institute of Computer Applications

**Happy Coding! 🚀**
