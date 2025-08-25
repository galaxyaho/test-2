<?php
/**
 * Auto Checkout Cron Job
 * This file should be called by cron job daily
 */

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Include database configuration
require_once dirname(__DIR__) . '/config/database.php';

// Log file for debugging
$log_file = dirname(__DIR__) . '/logs/auto_checkout.log';

function writeLog($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message" . PHP_EOL;
    
    // Create logs directory if it doesn't exist
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
}

function performAutoCheckout($pdo) {
    try {
        writeLog("Starting auto checkout process...");
        
        // Get current date and time
        $current_date = date('Y-m-d');
        $current_time = date('H:i:s');
        $current_datetime = date('Y-m-d H:i:s');
        
        writeLog("Current date: $current_date, Current time: $current_time");
        
        // Get auto checkout settings
        $stmt = $pdo->prepare("SELECT * FROM system_settings WHERE setting_key IN ('auto_checkout_time', 'auto_checkout_enabled', 'timezone')");
        $stmt->execute();
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        // Check if auto checkout is enabled
        if (!isset($settings['auto_checkout_enabled']) || $settings['auto_checkout_enabled'] != '1') {
            writeLog("Auto checkout is disabled");
            return ['status' => 'disabled', 'message' => 'Auto checkout is disabled'];
        }
        
        $checkout_time = isset($settings['auto_checkout_time']) ? $settings['auto_checkout_time'] : '10:00';
        writeLog("Configured checkout time: $checkout_time");
        
        // Find rooms that need to be checked out
        $sql = "SELECT b.*, r.room_number, r.room_type 
                FROM bookings b 
                JOIN rooms r ON b.room_id = r.id 
                WHERE b.status = 'occupied' 
                AND b.checkout_date <= ? 
                AND (b.auto_checkout_processed IS NULL OR b.auto_checkout_processed = 0)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$current_date]);
        $rooms_to_checkout = $stmt->fetchAll();
        
        writeLog("Found " . count($rooms_to_checkout) . " rooms to checkout");
        
        $successful_checkouts = 0;
        $failed_checkouts = 0;
        $checkout_details = [];
        
        foreach ($rooms_to_checkout as $booking) {
            try {
                // Begin transaction
                $pdo->beginTransaction();
                
                // Update booking status
                $update_booking = $pdo->prepare("UPDATE bookings SET 
                    status = 'completed', 
                    actual_checkout_date = ?, 
                    actual_checkout_time = ?,
                    auto_checkout_processed = 1,
                    updated_at = ?
                    WHERE id = ?");
                
                $update_booking->execute([
                    $current_date,
                    $current_time,
                    $current_datetime,
                    $booking['id']
                ]);
                
                // Update room status to available
                $update_room = $pdo->prepare("UPDATE rooms SET status = 'available', updated_at = ? WHERE id = ?");
                $update_room->execute([$current_datetime, $booking['room_id']]);
                
                // Log the checkout activity
                $log_activity = $pdo->prepare("INSERT INTO auto_checkout_logs 
                    (booking_id, room_id, checkout_date, checkout_time, status, created_at) 
                    VALUES (?, ?, ?, ?, 'success', ?)");
                
                $log_activity->execute([
                    $booking['id'],
                    $booking['room_id'],
                    $current_date,
                    $current_time,
                    $current_datetime
                ]);
                
                // Log activity
                $activity_log = $pdo->prepare("INSERT INTO activity_logs 
                    (activity_type, description, created_at) 
                    VALUES ('auto_checkout', ?, ?)");
                
                $description = "Auto checkout completed for Room {$booking['room_number']} (Booking ID: {$booking['id']})";
                $activity_log->execute([$description, $current_datetime]);
                
                $pdo->commit();
                $successful_checkouts++;
                
                $checkout_details[] = [
                    'booking_id' => $booking['id'],
                    'room_number' => $booking['room_number'],
                    'room_type' => $booking['room_type'],
                    'status' => 'success'
                ];
                
                writeLog("Successfully checked out Room {$booking['room_number']} (Booking ID: {$booking['id']})");
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $failed_checkouts++;
                
                // Log failed checkout
                $log_failed = $pdo->prepare("INSERT INTO auto_checkout_logs 
                    (booking_id, room_id, checkout_date, checkout_time, status, error_message, created_at) 
                    VALUES (?, ?, ?, ?, 'failed', ?, ?)");
                
                $log_failed->execute([
                    $booking['id'],
                    $booking['room_id'],
                    $current_date,
                    $current_time,
                    $e->getMessage(),
                    $current_datetime
                ]);
                
                $checkout_details[] = [
                    'booking_id' => $booking['id'],
                    'room_number' => $booking['room_number'],
                    'room_type' => $booking['room_type'],
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
                
                writeLog("Failed to checkout Room {$booking['room_number']} (Booking ID: {$booking['id']}): " . $e->getMessage());
            }
        }
        
        // Update last run time
        $update_last_run = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, updated_at) 
            VALUES ('last_auto_checkout_run', ?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = ?");
        
        $update_last_run->execute([
            $current_datetime,
            $current_datetime,
            $current_datetime,
            $current_datetime
        ]);
        
        $result = [
            'status' => 'completed',
            'total_rooms' => count($rooms_to_checkout),
            'successful_checkouts' => $successful_checkouts,
            'failed_checkouts' => $failed_checkouts,
            'details' => $checkout_details,
            'run_time' => $current_datetime
        ];
        
        writeLog("Auto checkout process completed. Success: $successful_checkouts, Failed: $failed_checkouts");
        
        return $result;
        
    } catch (Exception $e) {
        writeLog("Auto checkout process failed: " . $e->getMessage());
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

// Main execution
try {
    $result = performAutoCheckout($pdo);
    
    // Output result for cron job logging
    echo "Auto Checkout Result: " . json_encode($result) . "\n";
    
    // Send email notification if configured
    // You can add email notification logic here
    
} catch (Exception $e) {
    writeLog("Critical error in auto checkout cron: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
}
?>