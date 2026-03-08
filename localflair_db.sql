CREATE DATABASE IF NOT EXISTS localflair_db;
USE localflair_db;

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    contact_number VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE addresses (
    address_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    street_address VARCHAR(255) NOT NULL,
    barangay VARCHAR(100),
    city_municipality VARCHAR(100) NOT NULL,
    province VARCHAR(100) NOT NULL,
    region VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'Philippines',
    FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
);

CREATE TABLE provinces (
    province_id INT AUTO_INCREMENT PRIMARY KEY,
    province_name VARCHAR(100) NOT NULL UNIQUE
);

INSERT INTO provinces (province_name)
VALUES
('Cebu'),
('Guimaras'),
('Benguet'),
('Davao'),
('Laguna');

CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE
);

INSERT INTO categories (category_name)
VALUES
('Food Delicacies'),
('Handcrafted Crafts'),
('Eco-Friendly Goods');

CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(150) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    price DECIMAL(10,2) NOT NULL,
    category_id INT NOT NULL,
    province_id INT NOT NULL,
    image VARCHAR(255),
    net_content VARCHAR(120) NULL,
    packaging VARCHAR(120) NULL,
    description TEXT NULL,
    status VARCHAR(30) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    FOREIGN KEY (province_id) REFERENCES provinces(province_id)
);

CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address_id INT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM(
        'Order Placed',
        'Processed',
        'Shipped',
        'Out for Delivery',
        'Delivered'
    ) DEFAULT 'Order Placed',
    payment_method VARCHAR(50) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (address_id) REFERENCES addresses(address_id)
);

CREATE TABLE order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    product_image VARCHAR(255),
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
        ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

CREATE TABLE order_tracking (
    tracking_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status ENUM(
        'Order Placed',
        'Processed',
        'Shipped',
        'Out for Delivery',
        'Delivered'
    ) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
        ON DELETE CASCADE
);

CREATE TABLE cart (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO admins (name, email, password)
VALUES (
    'Admin',
    'admin@gmail.com',
    '$2y$10$Dw.Ket28fPP89qehYk9xpO21f1CYnDzclLtjqkOSwn1WLZnKEo3WS'
);

INSERT INTO admins (name, email, password)
VALUES
('Inventory_Manager', 'inventorymanagement@gmail.com', '$2y$10$Dw.Ket28fPP89qehYk9xpO21f1CYnDzclLtjqkOSwn1WLZnKEo3WS'),
('Supplier_Manager', 'supplierstaff@gmail.com', '$2y$10$Dw.Ket28fPP89qehYk9xpO21f1CYnDzclLtjqkOSwn1WLZnKEo3WS'),
('Order_Manager', 'ordermanagementstaff@gmail.com', '$2y$10$Dw.Ket28fPP89qehYk9xpO21f1CYnDzclLtjqkOSwn1WLZnKEo3WS');


CREATE TABLE suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(150) NOT NULL,
    category_id INT NOT NULL,
    province_id INT NOT NULL,
    contact_number VARCHAR(20),
    rating DECIMAL(3,2) DEFAULT 0,
    deliveries INT DEFAULT 0,
    total_spend DECIMAL(12,2) DEFAULT 0,
    terms VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    FOREIGN KEY (province_id) REFERENCES provinces(province_id)
);
