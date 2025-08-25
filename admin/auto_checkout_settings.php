<?php
session_start();

// Check if user is admin (add your authentication logic here)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

// Handle form submission
if ($_POST) {
    try {
        $auto_checkout_enabled = isset($_POST['auto_checkout_enabled']) ? 1 : 0;
        $auto_checkout_time = $_POST['auto_checkout_time'] ?? '10:00';
        $timezone = $_POST['timezone'] ?? 'Asia/Kolkata';
        
        // Update settings
        $settings = [
            'auto_checkout_enabled' => $auto_checkout_enabled,
            'auto_checkout_time' => $auto_checkout_time,
            'timezone' => $timezone
        ];
        
        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, updated_at) 
                VALUES (?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
            $stmt->execute([$key, $value, $value]);
        }
        
        $success_message = "Auto checkout settings updated successfully!";
        
        // Update cron job if enabled
        if ($auto_checkout_enabled) {
            updateCronJob($auto_checkout_time);
        }
        
    } catch (Exception $e) {
        $error_message = "Error updating settings: " . $e->getMessage();
    }
}

// Get current settings
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('auto_checkout_enabled', 'auto_checkout_time', 'timezone', 'last_auto_checkout_run')");
$stmt->execute();
$current_settings = [];
while ($row = $stmt->fetch()) {
    $current_settings[$row['setting_key']] = $row['setting_value'];
}

// Default values
$auto_checkout_enabled = $current_settings['auto_checkout_enabled'] ?? 0;
$auto_checkout_time = $current_settings['auto_checkout_time'] ?? '10:00';
$timezone = $current_settings['timezone'] ?? 'Asia/Kolkata';
$last_run = $current_settings['last_auto_checkout_run'] ?? 'Never';

// Get recent auto checkout logs
$stmt = $pdo->prepare("SELECT acl.*, r.room_number, b.guest_name 
    FROM auto_checkout_logs acl 
    LEFT JOIN rooms r ON acl.room_id = r.id 
    LEFT JOIN bookings b ON acl.booking_id = b.id 
    ORDER BY acl.created_at DESC 
    LIMIT 10");
$stmt->execute();
$recent_logs = $stmt->fetchAll();

function updateCronJob($time) {
    // This function would update the cron job
    // You'll need to implement this based on your server setup
    $hour = date('H', strtotime($time));
    $minute = date('i', strtotime($time));
    
    // Example cron command (you'll need to adjust the path)
    $cron_command = "$minute $hour * * * /usr/bin/php /home/u261459251/domains/soft.galaxytribes.in/public_html/cron/auto_checkout_cron.php";
    
    // Log the cron update
    error_log("Cron job should be updated to: $cron_command");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto Checkout Settings - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .settings-card {
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .status-badge {
            font-size: 0.9em;
        }
        .log-table {
            font-size: 0.9em;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4"><i class="fas fa-clock"></i> Auto Checkout Settings</h1>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row">
            <!-- Settings Form -->
            <div class="col-lg-6">
                <div class="card settings-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-cog"></i> Configuration</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="auto_checkout_enabled" 
                                           name="auto_checkout_enabled" <?php echo $auto_checkout_enabled ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="auto_checkout_enabled">
                                        <strong>Enable Auto Checkout</strong>
                                    </label>
                                </div>
                                <small class="text-muted">Enable automatic checkout of rooms at specified time</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="auto_checkout_time" class="form-label">
                                    <i class="fas fa-clock"></i> Checkout Time
                                </label>
                                <input type="time" class="form-control" id="auto_checkout_time" 
                                       name="auto_checkout_time" value="<?php echo $auto_checkout_time; ?>" required>
                                <small class="text-muted">Time when auto checkout should run daily</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="timezone" class="form-label">
                                    <i class="fas fa-globe"></i> Timezone
                                </label>
                                <select class="form-select" id="timezone" name="timezone">
                                    <option value="Asia/Kolkata" <?php echo $timezone == 'Asia/Kolkata' ? 'selected' : ''; ?>>Asia/Kolkata (IST)</option>
                                    <option value="Asia/Dubai" <?php echo $timezone == 'Asia/Dubai' ? 'selected' : ''; ?>>Asia/Dubai (GST)</option>
                                    <option value="Europe/London" <?php echo $timezone == 'Europe/London' ? 'selected' : ''; ?>>Europe/London (GMT)</option>
                                    <option value="America/New_York" <?php echo $timezone == 'America/New_York' ? 'selected' : ''; ?>>America/New_York (EST)</option>
                                </select>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Settings
                                </button>
                                <button type="button" class="btn btn-success" onclick="testAutoCheckout()">
                                    <i class="fas fa-play"></i> Test Auto Checkout Now
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Status Information -->
            <div class="col-lg-6">
                <div class="card settings-card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Status Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="mb-3">
                                    <label class="form-label">Current Status:</label>
                                    <div>
                                        <?php if ($auto_checkout_enabled): ?>
                                            <span class="badge bg-success status-badge">
                                                <i class="fas fa-check"></i> Enabled
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger status-badge">
                                                <i class="fas fa-times"></i> Disabled
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-3">
                                    <label class="form-label">Scheduled Time:</label>
                                    <div class="fw-bold"><?php echo date('h:i A', strtotime($auto_checkout_time)); ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Last Run:</label>
                            <div class="fw-bold">
                                <?php 
                                if ($last_run != 'Never') {
                                    echo date('Y-m-d h:i A', strtotime($last_run));
                                } else {
                                    echo $last_run;
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Current Server Time:</label>
                            <div class="fw-bold" id="current-time"><?php echo date('Y-m-d h:i:s A'); ?></div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Important:</strong> Make sure your server's cron job is properly configured to run the auto checkout script.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Logs -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card settings-card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Recent Auto Checkout Logs</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_logs)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>No auto checkout logs found</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped log-table">
                                    <thead>
                                        <tr>
                                            <th>Date & Time</th>
                                            <th>Room</th>
                                            <th>Guest</th>
                                            <th>Status</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_logs as $log): ?>
                                            <tr>
                                                <td><?php echo date('Y-m-d h:i A', strtotime($log['created_at'])); ?></td>
                                                <td><?php echo $log['room_number'] ?? 'N/A'; ?></td>
                                                <td><?php echo $log['guest_name'] ?? 'N/A'; ?></td>
                                                <td>
                                                    <?php if ($log['status'] == 'success'): ?>
                                                        <span class="badge bg-success">Success</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Failed</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($log['error_message']): ?>
                                                        <small class="text-danger"><?php echo htmlspecialchars($log['error_message']); ?></small>
                                                    <?php else: ?>
                                                        <small class="text-success">Checkout completed successfully</small>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update current time every second
        function updateCurrentTime() {
            const now = new Date();
            const timeString = now.toLocaleString('en-US', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });
            document.getElementById('current-time').textContent = timeString;
        }
        
        setInterval(updateCurrentTime, 1000);
        
        // Test auto checkout function
        function testAutoCheckout() {
            if (confirm('Are you sure you want to run auto checkout now? This will process all pending checkouts.')) {
                window.open('test_auto_checkout.php', '_blank');
            }
        }
        
        // Auto-refresh page every 5 minutes to show updated logs
        setTimeout(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>