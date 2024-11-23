-- Create the booking database if it doesn't exist
CREATE DATABASE IF NOT EXISTS booking;

-- Switch to booking database
USE booking;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bus Classes Table
CREATE TABLE IF NOT EXISTS bus_classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    total_seats INT DEFAULT 60
);

-- Insert bus classes if not exists
INSERT INTO bus_classes (class_name, price) 
SELECT * FROM (
    SELECT 'Ordinary' as name, 800.00 as price UNION
    SELECT 'Deluxe', 990.00 UNION
    SELECT 'Super Deluxe', 1200.00
) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM bus_classes
);

-- Schedule Table
CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    departure_time TIME NOT NULL,
    bus_class_id INT,
    available_seats INT DEFAULT 60,
    FOREIGN KEY (bus_class_id) REFERENCES bus_classes(id)
);

-- Insert schedules if not exists
INSERT INTO schedules (departure_time, bus_class_id, available_seats)
SELECT t.departure_time, bc.id, 60
FROM 
    (SELECT '17:00:00' AS departure_time UNION
     SELECT '18:00:00' UNION
     SELECT '19:00:00' UNION
     SELECT '20:00:00') AS t
CROSS JOIN bus_classes bc
WHERE NOT EXISTS (
    SELECT 1 FROM schedules s 
    WHERE s.departure_time = t.departure_time 
    AND s.bus_class_id = bc.id
);

-- Bookings Table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    schedule_id INT NOT NULL,
    bus_class_id INT NOT NULL,
    number_of_seats INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(20) NOT NULL,
    payment_status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (schedule_id) REFERENCES schedules(id),
    FOREIGN KEY (bus_class_id) REFERENCES bus_classes(id)
);

-- Booked Seats Table
CREATE TABLE IF NOT EXISTS booked_seats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    seat_number INT NOT NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
