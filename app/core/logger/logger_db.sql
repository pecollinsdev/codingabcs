-- Create the database (if it doesn't exist)
CREATE DATABASE IF NOT EXISTS logger_db;

-- Use the newly created database
USE logger_db;

-- Create the logs table to store log entries
CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level VARCHAR(20) NOT NULL,         -- Log level (e.g., ERROR, INFO, DEBUG)
    message TEXT NOT NULL,              -- Log message
    context JSON DEFAULT NULL,          -- Stores additional structured data (optional)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP -- Timestamp of the log entry
);

-- Index for faster retrieval of logs by level and date
CREATE INDEX idx_logs_level ON logs(level);
CREATE INDEX idx_logs_created_at ON logs(created_at);
