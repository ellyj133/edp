#!/bin/bash
# Database Setup Script for E-Commerce Platform

echo "=== E-Commerce Platform Database Setup ==="

# Check if MySQL is running
if ! systemctl is-active --quiet mysql; then
    echo "Starting MySQL service..."
    sudo systemctl start mysql
    sleep 3
fi

# Create database and user
echo "Creating database and user..."
sudo mysql <<EOF
DROP DATABASE IF EXISTS ecommerce_platform;
CREATE DATABASE ecommerce_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

DROP USER IF EXISTS 'duns1'@'localhost';
CREATE USER 'duns1'@'localhost' IDENTIFIED BY 'Tumukunde';
GRANT ALL PRIVILEGES ON ecommerce_platform.* TO 'duns1'@'localhost';
FLUSH PRIVILEGES;

-- Create a minimal admin user table first
USE ecommerce_platform;
CREATE TABLE users (
    id int(11) NOT NULL AUTO_INCREMENT,
    username varchar(50) NOT NULL,
    email varchar(100) NOT NULL,
    pass_hash varchar(255) NOT NULL,
    first_name varchar(50) NOT NULL,
    last_name varchar(50) NOT NULL,
    role enum('customer','vendor','admin') NOT NULL DEFAULT 'customer',
    status enum('active','inactive','pending','suspended') NOT NULL DEFAULT 'active',
    verified_at timestamp NULL DEFAULT NULL,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id),
    UNIQUE KEY idx_email (email),
    UNIQUE KEY idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert admin user
INSERT INTO users (username, email, pass_hash, first_name, last_name, role, status, verified_at) 
VALUES ('admin', 'admin@test.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin', 'active', NOW());

EOF

# Test connection
echo "Testing database connection..."
if mysql -u duns1 -pTumukunde -e "USE ecommerce_platform; SELECT * FROM users WHERE role='admin';" > /dev/null 2>&1; then
    echo "✓ Database setup successful!"
    echo "✓ Admin user created with email: admin@test.com"
    echo "✓ You can now access the admin dashboard"
else
    echo "✗ Database setup failed"
    exit 1
fi

echo "=== Setup Complete ==="