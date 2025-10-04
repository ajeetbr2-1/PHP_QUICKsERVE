-- Reset Database for Near By Me Application
-- This will drop and recreate all tables with fresh structure

USE nearbyme_db;

-- Drop all tables in correct order (respecting foreign keys)
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS certificates;
DROP TABLE IF EXISTS portfolio_items;
DROP TABLE IF EXISTS provider_profiles;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS users;

-- Now run the init-database.php file to recreate everything fresh!
