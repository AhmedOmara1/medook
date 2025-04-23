<?php
// Start output buffering to prevent header issues
ob_start();

require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../config/db_config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    // Properly redirect to the login page with absolute path
    header("Location: /medook/pages/login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
    $appointment_date = isset($_POST['appointment_date']) ? trim($_POST['appointment_date']) : '';
    $appointment_time = isset($_POST['appointment_time']) ? $_POST['appointment_time'] : '';
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    $patient_id = $_SESSION['user_id'];
    
    // For debugging - check what data is being submitted
    error_log("Processing appointment - Doctor ID: " . $doctor_id);
    error_log("Processing appointment - Appointment Date: " . $appointment_date);
    error_log("Processing appointment - Appointment Time: " . $appointment_time);
    error_log("Processing appointment - Patient ID: " . $patient_id);

    // Validate form data
    $errors = [];

    if (empty($doctor_id)) {
        $errors[] = "Doctor selection is required.";
    }

    if (empty($appointment_date)) {
        $errors[] = "Appointment date is required.";
    } else {
        // Validate date format and ensure it's in Y-m-d format
        try {
            // Ensure we have a properly formatted date
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointment_date)) {
                // Already in Y-m-d format
                $date = new DateTime($appointment_date);
            } else {
                // Try to parse the date
                $date = new DateTime($appointment_date);
            }
            
            // Force format to Y-m-d for database
            $appointment_date = $date->format('Y-m-d');
            
            // Ensure date is in the future
            $today = new DateTime();
            $today->setTime(0, 0, 0); // Reset time to beginning of day
            if ($date < $today) {
                $errors[] = "Appointment date must be in the future.";
            }
        } catch (Exception $e) {
            error_log("Date parsing error: " . $e->getMessage() . " for date: " . $appointment_date);
            $errors[] = "Invalid date format. Please use the date picker.";
        }
    }

    if (empty($appointment_time)) {
        $errors[] = "Appointment time is required.";
    }

    if (empty($reason)) {
        $errors[] = "Reason for appointment is required.";
    }

    // Check if doctor exists
    $doctorCheck = $conn->prepare("SELECT id FROM doctors WHERE id = ?");
    $doctorCheck->bind_param("i", $doctor_id);
    $doctorCheck->execute();
    $doctorResult = $doctorCheck->get_result();
    if ($doctorResult->num_rows === 0) {
        $errors[] = "Selected doctor does not exist.";
    }

    // Check for time slot availability
    $availabilityCheck = $conn->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'cancelled'");
    $availabilityCheck->bind_param("iss", $doctor_id, $appointment_date, $appointment_time);
    $availabilityCheck->execute();
    $availabilityResult = $availabilityCheck->get_result();
    if ($availabilityResult->num_rows > 0) {
        $errors[] = "The selected time slot is already booked. Please choose a different time.";
    }

    // If no errors, save the appointment
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("iisss", $patient_id, $doctor_id, $appointment_date, $appointment_time, $reason);
        
        if ($stmt->execute()) {
            // Get the appointment ID for reference
            $appointment_id = $conn->insert_id;
            
            // Create a one-time token for direct URL approach (bypassing sessions)
            $token = md5(uniqid($appointment_id . $patient_id, true));
            
            // Store the token in the database for validation
            $tokenStmt = $conn->prepare("UPDATE appointments SET token = ? WHERE id = ?");
            $tokenStmt->bind_param("si", $token, $appointment_id);
            $tokenStmt->execute();
            
            // Also still try the session and cookie approach as backup
            $_SESSION['appointment_success'] = true;
            setcookie('appointment_booked', 'true', time() + 300, '/');
            
            // Log success details
            error_log("Appointment successfully booked. ID: {$appointment_id}, Token: {$token}");
            
            // Make sure any session data is saved
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            
            // Use token in URL to ensure confirmation page can always validate the booking
            header("Location: /medook/pages/appointment_confirmation.php?appointment_id={$appointment_id}&token={$token}");
            exit();
        } else {
            error_log("Database error when booking appointment: " . $conn->error);
            $errors[] = "Failed to book appointment. Please try again. Error: " . $conn->error;
        }
    }

    // If there are errors, store them in session and redirect back
    if (!empty($errors)) {
        $_SESSION['appointment_errors'] = $errors;
        $_SESSION['appointment_form_data'] = [
            'doctor_id' => $doctor_id,
            'appointment_date' => $appointment_date,
            'appointment_time' => $appointment_time,
            'reason' => $reason
        ];
        
        error_log("Appointment booking errors: " . implode(", ", $errors));
        
        // Save session data before redirect
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        
        // Redirect back to the doctor profile page
        header("Location: /medook/pages/doctor_profile.php?id=" . $doctor_id);
        exit();
    }
} else {
    // If not POST request, redirect to doctors page
    header("Location: /medook/pages/doctors.php");
    exit();
}
?> 