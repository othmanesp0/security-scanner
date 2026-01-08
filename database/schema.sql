-- Security Scanner Dashboard Database Schema
-- Educational Project - Use only in controlled environments

CREATE DATABASE IF NOT EXISTS security_scanner;
USE security_scanner;

-- Users table for authentication and role management
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Scans table for tracking scan history and results
CREATE TABLE IF NOT EXISTS scans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    target_url VARCHAR(255) NOT NULL,
    tool VARCHAR(50) NOT NULL,
    result_file_path VARCHAR(255),
    status ENUM('running', 'completed', 'failed') DEFAULT 'running',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default admin user (username: admin, password: admin)
INSERT INTO users (username, password, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON DUPLICATE KEY UPDATE username = username;
