-- Creates the test database alongside the main one.
-- Runs automatically on first MySQL container startup.
CREATE DATABASE IF NOT EXISTS `translation_service_test`;
GRANT ALL PRIVILEGES ON `translation_service_test`.* TO 'tms_user'@'%';
FLUSH PRIVILEGES;
