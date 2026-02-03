-- =====================================================
-- DROP + CREATE DATABASE
-- =====================================================
DROP DATABASE IF EXISTS sms_hub;

CREATE DATABASE sms_hub
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sms_hub;

-- =====================================================
-- API CLIENTS
-- =====================================================
CREATE TABLE api_clients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  api_key CHAR(64) NOT NULL UNIQUE,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  daily_limit INT NOT NULL DEFAULT 1000,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- CONTACTS
-- =====================================================
CREATE TABLE contacts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  phone VARCHAR(20) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_phone (phone)
) ENGINE=InnoDB;

-- =====================================================
-- GROUPS
-- =====================================================
CREATE TABLE groups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- GROUP MEMBERS
-- =====================================================
CREATE TABLE group_members (
  group_id INT NOT NULL,
  contact_id INT NOT NULL,
  PRIMARY KEY (group_id, contact_id),
  FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
  FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- SMS BATCHES
-- =====================================================
CREATE TABLE sms_batches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  api_client_id INT NOT NULL,
  message TEXT NOT NULL,
  total_recipients INT NOT NULL,
  status ENUM('queued','processing','done','error') NOT NULL DEFAULT 'queued',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (api_client_id) REFERENCES api_clients(id)
) ENGINE=InnoDB;

-- =====================================================
-- SMS QUEUE (LOGICZNA)
-- =====================================================
CREATE TABLE sms_queue (
  id INT AUTO_INCREMENT PRIMARY KEY,
  batch_id INT NOT NULL,
  phone VARCHAR(20) NOT NULL,
  status ENUM('queued','sent','error') NOT NULL DEFAULT 'queued',
  smsd_outbox_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (batch_id) REFERENCES sms_batches(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- SMS HISTORY (ŹRÓDŁO PRAWDY APLIKACJI)
-- =====================================================
CREATE TABLE sms_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  batch_id INT NOT NULL,
  phone VARCHAR(20) NOT NULL,
  message TEXT NOT NULL,
  parts INT NOT NULL,
  status ENUM('queued','sent','error') NOT NULL DEFAULT 'queued',
  smsd_outbox_id INT NULL,
  smsd_sentitem_id INT NULL,
  error_message VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  sent_at TIMESTAMP NULL,
  FOREIGN KEY (batch_id) REFERENCES sms_batches(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- DANE TESTOWE
-- =====================================================

-- API CLIENT
INSERT INTO api_clients (name, api_key, daily_limit)
VALUES (
  'test_app',
  SHA2('TEST_API_KEY', 256),
  100
);

-- CONTACT
INSERT INTO contacts (name, phone)
VALUES ('Test User', '539704495');

-- GROUP
INSERT INTO groups (name)
VALUES ('test_group');

-- GROUP MEMBER
INSERT INTO group_members (group_id, contact_id)
VALUES (
  (SELECT id FROM groups WHERE name='test_group' LIMIT 1),
  (SELECT id FROM contacts WHERE phone='539704495' LIMIT 1)
);

-- BATCH
INSERT INTO sms_batches (api_client_id, message, total_recipients)
VALUES (
  (SELECT id FROM api_clients WHERE name='test_app' LIMIT 1),
  'Testowa wiadomość SMS HUB',
  1
);

-- HISTORY ENTRY
INSERT INTO sms_messages (batch_id, phone, message, parts)
VALUES (
  (SELECT id FROM sms_batches ORDER BY id DESC LIMIT 1),
  '539704495',
  'Testowa wiadomość SMS HUB',
  1
);

-- =====================================================
-- DATABASE USER (OGRANICZONY DO 10.%)
-- =====================================================

DROP USER IF EXISTS 'sms_hub_app'@'%';
DROP USER IF EXISTS 'sms_hub_app'@'10.%';

CREATE USER 'sms_hub_app'@'10.%'
IDENTIFIED BY 'SmsHub#StrongPass2026';

GRANT
  SELECT, INSERT, UPDATE, DELETE
ON sms_hub.*
TO 'sms_hub_app'@'10.%';

GRANT
  SELECT, INSERT
ON smsd.outbox
TO 'sms_hub_app'@'10.%';

GRANT
  SELECT, INSERT
ON smsd.outbox_multipart
TO 'sms_hub_app'@'10.%';

GRANT
  SELECT
ON smsd.sentitems
TO 'sms_hub_app'@'10.%';

FLUSH PRIVILEGES;
