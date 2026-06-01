-- =============================================
-- Jade Sole Shoe Store - Database Schema
-- =============================================

CREATE DATABASE IF NOT EXISTS jade_sole;
USE jade_sole;

-- Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products Table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category_id INT,
    image VARCHAR(255) DEFAULT 'default.jpg',
    stock INT DEFAULT 10,
    is_available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Staff Table
CREATE TABLE IF NOT EXISTS staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact VARCHAR(20),
    email VARCHAR(150),
    username VARCHAR(80) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','staff') DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders Table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(20) UNIQUE NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    delivery_option ENUM('delivery','pickup') DEFAULT 'pickup',
    address TEXT,
    payment_method ENUM('cod','cash') DEFAULT 'cash',
    total_amount DECIMAL(10,2) NOT NULL,
    discount DECIMAL(10,2) DEFAULT 0,
    status ENUM('Received','Preparing','Ready for Pickup','Out for Delivery','Completed','Cancelled') DEFAULT 'Received',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Order Items Table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(20) NOT NULL,
    product_id INT,
    product_name VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
);

-- =============================================
-- Default Data
-- =============================================

-- Categories
INSERT INTO categories (name, description) VALUES
('Running Shoes', 'High-performance shoes built for speed and endurance'),
('Casual Sneakers', 'Everyday comfort meets effortless style'),
('Formal Shoes', 'Sophisticated footwear for every occasion'),
('Sandals & Slippers', 'Breezy, relaxed footwear for leisure'),
('Boots', 'Durable and stylish boots for any terrain');

-- Products
INSERT INTO products (name, description, price, category_id, stock) VALUES
-- Running Shoes
('SpeedFlex Pro', 'Lightweight mesh upper with responsive cushioning for max performance', 2999.00, 1, 10),
('TrailBlazer X', 'Rugged outsole with enhanced grip for off-road running', 3299.00, 1, 10),
('AirStride Elite', 'Carbon fiber plate for explosive energy return', 4500.00, 1, 10),
('PaceRunner Lite', 'Ultra-light design for competitive runners', 2499.00, 1, 10),

-- Casual Sneakers
('UrbanEdge Classic', 'Minimalist leather upper with vulcanized sole', 1799.00, 2, 10),
('SoftStep Canvas', 'Breathable canvas with memory foam insole', 1299.00, 2, 10),
('NeoWave Street', 'Bold chunky sole with retro colorway', 2199.00, 2, 10),
('CloudComfort Knit', 'Sock-fit knit upper with plush cushioning', 1999.00, 2, 10),

-- Formal Shoes
('Oxford Premier', 'Full-grain leather oxford with Goodyear welt construction', 3999.00, 3, 10),
('Derby Luxe', 'Smooth calfskin derby with leather sole', 3499.00, 3, 10),
('Loafer Signature', 'Penny loafer in suede with golden bit detail', 2999.00, 3, 10),

-- Sandals & Slippers
('Drift Slide', 'EVA foam slide with anatomical footbed', 699.00, 4, 10),
('Bali Strap', 'Woven leather strap sandal for beach or brunch', 1199.00, 4, 10),
('Terra Sport', 'Sport sandal with adjustable straps and traction sole', 999.00, 4, 10),

-- Boots
('Highland Chukka', 'Desert boot in suede with crepe sole', 2799.00, 5, 10),
('Storm Rider', 'Waterproof combat boot with lug sole', 3599.00, 5, 10),
('Chelsea Noir', 'Sleek Chelsea boot in full-grain leather', 3199.00, 5, 10);

-- Default Admin Account (password: admin123)
INSERT INTO staff (name, contact, email, username, password, role) VALUES
('Admin', '09701933534', 'admin@jadesole.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uHxL8GEZi', 'admin');
-- Note: password hash above is for 'admin123' using bcrypt

-- Default Staff Account (password: staff123)
INSERT INTO staff (name, contact, email, username, password, role) VALUES
('Staff User', '09000000000', 'staff@jadesole.com', 'staff', '$2y$10$TKh8H1.PXy3zvJHzMGPgT.bTqWo.pQE3cjfuQJq5dXm5p.iL6Hyyu', 'staff');
-- Note: password hash above is for 'staff123' using bcrypt
