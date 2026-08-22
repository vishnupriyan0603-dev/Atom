-- ================================================================================
-- ATOM SYSTEM COMPLETE DATABASE BACKUP & SCHEMA DUMP
-- ================================================================================
-- Generated Date : 2026-08-22
-- Target System  : ATOM AI — CodeIgniter 4 / MySQL / SQLite
-- ================================================================================

CREATE DATABASE IF NOT EXISTS atom_assistant;
USE atom_assistant;

-- 1. Projects table
CREATE TABLE IF NOT EXISTS atom_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    path VARCHAR(500) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Sessions table
CREATE TABLE IF NOT EXISTS atom_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    session_uuid VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES atom_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Messages table
CREATE TABLE IF NOT EXISTS atom_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    role VARCHAR(50) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES atom_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Memories table
CREATE TABLE IF NOT EXISTS atom_memories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL DEFAULT 1,
    memory_type VARCHAR(32) NOT NULL DEFAULT 'fact',
    topic VARCHAR(128) NOT NULL,
    content TEXT NOT NULL,
    source VARCHAR(64) NOT NULL DEFAULT 'conversation',
    confidence FLOAT NOT NULL DEFAULT 1.0,
    relevance_score FLOAT NOT NULL DEFAULT 1.0,
    access_count INT NOT NULL DEFAULT 0,
    last_accessed_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Solutions table
CREATE TABLE IF NOT EXISTS atom_solutions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NULL,
    problem_summary VARCHAR(255) NOT NULL,
    root_cause TEXT NOT NULL,
    solution_text TEXT NOT NULL,
    technology VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES atom_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Settings table
CREATE TABLE IF NOT EXISTS atom_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Documents table
CREATE TABLE IF NOT EXISTS atom_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    path VARCHAR(500) NOT NULL UNIQUE,
    ai_summary TEXT NULL,
    trained_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Document Chunks table
CREATE TABLE IF NOT EXISTS atom_document_chunks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    page_number INT NOT NULL,
    section_title VARCHAR(255) NULL,
    chunk_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES atom_documents(id) ON DELETE CASCADE,
    FULLTEXT KEY (chunk_text)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Knowledge Items table
CREATE TABLE IF NOT EXISTS atom_knowledge_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(64) NOT NULL DEFAULT 'general',
    content TEXT NOT NULL,
    source_uri TEXT NULL,
    embedding BLOB NULL,
    confidence_score FLOAT NOT NULL DEFAULT 0.90,
    version INT NOT NULL DEFAULT 1,
    checksum VARCHAR(64) NOT NULL DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX (category),
    INDEX (checksum)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Knowledge Triples table (Knowledge Graph)
CREATE TABLE IF NOT EXISTS atom_knowledge_triples (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(128) NOT NULL,
    predicate VARCHAR(64) NOT NULL,
    object VARCHAR(128) NOT NULL,
    confidence FLOAT NOT NULL DEFAULT 0.95,
    source_item_id INT UNSIGNED NULL,
    created_at DATETIME NULL,
    INDEX (subject),
    INDEX (predicate),
    INDEX (object)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Evaluations table (Performance Metrics)
CREATE TABLE IF NOT EXISTS atom_evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chat_id INT UNSIGNED NOT NULL DEFAULT 0,
    message_id INT UNSIGNED NOT NULL DEFAULT 0,
    prompt_version VARCHAR(32) NOT NULL DEFAULT 'v1.0',
    model_name VARCHAR(64) NOT NULL DEFAULT 'default',
    rag_retrieval_count INT NOT NULL DEFAULT 0,
    user_feedback VARCHAR(32) NULL,
    accuracy_score FLOAT NULL,
    latency_ms INT NOT NULL DEFAULT 0,
    tokens_used INT NOT NULL DEFAULT 0,
    created_at DATETIME NULL,
    INDEX (chat_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Experiments table (Self-Improvement Sandbox)
CREATE TABLE IF NOT EXISTS atom_experiments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(128) NOT NULL,
    target_component VARCHAR(64) NOT NULL,
    baseline_config TEXT NOT NULL,
    candidate_config TEXT NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'running',
    baseline_score FLOAT NOT NULL DEFAULT 0.0,
    candidate_score FLOAT NOT NULL DEFAULT 0.0,
    improvement_pct FLOAT NOT NULL DEFAULT 0.0,
    human_approved TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Human Approvals table (Safety Controls)
CREATE TABLE IF NOT EXISTS atom_human_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    experiment_id INT UNSIGNED NOT NULL,
    action VARCHAR(64) NOT NULL,
    requested_by VARCHAR(64) NOT NULL DEFAULT 'ATOM_SELF_IMPROVEMENT_ENGINE',
    approved_by VARCHAR(64) NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    reason TEXT NULL,
    created_at DATETIME NULL,
    resolved_at DATETIME NULL,
    INDEX (experiment_id),
    INDEX (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Web Chats table
CREATE TABLE IF NOT EXISTS chats (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    user_id INT UNSIGNED NULL,
    model VARCHAR(100) NULL,
    provider VARCHAR(50) NULL,
    is_pinned TINYINT(1) NOT NULL DEFAULT 0,
    folder_id INT UNSIGNED NULL,
    tags TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX (is_pinned),
    INDEX (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Web Messages table
CREATE TABLE IF NOT EXISTS messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chat_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    role VARCHAR(20) NOT NULL,
    content LONGTEXT NOT NULL,
    tokens_in INT NULL,
    tokens_out INT NULL,
    model VARCHAR(100) NULL,
    created_at DATETIME NULL,
    FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
    INDEX (chat_id),
    INDEX (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================================
-- INITIAL SEED DATA
-- ================================================================================

INSERT INTO atom_settings (setting_key, setting_value) VALUES 
('system_version', '2.0.0'),
('learning_engine_status', 'enabled'),
('human_approval_required', 'true')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
