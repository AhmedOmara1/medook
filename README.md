# MedOok - Medical Appointment Booking System

MedOok is a comprehensive web application that allows patients to book appointments with doctors online. Built with PHP, MySQL, and modern frontend technologies.

## Features

- **User Authentication**: Secure registration and login system with role-based access control
- **Doctor Listings**: Browse doctors by specialty and view their profiles
- **Appointment Booking**: Schedule appointments with available doctors
- **Appointment Management**: View, cancel, and manage appointments
- **Admin Dashboard**: Comprehensive admin interface for system management
- **Doctor Dashboard**: Interface for doctors to manage their appointments
- **Modern UI**: Responsive design with animations and modern aesthetics

## Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- XAMPP, WAMP, MAMP, or any PHP development environment

### Setup Instructions

1. **Clone the repository to your web server directory**:
   ```
   git clone https://github.com/yourusername/medook.git
   ```
   Or download and extract the ZIP file to your web server directory (e.g., `htdocs` for XAMPP)

2. **Create the database**:
   - Open phpMyAdmin or any MySQL client
   - Create a new database named `medook_db`
   - Import the database schema from `config/db_setup.sql`

3. **Configure the application**:
   - Open `config/db_config.php`
   - Update the database connection details if needed:
     ```php
     $servername = "localhost"; // Your database server
     $username = "root";        // Your database username
     $password = "";            // Your database password
     $dbname = "medook_db";     // Your database name
     ```

4. **Access the application**:
   - Open your web browser and navigate to `http://localhost/medook/`
   - The application should load and redirect to the home page

## Default Accounts

After setting up the database, you can log in with the following default accounts:

### Admin
- **Username**: admin
- **Password**: password
- **Access**: Full system administration

### Doctor
- **Username**: drsmith
- **Password**: password
- **Access**: Doctor dashboard and appointment management

## Project Structure

```
medook/
├── assets/            # CSS, JS, images and other static assets
├── config/            # Configuration files and database setup
├── includes/          # Shared components (header, footer)
├── pages/             # Application pages
│   ├── admin/         # Admin dashboard and management
│   └── doctor/        # Doctor dashboard and appointment management
├── utils/             # Utility functions and helpers
├── index.php          # Application entry point
└── README.md          # This file
```

## Usage

1. **For Patients**:
   - Register a new account or log in
   - Browse doctors by specialty
   - View doctor profiles and book appointments
   - Manage your appointments (view, cancel, etc.)

2. **For Doctors**:
   - Log in with your doctor account
   - View and manage your appointments
   - Update your profile and availability

3. **For Administrators**:
   - Log in with the admin account
   - Manage users, doctors, and appointments
   - View system statistics and reports

## License

This project is licensed under the Ahmed Omara License - see the LICENSE file for details.

## Acknowledgments

- Bootstrap for the responsive UI components
- Font Awesome for the icons
- AOS for the animations
- Chart.js for the dashboard charts 