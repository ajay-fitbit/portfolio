-- Database setup for AI Chatbot

-- Chat logs table (already created in chat.php, but here for reference)
CREATE TABLE IF NOT EXISTS chat_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    role ENUM('user', 'assistant', 'system') NOT NULL,
    message TEXT NOT NULL,
    sources JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session (session_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Chat sessions table for tracking user sessions
CREATE TABLE IF NOT EXISTS chat_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL UNIQUE,
    user_ip VARCHAR(45),
    user_agent TEXT,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    message_count INT DEFAULT 0,
    INDEX idx_session (session_id),
    INDEX idx_started (started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Chat analytics table for tracking metrics
CREATE TABLE IF NOT EXISTS chat_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    query TEXT NOT NULL,
    response_time_ms INT,
    chunks_used INT,
    feedback ENUM('positive', 'negative', 'neutral') NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session (session_id),
    INDEX idx_created (created_at),
    FOREIGN KEY (session_id) REFERENCES chat_sessions(session_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Knowledge base status table
CREATE TABLE IF NOT EXISTS kb_ingestion_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_file VARCHAR(255) NOT NULL,
    chunks_count INT NOT NULL,
    status ENUM('success', 'failed') NOT NULL,
    error_message TEXT NULL,
    ingested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_source (source_file),
    INDEX idx_status (status),
    INDEX idx_ingested (ingested_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Popular queries table (for quick responses and optimization)
CREATE TABLE IF NOT EXISTS popular_queries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    query_text TEXT NOT NULL,
    query_hash VARCHAR(64) NOT NULL UNIQUE,
    count INT DEFAULT 1,
    avg_response_time_ms INT,
    last_asked TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_count (count),
    INDEX idx_hash (query_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User feedback table
CREATE TABLE IF NOT EXISTS chat_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    message_id INT NOT NULL,
    feedback_type ENUM('helpful', 'not_helpful', 'inaccurate', 'other') NOT NULL,
    comment TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session (session_id),
    INDEX idx_type (feedback_type),
    FOREIGN KEY (message_id) REFERENCES chat_logs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
