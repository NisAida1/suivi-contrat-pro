CREATE DATABASE IF NOT EXISTS suivi_contrat_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE suivi_contrat_pro;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    role VARCHAR(30) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    student_number VARCHAR(50) NULL,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dossier_number VARCHAR(50) NOT NULL UNIQUE,
    student_user_id INT NOT NULL,
    company_name VARCHAR(200) NOT NULL,
    formation VARCHAR(200) NULL,
    academic_year VARCHAR(20) NULL,
    opco VARCHAR(200) NULL,
    is_eu_eea_swiss TINYINT(1) NOT NULL DEFAULT 0,
    status VARCHAR(30) NOT NULL DEFAULT 'BROUILLON',
    current_step VARCHAR(200) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    CONSTRAINT fk_contract_student FOREIGN KEY (student_user_id) REFERENCES users(id),
    INDEX idx_deleted_at (deleted_at)
);

CREATE TABLE IF NOT EXISTS contract_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contract_id INT NOT NULL,
    step_order INT NOT NULL DEFAULT 0,
    step_name VARCHAR(200) NOT NULL,
    state VARCHAR(20) NOT NULL DEFAULT 'pending',
    done_at DATETIME NULL,
    done_by_id INT NULL,
    note TEXT NULL,
    CONSTRAINT fk_step_contract FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    CONSTRAINT fk_step_user FOREIGN KEY (done_by_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contract_id INT NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(200) NOT NULL,
    details TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_contract FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token_expires (token, expires_at, used)
);
