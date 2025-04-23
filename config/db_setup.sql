-- Create the database
CREATE DATABASE IF NOT EXISTS medook_db;
USE medook_db;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('patient', 'doctor', 'admin') NOT NULL DEFAULT 'patient',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create doctors table
CREATE TABLE IF NOT EXISTS doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    specialty VARCHAR(100) NOT NULL,
    bio TEXT,
    experience INT,
    profile_image VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create appointments table
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- Insert admin user
INSERT INTO users (username, password, email, full_name, role)
VALUES ('admin', '$2y$10$BEuZ9VZmfFKXMVw5TsOSS.JQk/FJhkFYqKf5v/p89pGkbIBqXojxy', 'admin@medook.com', 'System Administrator', 'admin');

-- Insert sample doctors (passwords are 'password')
INSERT INTO users (username, password, email, full_name, role)
VALUES 
('drsmith', '$2y$10$BEuZ9VZmfFKXMVw5TsOSS.JQk/FJhkFYqKf5v/p89pGkbIBqXojxy', 'drsmith@medook.com', 'Dr. John Smith', 'doctor'),
('drpatel', '$2y$10$BEuZ9VZmfFKXMVw5TsOSS.JQk/FJhkFYqKf5v/p89pGkbIBqXojxy', 'drpatel@medook.com', 'Dr. Priya Patel', 'doctor'),
('drwilliams', '$2y$10$BEuZ9VZmfFKXMVw5TsOSS.JQk/FJhkFYqKf5v/p89pGkbIBqXojxy', 'drwilliams@medook.com', 'Dr. Sarah Williams', 'doctor');

-- Insert doctor profiles
INSERT INTO doctors (user_id, specialty, bio, experience, profile_image)
VALUES 
(2, 'Cardiology', 'Specialized in treating heart conditions with over 15 years of experience.', 15, 'doctor1.jpg'),
(3, 'Pediatrics', 'Dedicated to providing quality healthcare for children from birth to adolescence.', 10, 'doctor2.jpg'),
(4, 'Dermatology', 'Expert in diagnosing and treating skin conditions for patients of all ages.', 8, 'doctor3.jpg'); 