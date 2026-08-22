-- Migration to add ATOM Personal Model tables

USE atom_assistant;

-- 1. Personal Profile preferences
CREATE TABLE IF NOT EXISTS atom_personal_profile (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NULL, -- NULL means global, or project specific
    preference_key VARCHAR(100) NOT NULL,
    preference_value TEXT NOT NULL,
    source VARCHAR(100) NOT NULL, -- 'explicit_user_request', 'user_correction', 'repeated_confirmed', etc.
    confidence FLOAT DEFAULT 1.0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_project_pref (project_id, preference_key),
    FOREIGN KEY (project_id) REFERENCES atom_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Training Examples
CREATE TABLE IF NOT EXISTS atom_training_examples (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(100) NULL,
    user_input TEXT NOT NULL,
    context_summary TEXT NULL,
    preferred_response TEXT NOT NULL,
    source VARCHAR(100) NOT NULL, -- 'user_approved', 'gemini_teacher', etc.
    quality VARCHAR(50) DEFAULT 'UNREVIEWED', -- 'UNREVIEWED', 'GOOD', 'CORRECTED', 'REJECTED', 'VERIFIED'
    verified TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Feedback logging
CREATE TABLE IF NOT EXISTS atom_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    feedback_type VARCHAR(50) NOT NULL, -- 'good', 'bad', 'correct', 'natural'
    feedback_text TEXT NULL,
    associated_message_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES atom_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Learning Topics
CREATE TABLE IF NOT EXISTS atom_learning_topics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topic VARCHAR(255) NOT NULL UNIQUE,
    level VARCHAR(50) DEFAULT 'beginner',
    practice_count INT DEFAULT 0,
    successful_count INT DEFAULT 0,
    last_practiced TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Learning Progress
CREATE TABLE IF NOT EXISTS atom_learning_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    section VARCHAR(255) NULL,
    page_start INT NOT NULL,
    page_end INT NOT NULL,
    status VARCHAR(50) DEFAULT 'NEW', -- 'NEW', 'PROCESSED', 'REVIEWED'
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (document_id) REFERENCES atom_documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
