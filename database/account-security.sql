ALTER TABLE student_accounts ADD COLUMN IF NOT EXISTS email_verified_at DATETIME NULL;
CREATE TABLE IF NOT EXISTS student_sessions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, student_number VARCHAR(30) NOT NULL,
 token_hash CHAR(64) NOT NULL UNIQUE, source_ip VARCHAR(45) NOT NULL, user_agent VARCHAR(500) NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, last_seen DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 expires_at DATETIME NOT NULL, revoked_at DATETIME NULL,
 INDEX account_sessions(student_number,revoked_at,last_seen)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS email_verifications (
 student_number VARCHAR(30) PRIMARY KEY, email VARCHAR(254) NOT NULL,
 code_hash VARCHAR(255) NOT NULL, attempts INT NOT NULL DEFAULT 0,
 requested_at DATETIME NOT NULL, expires_at DATETIME NOT NULL
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS account_mail (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, student_number VARCHAR(30) NOT NULL,
 kind VARCHAR(40) NOT NULL, recipient VARCHAR(254) NOT NULL, subject VARCHAR(200) NOT NULL, body TEXT NOT NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'pending', attempts INT NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, next_attempt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 expires_at DATETIME NULL, sent_at DATETIME NULL, last_error VARCHAR(255) NULL,
 INDEX delivery_queue(status,next_attempt), INDEX account_history(student_number,created_at)
) ENGINE=InnoDB;
