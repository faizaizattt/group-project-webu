-- DriveEase Car Rental System MySQL Schema & Seeds

CREATE DATABASE IF NOT EXISTS `driveease_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `driveease_db`;

-- 1. Users Table (Handles authentication credentials and roles)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Customers Table (Profile data linked to customer accounts)
CREATE TABLE IF NOT EXISTS `customers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `address` TEXT DEFAULT NULL,
  `join_date` DATE DEFAULT (CURRENT_DATE),
  `total_bookings` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Cars Table (Fleet Inventory)
CREATE TABLE IF NOT EXISTS `cars` (
  `id` VARCHAR(50) PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `brand` VARCHAR(100) NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `year` INT NOT NULL,
  `price_per_day` DECIMAL(10,2) NOT NULL,
  `seats` INT NOT NULL,
  `transmission` VARCHAR(50) NOT NULL,
  `fuel_type` VARCHAR(50) NOT NULL,
  `available` BOOLEAN DEFAULT TRUE,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `features` TEXT DEFAULT NULL, -- Stored as comma-separated or JSON array
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. Bookings Table (Reservations)
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` VARCHAR(50) PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `car_id` VARCHAR(50) NOT NULL,
  `pickup_date` DATE NOT NULL,
  `return_date` DATE NOT NULL,
  `total_price` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pending', 'confirmed', 'cancelled', 'active', 'completed') NOT NULL DEFAULT 'pending',
  `special_requests` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. Rentals Table (Ongoing/completed rentals processed from bookings)
CREATE TABLE IF NOT EXISTS `rentals` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` VARCHAR(50) NOT NULL UNIQUE,
  `pickup_date` DATE NOT NULL,
  `return_date` DATE NOT NULL,
  `status` ENUM('booked', 'ongoing', 'completed') NOT NULL DEFAULT 'booked',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Payments Table (Transaction logs)
CREATE TABLE IF NOT EXISTS `payments` (
  `id` VARCHAR(50) PRIMARY KEY,
  `booking_id` VARCHAR(50) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `method` VARCHAR(50) NOT NULL,
  `status` ENUM('paid', 'pending', 'flagged') NOT NULL DEFAULT 'pending',
  `date` DATE NOT NULL,
  `details` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. Feedback Table (Reviews)
CREATE TABLE IF NOT EXISTS `feedback` (
  `id` VARCHAR(50) PRIMARY KEY,
  `booking_id` VARCHAR(50) NOT NULL,
  `customer_id` INT NOT NULL,
  `stars` INT NOT NULL,
  `comment` TEXT NOT NULL,
  `date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;


-- ────────────────────────────────────────────────────────
--  SEEDING DEFAULT DATA
-- ────────────────────────────────────────────────────────

-- Seed Users (Bcrypt hashes: admin123 -> $2y$10$/tQ.qz.jkEweKL.HRUZ7Ye2JrjxDedc9bV36HvhqLbAdAq0VcH9PC, customer123 -> $2y$10$JNj1F27RI4pWYIhhS8wXQepPjSnHUY6LBn7BZGAEhguhlRpPuVSpS)
INSERT INTO `users` (`id`, `email`, `password`, `role`) VALUES
(1, 'admin@bluedrive.com', '$2y$10$/tQ.qz.jkEweKL.HRUZ7Ye2JrjxDedc9bV36HvhqLbAdAq0VcH9PC', 'admin'),
(2, 'customer@bluedrive.com', '$2y$10$JNj1F27RI4pWYIhhS8wXQepPjSnHUY6LBn7BZGAEhguhlRpPuVSpS', 'customer'),
(3, 'jane@example.com', '$2y$10$JNj1F27RI4pWYIhhS8wXQepPjSnHUY6LBn7BZGAEhguhlRpPuVSpS', 'customer'),
(4, 'michael@example.com', '$2y$10$JNj1F27RI4pWYIhhS8wXQepPjSnHUY6LBn7BZGAEhguhlRpPuVSpS', 'customer'),
(5, 'emily@example.com', '$2y$10$JNj1F27RI4pWYIhhS8wXQepPjSnHUY6LBn7BZGAEhguhlRpPuVSpS', 'customer'),
(6, 'robert@example.com', '$2y$10$JNj1F27RI4pWYIhhS8wXQepPjSnHUY6LBn7BZGAEhguhlRpPuVSpS', 'customer'),
(7, 'sarah@example.com', '$2y$10$JNj1F27RI4pWYIhhS8wXQepPjSnHUY6LBn7BZGAEhguhlRpPuVSpS', 'customer'),
(8, 'david@example.com', '$2y$10$JNj1F27RI4pWYIhhS8wXQepPjSnHUY6LBn7BZGAEhguhlRpPuVSpS', 'customer'),
(9, 'brody@example.com', '$2y$10$JNj1F27RI4pWYIhhS8wXQepPjSnHUY6LBn7BZGAEhguhlRpPuVSpS', 'customer');

-- Seed Customers
INSERT INTO `customers` (`id`, `user_id`, `name`, `phone`, `address`, `join_date`, `total_bookings`) VALUES
(1, 2, 'John Doe', '+1234567890', '123 Main St, New York', '2026-01-15', 3),
(2, 3, 'Jane Smith', '+1234567891', '456 Elm St, San Francisco', '2026-02-20', 1),
(3, 4, 'Mike Johnson', '+1234567892', '789 Maple Ave, Seattle', '2026-03-10', 2),
(4, 5, 'Emily Davis', '+63 918 555 0199', '202 Oak Rd, Boston', '2026-04-12', 1),
(5, 6, 'Robert Wilson', '+63 920 112 3344', '505 Pine Ln, Chicago', '2026-05-01', 1),
(6, 7, 'Sarah Jenkins', '+63 917 123 4567', '707 Birch Dr, Miami', '2026-05-10', 1),
(7, 8, 'David Vance', '+63 919 321 8888', '909 Cedar Rd, Dallas', '2026-05-15', 1),
(8, 9, 'Michael Brody', '+63 920 112 3344', '808 Spruce Ct, Denver', '2026-05-18', 1);

-- Seed Cars
INSERT INTO `cars` (`id`, `name`, `brand`, `category`, `year`, `price_per_day`, `seats`, `transmission`, `fuel_type`, `available`, `image_url`, `features`, `description`) VALUES
('CAR-001', 'Model 3 Long Range', 'Tesla', 'Electric', 2025, 89.00, 5, 'Automatic', 'Electric', 1, 'https://images.unsplash.com/photo-1560958089-b8a1929cea89?q=80&w=800&auto=format&fit=crop', 'Autopilot,Premium Audio,Heated Seats,Glass Roof,Supercharger Access', 'Experience the future of driving with this all-electric sedan. The Model 3 Long Range offers an impressive 358-mile range, lightning-fast acceleration, and the most advanced autopilot features available.'),
('CAR-002', '3 Series 330i', 'BMW', 'Sedan', 2025, 75.00, 5, 'Automatic', 'Petrol', 1, 'https://images.unsplash.com/photo-1555215695-3004980ad54e?q=80&w=800&auto=format&fit=crop', 'Sport Package,Leather Interior,Navigation,Parking Sensors,Apple CarPlay', 'The ultimate driving machine. This BMW 3 Series combines athletic agility with refined luxury, featuring a turbocharged engine that delivers exhilarating performance on every drive.'),
('CAR-003', 'Camry XSE', 'Toyota', 'Sedan', 2026, 52.00, 5, 'Automatic', 'Hybrid', 1, 'https://platform.cstatic-images.com/in/v2/stock_photos/d2d23f58-b525-4eb3-826c-eab83b8b616f/c763b801-5d75-492d-87c6-9cf50427ecd3.png', 'Hybrid Engine,Toyota Safety Sense,Wireless Charging,JBL Audio,Panoramic Roof', 'The most popular sedan just got better. The Camry XSE Hybrid delivers exceptional fuel economy without sacrificing driving excitement, wrapped in a stunning sport-tuned design.'),
('CAR-004', 'Explorer ST', 'Ford', 'SUV', 2025, 95.00, 7, 'Automatic', 'Petrol', 0, 'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?q=80&w=800&auto=format&fit=crop', '3rd Row Seating,B&O Sound,360° Camera,Adaptive Cruise,Tow Package', 'Command the road in this powerful 7-seat SUV. The Explorer ST features a twin-turbo V6 engine, sport-tuned suspension, and enough space for the whole family and all their gear.'),
('CAR-005', 'A4 Premium Plus', 'Audi', 'Luxury', 2025, 110.00, 5, 'Automatic', 'Petrol', 1, 'https://images.unsplash.com/photo-1606152421802-db97b9c7a11b?q=80&w=800&auto=format&fit=crop', 'Quattro AWD,Virtual Cockpit,Bang & Olufsen Audio,Matrix LED,Sport Seats', 'Sophistication meets performance. The Audi A4 Premium Plus features Quattro all-wheel drive, a stunning virtual cockpit display, and the finest interior craftsmanship in its class.'),
('CAR-006', 'Grand Cherokee L', 'Jeep', 'SUV', 2026, 105.00, 7, 'Automatic', 'Diesel', 1, 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?q=80&w=800&auto=format&fit=crop', '4x4 System,Air Suspension,McIntosh Audio,Night Vision,Head-Up Display', 'Legendary capability meets modern luxury. The Grand Cherokee L offers unmatched off-road prowess with three rows of premium comfort and the most advanced technology in its segment.'),
('CAR-008', 'Mustang GT', 'Ford', 'Sports', 2025, 120.00, 4, 'Manual', 'Petrol', 0, 'https://images.unsplash.com/photo-1584345604476-8ec5e12e42dd?q=80&w=800&auto=format&fit=crop', '5.0L V8,Performance Pack,MagneRide,Launch Control,Recaro Seats', 'An American icon reborn. The Mustang GT delivers pure muscle car thrills with its naturally aspirated 5.0L V8, producing 480 horsepower of unbridled excitement.');

-- Seed Bookings
INSERT INTO `bookings` (`id`, `customer_id`, `car_id`, `pickup_date`, `return_date`, `total_price`, `status`, `special_requests`) VALUES
('BKG-9281', 6, 'CAR-001', '2026-05-25', '2026-05-28', 267.00, 'completed', 'Need child seat installed'),
('BKG-7492', 4, 'CAR-005', '2026-05-30', '2026-06-02', 330.00, 'active', ''),
('BKG-6610', 7, 'CAR-002', '2026-05-22', '2026-05-26', 300.00, 'completed', 'Airport pickup required'),
('BKG-8831', 8, 'CAR-004', '2026-05-28', '2026-05-30', 190.00, 'completed', 'GPS navigation required');

-- Seed Rentals
INSERT INTO `rentals` (`id`, `booking_id`, `pickup_date`, `return_date`, `status`) VALUES
(1, 'BKG-9281', '2026-05-25', '2026-05-28', 'completed'),
(2, 'BKG-7492', '2026-05-30', '2026-06-02', 'ongoing'),
(3, 'BKG-6610', '2026-05-22', '2026-05-26', 'completed'),
(4, 'BKG-8831', '2026-05-28', '2026-05-30', 'completed');

-- Seed Payments
INSERT INTO `payments` (`id`, `booking_id`, `amount`, `method`, `status`, `date`, `details`) VALUES
('PAY-1001', 'BKG-9281', 320.00, 'Credit Card', 'paid', '2026-05-28', 'Visa ending in 8821'),
('PAY-1002', 'BKG-7492', 150.50, 'Bank Transfer', 'pending', '2026-05-30', 'Reference: BT-991203-CHK'),
('PAY-1003', 'BKG-6610', 540.00, 'Mobile Wallet', 'paid', '2026-05-25', 'G-Cash: +63 917 123 4567'),
('PAY-1004', 'BKG-8831', 180.00, 'Credit Card', 'flagged', '2026-05-29', 'Mastercard ending in 1054');

-- Seed Feedback
INSERT INTO `feedback` (`id`, `booking_id`, `customer_id`, `stars`, `comment`, `date`) VALUES
('FDB-201', 'BKG-9281', 6, 5, 'The Tesla Model 3 was absolutely clean and fully charged! The pick-up and drop-off process was extremely smooth. Will definitely rent again!', '2026-05-28'),
('FDB-202', 'BKG-6610', 7, 4, 'Great service. The vehicle (BMW 3 Series) performed flawlessly. Only issue was a small scratch on the door which was already documented. Clear invoicing!', '2026-05-26'),
('FDB-203', 'BKG-8831', 8, 2, 'The car interior smelled of smoke when I got it, although they cleaned it up, it was annoying. Customer support did offer a discount, which was nice.', '2026-05-30');
