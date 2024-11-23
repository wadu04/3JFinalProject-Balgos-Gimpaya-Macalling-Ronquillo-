-- Switch to booking database
USE booking;

-- Drop tables if they exist (in correct order due to foreign key constraints)
DROP TABLE IF EXISTS booked_seats;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS schedules;
DROP TABLE IF EXISTS bus_classes;

-- Bus Classes Table
CREATE TABLE bus_classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    total_seats INT DEFAULT 60
);

-- Insert bus classes
INSERT INTO bus_classes (class_name, price) VALUES
('Ordinary', 800.00),
('Deluxe', 990.00),
('Super Deluxe', 1200.00);

-- Schedule Table
CREATE TABLE schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    departure_time TIME NOT NULL,
    bus_class_id INT,
    available_seats INT DEFAULT 60,
    FOREIGN KEY (bus_class_id) REFERENCES bus_classes(id)
);

-- Insert schedules
INSERT INTO schedules (departure_time, bus_class_id, available_seats) 
SELECT departure_time, bc.id, 60
FROM 
    (SELECT '17:00:00' AS departure_time UNION
     SELECT '18:00:00' UNION
     SELECT '19:00:00' UNION
     SELECT '20:00:00') AS times
CROSS JOIN bus_classes bc;

-- Bookings Table
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    schedule_id INT NOT NULL,
    bus_class_id INT NOT NULL,
    number_of_seats INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(20) NOT NULL,
    payment_status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (schedule_id) REFERENCES schedules(id),
    FOREIGN KEY (bus_class_id) REFERENCES bus_classes(id)
);

-- Booked Seats Table
CREATE TABLE booked_seats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    seat_number INT NOT NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);
