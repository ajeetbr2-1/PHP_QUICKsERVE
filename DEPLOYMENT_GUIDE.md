# 🚀 PHP_QUICKsERVE Deployment Guide

## 📋 Overview
This guide will help you deploy your PHP_QUICKsERVE project to Vercel with Supabase database - completely FREE!

## 🎯 What We'll Deploy
- **QuickServe**: Main marketplace application
- **NearByMe**: Local service provider platform
- **Database**: Supabase PostgreSQL (Free tier)
- **Hosting**: Vercel (Free tier)

---

## 🔧 Prerequisites
- GitHub account
- Vercel account (free)
- Supabase account (free)

---

## 📝 Step 1: Setup Supabase Database

### 1.1 Create Supabase Project
1. Go to [supabase.com](https://supabase.com)
2. Click "Start your project"
3. Sign up with GitHub
4. Click "New Project"
5. Choose organization and enter project details:
   - **Name**: `php-quickserve`
   - **Database Password**: Create a strong password (save it!)
   - **Region**: Choose closest to your users
6. Click "Create new project"

### 1.2 Get Database Credentials
1. Go to **Settings** → **Database**
2. Copy these details:
   - **Host**: `db.xxxxxxxxxxxxx.supabase.co`
   - **Database name**: `postgres`
   - **Port**: `5432`
   - **Username**: `postgres`
   - **Password**: (the one you created)

### 1.3 Initialize Database
1. Go to **SQL Editor** in Supabase dashboard
2. Click "New query"
3. Copy and paste the SQL from `database-schema.sql`
4. Click "Run" to create all tables

---

## 📝 Step 2: Setup Vercel

### 2.1 Install Vercel CLI
```bash
npm install -g vercel
```

### 2.2 Login to Vercel
```bash
vercel login
```

### 2.3 Deploy Project
```bash
cd PHP_QUICKsERVE
vercel
```

Follow the prompts:
- **Set up and deploy?** → Yes
- **Which scope?** → Your account
- **Link to existing project?** → No
- **What's your project's name?** → `php-quickserve`
- **In which directory is your code located?** → `./`

---

## 📝 Step 3: Configure Environment Variables

### 3.1 Add Environment Variables in Vercel
1. Go to your Vercel dashboard
2. Select your project
3. Go to **Settings** → **Environment Variables**
4. Add these variables:

```
DB_HOST = db.xxxxxxxxxxxxx.supabase.co
DB_NAME = postgres
DB_USER = postgres
DB_PASS = your_supabase_password
DB_PORT = 5432
```

### 3.2 Redeploy
After adding environment variables:
```bash
vercel --prod
```

---

## 📝 Step 4: Initialize Database

### 4.1 Run Database Initialization
1. Go to your deployed URL: `https://your-project.vercel.app/supabase-init.php`
2. This will create all tables and sample data
3. You should see: `{"status":"success","message":"Database initialized successfully..."}`

---

## 📝 Step 5: Test Your Application

### 5.1 Access Your Application
- **Main App**: `https://your-project.vercel.app/php-marketplace.html`
- **NearByMe**: `https://your-project.vercel.app/nearbyme/`

### 5.2 Test Login Credentials
**Admin Account:**
- Email: `admin@quickserve.com`
- Password: `admin123`

**Sample Provider:**
- Email: `john.plumber@email.com`
- Password: `password123`

**Sample Customer:**
- Email: `alice.customer@email.com`
- Password: `password123`

---

## 🔧 Troubleshooting

### Database Connection Issues
1. Check environment variables in Vercel dashboard
2. Verify Supabase database is running
3. Check database credentials

### API Not Working
1. Check Vercel function logs
2. Verify PHP files are in correct directories
3. Check CORS headers

### CSS/JS Not Loading
1. Check file paths in HTML
2. Verify static files are deployed
3. Clear browser cache

---

## 📊 Monitoring

### Vercel Analytics
- Go to Vercel dashboard → Analytics
- Monitor traffic, performance, and errors

### Supabase Monitoring
- Go to Supabase dashboard → Logs
- Monitor database queries and performance

---

## 🚀 Advanced Configuration

### Custom Domain
1. Go to Vercel dashboard → Domains
2. Add your custom domain
3. Update DNS settings

### SSL Certificate
- Automatically provided by Vercel
- HTTPS enabled by default

### Database Backups
- Supabase provides automatic backups
- Go to Settings → Database → Backups

---

## 💰 Cost Breakdown

| Service | Free Tier | What's Included |
|---------|-----------|-----------------|
| **Vercel** | ✅ Free | 100GB bandwidth, unlimited deployments |
| **Supabase** | ✅ Free | 500MB database, 2GB bandwidth |
| **Total** | **$0/month** | Perfect for small to medium projects |

---

## 🎉 You're Done!

Your PHP_QUICKsERVE application is now live and accessible worldwide!

**Live URL**: `https://your-project.vercel.app`

---

## 📞 Support

If you encounter any issues:
1. Check Vercel function logs
2. Check Supabase database logs
3. Verify all environment variables
4. Test locally first with XAMPP

---

## 🔄 Updates

To update your deployed application:
```bash
git add .
git commit -m "Update application"
git push origin main
vercel --prod
```

---

**Happy Deploying! 🚀**
