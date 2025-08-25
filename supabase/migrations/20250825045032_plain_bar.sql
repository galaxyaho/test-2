-- Create auto_checkout_logs table if it doesn't exist
CREATE TABLE IF NOT EXISTS `auto_checkout_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `checkout_date` date NOT NULL,
  `checkout_time` time NOT NULL,
  `status` enum('success','failed') NOT NULL DEFAULT 'success',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `room_id` (`room_id`),
  KEY `checkout_date` (`checkout_date`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create auto_checkout_settings table if it doesn't exist
CREATE TABLE IF NOT EXISTS `auto_checkout_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO `auto_checkout_settings` (`setting_key`, `setting_value`) VALUES
('auto_checkout_enabled', '1'),
('auto_checkout_time', '10:00'),
('timezone', 'Asia/Kolkata')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- Update system_settings table structure if needed
ALTER TABLE `system_settings` 
ADD COLUMN IF NOT EXISTS `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add auto_checkout_processed column to bookings table if it doesn't exist
ALTER TABLE `bookings` 
ADD COLUMN IF NOT EXISTS `auto_checkout_processed` tinyint(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS `actual_checkout_date` date DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `actual_checkout_time` time DEFAULT NULL;

-- Create index for better performance
CREATE INDEX IF NOT EXISTS `idx_bookings_auto_checkout` ON `bookings` (`status`, `checkout_date`, `auto_checkout_processed`);
CREATE INDEX IF NOT EXISTS `idx_rooms_status` ON `rooms` (`status`);

-- Insert default system settings if they don't exist
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('auto_checkout_enabled', '1'),
('auto_checkout_time', '10:00'),
('timezone', 'Asia/Kolkata'),
('last_auto_checkout_run', '');