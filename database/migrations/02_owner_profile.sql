-- Migration to add ATOM Owner Profile and Biometric Settings tables

USE atom_assistant;

-- 1. Owner Profile table
CREATE TABLE IF NOT EXISTS atom_owner_profile (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL DEFAULT 'Vishnupriyan R',
    preferred_name VARCHAR(255) NULL,
    atom_display_name VARCHAR(255) NULL DEFAULT 'ATOM',
    profile_image VARCHAR(500) NULL, -- Local relative path
    preferred_language VARCHAR(100) NULL DEFAULT 'English',
    response_style VARCHAR(100) NULL DEFAULT 'concise',
    explanation_level VARCHAR(100) NULL DEFAULT 'intermediate',
    main_technologies TEXT NULL,
    main_use_cases TEXT NULL,
    timezone VARCHAR(100) NULL DEFAULT 'Asia/Kolkata',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Isolated Biometrics / Face Data (OPT-IN & DISABLED BY DEFAULT)
CREATE TABLE IF NOT EXISTS atom_biometric_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    face_data_enabled TINYINT DEFAULT 0,
    face_photo_path VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
