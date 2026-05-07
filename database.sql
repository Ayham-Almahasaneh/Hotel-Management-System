CREATE DATABASE IF NOT EXISTS ottawa_albus_hotel;
USE ottawa_albus_hotel;

DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    userID INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE rooms (
    roomID INT AUTO_INCREMENT PRIMARY KEY,
    roomName VARCHAR(150) NOT NULL,
    description VARCHAR(255) NOT NULL,
    price_per_night DECIMAL(10,2) NOT NULL,
    max_guests INT NOT NULL,
    stock INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE bookings (
    bookingID INT AUTO_INCREMENT PRIMARY KEY,
    userID INT NOT NULL,
    roomID INT NOT NULL,
    checkin_date DATE NOT NULL,
    checkout_date DATE NOT NULL,
    number_of_rooms INT NOT NULL DEFAULT 1,
    adults INT NOT NULL DEFAULT 1,
    children INT NOT NULL DEFAULT 0,
    special_requests TEXT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    booking_status ENUM('Confirmed', 'Cancelled') NOT NULL DEFAULT 'Confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_users FOREIGN KEY (userID) REFERENCES users(userID) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_rooms FOREIGN KEY (roomID) REFERENCES rooms(roomID) ON DELETE RESTRICT
);

INSERT INTO rooms (roomName, description, price_per_night, max_guests, stock, image_path) VALUES
('Standard Room (1 Bed)', 'Perfect for couples', 149.00, 2, 8, 'images/single.jpg'),
('Standard Room (2 Beds)', 'Ideal for families', 179.00, 4, 6, 'images/room2.jpg'),
('King Bed Suite Room', 'Spacious and luxurious', 249.00, 4, 4, 'images/room3.jpg');
