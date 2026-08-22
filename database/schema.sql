-- ATOM Personal AI Database Schema

CREATE DATABASE IF NOT EXISTS atom_assistant;
USE atom_assistant;

-- 1. Projects table to track workspaces
CREATE TABLE IF NOT EXISTS atom_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    path VARCHAR(500) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Sessions table to track conversation sessions per workspace
CREATE TABLE IF NOT EXISTS atom_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    session_uuid VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES atom_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Messages table for detailed chat history logging
CREATE TABLE IF NOT EXISTS atom_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    role VARCHAR(50) NOT NULL, -- 'user' or 'assistant'
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES atom_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Memories table for storing structured preferences and notes
CREATE TABLE IF NOT EXISTS atom_memories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NULL, -- NULL means global memory, otherwise project specific
    type VARCHAR(50) NOT NULL, -- 'preference', 'convention', 'architecture', etc.
    memory_key VARCHAR(100) NOT NULL,
    memory_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES atom_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Solutions table to track problems and confirmed solutions
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

-- 6. Settings table for storing key-value CLI preferences
CREATE TABLE IF NOT EXISTS atom_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Documents table for technical reference metadata
CREATE TABLE IF NOT EXISTS atom_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    path VARCHAR(500) NOT NULL UNIQUE,
    ai_summary TEXT NULL,
    trained_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Document chunks table for semantically split texts with FULLTEXT search index
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
