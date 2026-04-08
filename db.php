<?php
// === DATABASE CONFIGURATION ===
// For XAMPP (local): use root / no password
// For production hosting: use the pgsucxjmhosting credentials
$host = 'localhost';
$db   = 'hotel_booking_db';
$user = 'root';
$pass = '';

// Uncomment below for production hosting:
// $db   = 'pgsucxjmhosting_hotel_booking_db';
// $user = 'pgsucxjmhosting_Thanh1311k6';
// $pass = 'Thanh1311@6';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db`");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('user', 'admin') DEFAULT 'user',
        email VARCHAR(100) DEFAULT NULL,
        phone VARCHAR(20) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS rooms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price INT NOT NULL,
        image_url VARCHAR(255) DEFAULT 'https://via.placeholder.com/300x200',
        status ENUM('available', 'booked') DEFAULT 'available',
        room_type ENUM('standard', 'medium', 'premium') DEFAULT 'standard',
        capacity INT DEFAULT 2,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        room_id INT NOT NULL,
        check_in_date DATE NOT NULL,
        check_out_date DATE NOT NULL,
        total_price INT NOT NULL,
        status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price INT NOT NULL,
        icon VARCHAR(50) DEFAULT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS booking_services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        service_id INT NOT NULL,
        quantity INT DEFAULT 1,
        price INT NOT NULL,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        room_id INT NOT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        amount INT NOT NULL,
        payment_method ENUM('cash', 'credit_card', 'bank_transfer') DEFAULT 'cash',
        payment_status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
        transaction_id VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS amenities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        icon VARCHAR(50) DEFAULT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS room_amenities (
        room_id INT NOT NULL,
        amenity_id INT NOT NULL,
        PRIMARY KEY (room_id, amenity_id),
        FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
        FOREIGN KEY (amenity_id) REFERENCES amenities(id) ON DELETE CASCADE
    )");

    // Auto create the specific admin account
    $adminUser = 'Thanh1311k6';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$adminUser]);
    if ($stmt->fetchColumn() == 0) {
        $adminPass = password_hash('13112006', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')")->execute([$adminUser, $adminPass]);
    }

    // Bỏ qua insert phòng mẫu

    $stmt = $pdo->query("SELECT COUNT(*) FROM services");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO services (name, description, price) VALUES 
        ('Bữa sáng', 'Bữa sáng buffet tại nhà hàng', 150000),
        ('Spa & Massage', 'Gói massage thư giãn 60 phút', 500000),
        ('Đưa đón sân bay', 'Xe đưa rước sân bay 4 chỗ', 300000)");
    }

    $stmt = $pdo->query("SELECT COUNT(*) FROM amenities");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO amenities (name) VALUES 
        ('Wifi miễn phí'), ('Điều hòa'), ('Tivi'), ('Tủ lạnh'), ('Bồn tắm')");
    }

} catch (PDOException $e) {
    die("Lỗi kết nối: " . $e->getMessage());
}
?>