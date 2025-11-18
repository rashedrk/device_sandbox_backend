CREATE TABLE IF NOT EXISTS `devices` (
  `id` varchar(36) PRIMARY KEY DEFAULT (UUID()),
  `type` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `settings` json NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table structure for table `presets`
CREATE TABLE IF NOT EXISTS `presets` (
  `id` varchar(36) PRIMARY KEY DEFAULT (UUID()),
  `name` varchar(255) NOT NULL,
  `devices` json NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample data for devices
INSERT INTO `devices` (`type`, `name`, `settings`) VALUES
('light', 'Light', '{"power": false, "brightness": 70, "color": "#FFE5B4"}'),
('fan', 'Fan', '{"power": false, "speed": 64}');


-- Insert sample data for presets
INSERT INTO `presets` (`name`, `devices`) VALUES
('DimLight', '{"type": "light", "name": "Light", "settings": {"power": false, "brightness": 70, "color": "#FFE5B4"}}'),
('HyperFan', '{"type": "fan", "name": "Fan", "settings": {"power": false, "speed": 64}}');