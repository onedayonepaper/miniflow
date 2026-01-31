-- MiniFlow Database Initialization

-- Set character set and collation
ALTER DATABASE miniflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Grant privileges
GRANT ALL PRIVILEGES ON miniflow.* TO 'miniflow'@'%';
FLUSH PRIVILEGES;
