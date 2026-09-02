<?php
/**
 * Chat Analytics API
 * Provides chat logs and analytics data
 */

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Check if tables exist, create if not
    ensureTablesExist($conn);
    
    $action = $_GET['action'] ?? 'logs';
    
    switch ($action) {
        case 'logs':
            echo json_encode(getChatLogs($conn));
            break;
            
        case 'stats':
            echo json_encode(getChatStats($conn));
            break;
            
        case 'popular':
            echo json_encode(getPopularQueries($conn));
            break;
            
        default:
            echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
    }
    
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

/**
 * Ensure required tables exist
 */
function ensureTablesExist($conn) {
    // Check if chat_logs table exists
    $result = $conn->query("SHOW TABLES LIKE 'chat_logs'");
    if ($result->num_rows === 0) {
        // Create tables from setup_chatbot_db.sql structure
        $sql = "CREATE TABLE IF NOT EXISTS chat_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(100) NOT NULL,
            role ENUM('user', 'assistant', 'system') NOT NULL,
            message TEXT NOT NULL,
            sources JSON DEFAULT NULL,
            response_time_ms INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_session (session_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->query($sql);
        
        $sql = "CREATE TABLE IF NOT EXISTS chat_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(100) UNIQUE NOT NULL,
            started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            message_count INT DEFAULT 0,
            INDEX idx_session (session_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->query($sql);
    }
}

/**
 * Get recent chat logs
 */
function getChatLogs($conn) {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    $query = "
        SELECT 
            id,
            session_id,
            role,
            message,
            sources,
            created_at
        FROM chat_logs
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = [
            'id' => $row['id'],
            'session_id' => $row['session_id'],
            'role' => $row['role'],
            'message' => $row['message'],
            'sources' => $row['sources'] ? json_decode($row['sources']) : null,
            'created_at' => $row['created_at']
        ];
    }
    
    // Get total count
    $countResult = $conn->query("SELECT COUNT(*) as total FROM chat_logs");
    $total = $countResult->fetch_assoc()['total'];
    
    return [
        'status' => 'success',
        'logs' => $logs,
        'total' => (int)$total,
        'limit' => $limit,
        'offset' => $offset
    ];
}

/**
 * Get chat statistics
 */
function getChatStats($conn) {
    // Total conversations
    $totalLogs = $conn->query("SELECT COUNT(*) as c FROM chat_logs")->fetch_assoc()['c'];
    $totalSessions = $conn->query("SELECT COUNT(*) as c FROM chat_sessions")->fetch_assoc()['c'];
    
    // User vs Assistant messages
    $userMsgs = $conn->query("SELECT COUNT(*) as c FROM chat_logs WHERE role='user'")->fetch_assoc()['c'];
    $assistantMsgs = $conn->query("SELECT COUNT(*) as c FROM chat_logs WHERE role='assistant'")->fetch_assoc()['c'];
    
    // Today's stats
    $todayLogs = $conn->query("SELECT COUNT(*) as c FROM chat_logs WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['c'];
    $todaySessions = $conn->query("SELECT COUNT(*) as c FROM chat_sessions WHERE DATE(started_at) = CURDATE()")->fetch_assoc()['c'];
    
    // This week's stats
    $weekLogs = $conn->query("SELECT COUNT(*) as c FROM chat_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['c'];
    
    // Average messages per session
    $avgMsgsPerSession = $totalSessions > 0 ? round($userMsgs / $totalSessions, 2) : 0;
    
    // Recent activity (last 7 days by day)
    $activityQuery = "
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as count
        FROM chat_logs
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ";
    $activityResult = $conn->query($activityQuery);
    $activity = [];
    while ($row = $activityResult->fetch_assoc()) {
        $activity[] = $row;
    }
    
    return [
        'status' => 'success',
        'stats' => [
            'total_messages' => (int)$totalLogs,
            'total_sessions' => (int)$totalSessions,
            'user_messages' => (int)$userMsgs,
            'assistant_messages' => (int)$assistantMsgs,
            'today_messages' => (int)$todayLogs,
            'today_sessions' => (int)$todaySessions,
            'week_messages' => (int)$weekLogs,
            'avg_messages_per_session' => $avgMsgsPerSession,
            'recent_activity' => $activity
        ]
    ];
}

/**
 * Get popular queries
 */
function getPopularQueries($conn) {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    $query = "
        SELECT 
            message,
            COUNT(*) as count
        FROM chat_logs
        WHERE role = 'user'
        GROUP BY message
        ORDER BY count DESC
        LIMIT ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $queries = [];
    while ($row = $result->fetch_assoc()) {
        $queries[] = [
            'query' => $row['message'],
            'count' => (int)$row['count']
        ];
    }
    
    return [
        'status' => 'success',
        'queries' => $queries
    ];
}
?>
