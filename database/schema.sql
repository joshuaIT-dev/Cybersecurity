CREATE DATABASE IF NOT EXISTS plmun_cybersecurity CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE plmun_cybersecurity;

CREATE TABLE IF NOT EXISTS security_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  source_event_id VARCHAR(120) NULL,
  event_type VARCHAR(80) NOT NULL,
  severity ENUM('low','medium','high','critical') NOT NULL DEFAULT 'low',
  user_identifier VARCHAR(190) NULL,
  source_ip VARCHAR(45) NULL,
  user_agent VARCHAR(500) NULL,
  event_details JSON NULL,
  occurred_at DATETIME NOT NULL,
  received_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_events_occurred_at (occurred_at),
  INDEX idx_events_user_time (user_identifier, occurred_at),
  INDEX idx_events_type_time (event_type, occurred_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS incidents (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  event_id BIGINT UNSIGNED NULL,
  title VARCHAR(180) NOT NULL,
  status ENUM('open','investigating','resolved','closed') NOT NULL DEFAULT 'open',
  priority ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  assigned_to VARCHAR(190) NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_incidents_event FOREIGN KEY (event_id) REFERENCES security_events(id) ON DELETE SET NULL,
  INDEX idx_incidents_status_priority (status, priority)
) ENGINE=InnoDB;
