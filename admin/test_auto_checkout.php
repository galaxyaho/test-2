<?php
/**
 * Test Auto Checkout System
 * This file allows manual testing of the auto checkout system
 */

session_start();

// Check if user is admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

// Set timezone
date_default_timezone_set('Asia/Kolkata');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testing Auto Checkout System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .test-output {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            font-family: monospace;
            white-space: pre-wrap;
            max-height: 500px;
            overflow-y: auto;
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
        .warning { color: #ffc107; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">Testing Auto Checkout System</h1>
                <p class="text-muted">Current time: <?php echo date('Y-m-d H:i:s'); ?></p>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Test Results</h5>
                    </div>
                    <div class="card-body">
                        <div class="test-output" id="output">
<?php
// Include the auto checkout logic
require_once '../cron/auto_checkout_cron.php';

// Run the test
echo "Testing Auto Checkout System\n";
echo "Current time: " . date('Y-m-d H:i:s') . "\n\n";

echo "1. Testing Database Connection...\n";
try {
    $pdo->query("SELECT 1");
    echo "✅ Database connection successful!\n\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n\n";
}

echo "2. Checking Database Tables...\n";
$tables = ['rooms', 'bookings', 'auto_checkout_logs', 'system_settings'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch();
        echo "✅ Table '$table' exists with {$result['count']} records\n";
    } catch (Exception $e) {
        echo "❌ Table '$table' error: " . $e->getMessage() . "\n";
    }
}

echo "\n3. Checking Occupied Rooms...\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'occupied'");
    $result = $stmt->fetch();
    echo "ℹ️ Found {$result['count']} occupied rooms\n";
    
    if ($result['count'] == 0) {
        echo "ℹ️ No occupied rooms found. Creating a test booking...\n";
        
        // Create a test booking for today's checkout
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("INSERT INTO bookings (room_id, guest_name, guest_email, checkin_date, checkout_date, status, created_at) VALUES (?, ?, ?, ?, ?, 'occupied', NOW())");
        $stmt->execute([1, 'Test Guest', 'test@example.com', date('Y-m-d', strtotime('-1 day')), $today]);
        echo "✅ Test booking created for Room 1\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking occupied rooms: " . $e->getMessage() . "\n";
}

echo "\n4. Testing Auto Checkout System...\n";
try {
    $result = performAutoCheckout($pdo);
    
    echo "✅ Auto checkout test completed!\n";
    echo "Result: " . $result['status'] . "\n";
    
    if (isset($result['total_rooms'])) {
        echo "Rooms checked out: {$result['successful_checkouts']}/{$result['total_rooms']}\n";
        echo "Failed checkouts: {$result['failed_checkouts']}\n";
    }
    
    if (isset($result['details']) && !empty($result['details'])) {
        echo "\nDetails:\n";
        foreach ($result['details'] as $detail) {
            $status_icon = $detail['status'] == 'success' ? '✅' : '❌';
            echo "$status_icon Room {$detail['room_number']} (Booking {$detail['booking_id']}) - {$detail['status']}\n";
            if (isset($detail['error'])) {
                echo "   Error: {$detail['error']}\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Auto checkout test failed: " . $e->getMessage() . "\n";
}

echo "\n5. System Settings...\n";
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'auto_checkout%' OR setting_key = 'timezone'");
    $settings = $stmt->fetchAll();
    
    if (empty($settings)) {
        echo "⚠️ No auto checkout settings found. Creating default settings...\n";
        
        $default_settings = [
            'auto_checkout_time' => '10:00',
            'auto_checkout_enabled' => '1',
            'timezone' => 'Asia/Kolkata'
        ];
        
        foreach ($default_settings as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$key, $value]);
        }
        echo "✅ Default settings created\n";
    } else {
        foreach ($settings as $setting) {
            echo "ℹ️ {$setting['setting_key']}: {$setting['setting_value']}\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error checking system settings: " . $e->getMessage() . "\n";
}

echo "\n6. Manual Test Links:\n";
echo "📋 <a href='auto_checkout_settings.php' target='_blank'>Auto Checkout Settings</a>\n";
echo "📋 <a href='../cron/auto_checkout_cron.php' target='_blank'>Run Cron Script Directly</a>\n";

echo "\n✅ Test completed at " . date('Y-m-d H:i:s') . "\n";
?>
                        </div>
                        
                        <div class="mt-3">
                            <button class="btn btn-primary" onclick="location.reload()">
                                <i class="fas fa-refresh"></i> Run Test Again
                            </button>
                            <a href="auto_checkout_settings.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>