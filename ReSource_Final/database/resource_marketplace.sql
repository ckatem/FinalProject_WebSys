CREATE DATABASE IF NOT EXISTS resource_marketplace
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE resource_marketplace;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(80) NOT NULL,
    middle_name VARCHAR(80) NULL,
    last_name VARCHAR(80) NOT NULL,
    student_id VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    course VARCHAR(100) NOT NULL,
    campus VARCHAR(100) NOT NULL,
    role ENUM('student','admin') NOT NULL DEFAULT 'student',
    profile_photo VARCHAR(500) NULL,
    notify_messages TINYINT(1) NOT NULL DEFAULT 1,
    notify_listings TINYINT(1) NOT NULL DEFAULT 1,
    privacy_photo TINYINT(1) NOT NULL DEFAULT 1,
    privacy_course TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS listings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seller_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    course_code VARCHAR(80) NULL,
    department VARCHAR(100) NULL,
    category VARCHAR(80) NOT NULL,
    item_condition VARCHAR(80) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT NOT NULL,
    campus VARCHAR(100) NOT NULL,
    status ENUM('active','draft','reserved','sold','hidden') NOT NULL DEFAULT 'active',
    buyer_id INT UNSIGNED NULL,
    sold_at DATETIME NULL,
    sold_quantity INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_listings_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_listings_buyer FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_listings_status (status),
    INDEX idx_listings_seller (seller_id),
    INDEX idx_listings_created (created_at),
    INDEX idx_listings_course (course_code)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS listing_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    listing_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    sort_order TINYINT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_images_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    INDEX idx_images_listing (listing_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS favorites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    listing_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_favorite (user_id, listing_id),
    CONSTRAINT fk_fav_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_fav_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cart_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    listing_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    added_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cart_item (user_id, listing_id),
    CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED NOT NULL,
    listing_id INT UNSIGNED NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_msg_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_msg_receiver FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_msg_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE SET NULL,
    INDEX idx_msg_pair (sender_id, receiver_id),
    INDEX idx_msg_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS purchases (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT UNSIGNED NOT NULL,
    seller_id INT UNSIGNED NOT NULL,
    listing_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    image VARCHAR(500) NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    purchased_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_purchase_buyer FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_purchase_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_purchase_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    INDEX idx_purchase_buyer (buyer_id),
    INDEX idx_purchase_seller (seller_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT UNSIGNED NOT NULL,
    listing_id INT UNSIGNED NOT NULL,
    reason VARCHAR(100) NOT NULL,
    description TEXT NULL,
    status ENUM('pending','reviewed','resolved','dismissed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reporter FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_report_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    INDEX idx_report_status (status)
) ENGINE=InnoDB;

-- Default administrator account for local development.
-- Password: admin123 (stored as a password hash).
INSERT INTO users
    (first_name,last_name,student_id,email,password,course,campus,role)
    
VALUES
    ('Admin','User','ADMIN-001','admin@tip.edu.ph',
     '$2y$10$F/yr27sC8gUgwlyeCIvXc.eBHatF8o./YswleaQby4S49hTAFtmL2',
     'Administration','Manila','admin')
ON DUPLICATE KEY UPDATE
    first_name=VALUES(first_name),
    last_name=VALUES(last_name),
    password=VALUES(password),
    course=VALUES(course),
    campus=VALUES(campus),
    role='admin';
