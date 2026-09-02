<?php
session_start();
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.php");
    exit();
}

include('../includes/config.php');

// Create content_categories table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS content_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating content_categories table: " . $conn->error);
}

$message = '';

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_main'])) {
    $intro_title = $_POST['intro_title'];
    $intro_text = $_POST['intro_text'];
    
    // Check if the main section exists
    $check_sql = "SELECT COUNT(*) as count FROM content_sections WHERE section_name = 'main'";
    $result = $conn->query($check_sql);
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        $sql = "UPDATE content_sections SET title = ?, content = ? WHERE section_name = 'main'";
    } else {
        $sql = "INSERT INTO content_sections (section_name, title, content) VALUES ('main', ?, ?)";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $intro_title, $intro_text);
    
    if ($stmt->execute()) {
        $message = '<div class="success">Main page content updated successfully!</div>';
    } else {
        $message = '<div class="error">Error updating main page content: ' . $conn->error . '</div>';
    }
    $stmt->close();
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle resume file upload
    if (isset($_POST['upload_resume'])) {
        $upload_dir = "../resume/";
        $file = $_FILES['resume_file'];
        
        // Sanitize the filename and add title to the filename
        $file_title = preg_replace("/[^a-zA-Z0-9-_\.]/", "", $_POST['resume_title']);
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_filename = $file_title . "." . $file_extension;
        $target_file = $upload_dir . $new_filename;
        
        // Check file type
        $allowed_types = ['pdf', 'doc', 'docx'];
        if (!in_array($file_extension, $allowed_types)) {
            $message = '<div class="error">Only PDF, DOC, and DOCX files are allowed.</div>';
        } else {
            // Upload file
            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                $message = '<div class="success">Resume file uploaded successfully!</div>';
            } else {
                $message = '<div class="error">Error uploading file.</div>';
            }
        }
    }

    // Handle file deletion
    if (isset($_POST['delete_file'])) {
        $filename = $_POST['delete_file'];
        $file_path = "../resume/" . $filename;
        
        // Verify file exists and is within the resume directory
        if (file_exists($file_path) && is_file($file_path) && dirname(realpath($file_path)) === realpath("../resume")) {
            if (unlink($file_path)) {
                $message = '<div class="success">File deleted successfully!</div>';
                // Refresh the page to update the file list
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $message = '<div class="error">Error deleting file.</div>';
            }
        } else {
            $message = '<div class="error">File not found.</div>';
        }
    }

    if (isset($_POST['update_about'])) {
        $title = $_POST['about_title'];
        $content = $_POST['about_content'];
        
        // First check if the about section exists
        $check_sql = "SELECT COUNT(*) as count FROM content_sections WHERE section_name = 'about'";
        $result = $conn->query($check_sql);
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            // Update existing record
            $sql = "UPDATE content_sections SET title = ?, content = ? WHERE section_name = 'about'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $title, $content);
        } else {
            // Insert new record
            $sql = "INSERT INTO content_sections (section_name, title, content) VALUES ('about', ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $title, $content);
        }
        
        if ($stmt->execute()) {
            $message = '<div class="success">About section updated successfully!</div>';
        } else {
            $message = '<div class="error">Error updating about section: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
    
    if (isset($_POST['add_skill'])) {
        $skill_name = $_POST['skill_name'];
        $category = $_POST['category'];
        $order = $_POST['display_order'];
        
        $sql = "INSERT INTO skills (skill_name, category, display_order) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $skill_name, $category, $order);
        
        if ($stmt->execute()) {
            $message = '<div class="success">Skill added successfully!</div>';
        } else {
            $message = '<div class="error">Error adding skill: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }

    if (isset($_POST['delete_skill'])) {
        $skill_id = $_POST['skill_id'];
        
        $sql = "DELETE FROM skills WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $skill_id);
        
        if ($stmt->execute()) {
            $message = '<div class="success">Skill deleted successfully!</div>';
            // Refresh the page to show updated skills list
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $message = '<div class="error">Error deleting skill: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
}

// Get current content
$about_sql = "SELECT * FROM content_sections WHERE section_name = 'about'";
$about_result = $conn->query($about_sql);
$about_content = $about_result->fetch_assoc();

$main_sql = "SELECT * FROM content_sections WHERE section_name = 'main'";
$main_result = $conn->query($main_sql);
$main_content = $main_result->fetch_assoc();

$skills_sql = "SELECT * FROM skills ORDER BY category, display_order";
$skills_result = $conn->query($skills_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Content - Portfolio Admin</title>
    <!-- Load API Configuration -->
    <script src="../api/config.php"></script>
    <!-- Include TinyMCE -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.7.0/tinymce.min.js"></script>
    <script>
        tinymce.init({
            selector: '#about_content, #intro_text',
            height: 500,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'charmap', 'preview',
                'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'table', 'help', 'wordcount'
            ],
            toolbar1: 'undo redo | styles | bold italic | alignleft aligncenter alignright alignjustify',
            toolbar2: 'bullist numlist outdent indent | link | forecolor backcolor | code | help',
            style_formats: [
                { title: 'Headings', items: [
                    { title: 'Heading 3', format: 'h3' },
                    { title: 'Heading 4', format: 'h4' }
                ]},
                { title: 'Inline', items: [
                    { title: 'Bold', format: 'bold' },
                    { title: 'Italic', format: 'italic' },
                    { title: 'Underline', format: 'underline' },
                    { title: 'Strikethrough', format: 'strikethrough' },
                    { title: 'Highlight', inline: 'mark' }
                ]},
                { title: 'Blocks', items: [
                    { title: 'Paragraph', format: 'p' },
                    { title: 'Blockquote', format: 'blockquote' }
                ]}
            ],
            content_style: `
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                    font-size: 16px;
                    line-height: 1.6;
                }
                h3 { 
                    color: #2c3e50;
                    font-size: 1.5em;
                    margin-top: 1.5em;
                }
                h4 {
                    color: #34495e;
                    font-size: 1.2em;
                }
                p { margin: 1em 0; }
                mark {
                    background-color: #ffeaa7;
                    padding: 2px 4px;
                }
            `,
            browser_spellcheck: true,
            contextmenu: false,
            branding: false,
            promotion: false
        });
    </script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }

        .setup-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .nav {
            background-color: #2c3e50;
            padding: 15px 20px;
            margin-bottom: 30px;
            border-radius: 8px;
            display: flex;
            gap: 20px;
        }

        .nav a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .nav a:hover {
            background-color: #34495e;
        }

        .content-section {
            background: white;
            margin-bottom: 30px;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
        }

        .form-group input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 2px rgba(52,152,219,0.2);
        }

        .skills-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 25px;
        }

        .skill-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .skill-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .btn-submit {
            background-color: #2ecc71;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .btn-submit:hover {
            background-color: #27ae60;
        }

        .btn-delete {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-delete:hover {
            background-color: #c0392b;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        h1, h2 {
            color: #2c3e50;
            margin-bottom: 25px;
        }

        h1 {
            font-size: 2.5em;
            font-weight: 600;
        }

        h2 {
            font-size: 1.8em;
            font-weight: 500;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        /* Category badges in skills grid */
        .skill-category {
            font-size: 12px;
            background-color: #3498db;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            margin-left: 8px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .setup-container {
                padding: 10px;
            }

            .content-section {
                padding: 15px;
            }

            .skills-grid {
                grid-template-columns: 1fr;
            }

            .nav {
                flex-direction: column;
                gap: 10px;
            }

            .nav a {
                display: block;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="nav">
            <a href="index.php">Dashboard</a>
            <a href="projects.php">Manage Projects</a>
            <a href="resume_admin.php">Manage Resume</a>
            <a href="../index.php">View Site</a>
            <a href="logout.php">Logout</a>
        </div>

        <h1>Manage Content</h1>
        
        <?php echo $message; ?>

        <!-- Main Page Content -->
        <div class="content-section">
            <h2>Edit Main Page Content</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="intro_title">Introduction Title</label>
                    <input type="text" id="intro_title" name="intro_title" 
                           value="<?php echo isset($main_content['title']) ? htmlspecialchars($main_content['title']) : 'Hello! I\'m Ajay Singh'; ?>" required>
                </div>
                <div class="form-group">
                    <label for="intro_text">Introduction Text</label>
                    <textarea id="intro_text" name="intro_text" rows="10" required><?php 
                        echo isset($main_content['content']) ? htmlspecialchars($main_content['content']) : ''; 
                    ?></textarea>
                </div>
                <button type="submit" name="update_main" class="btn-submit">Update Main Page</button>
            </form>
        </div>

        <!-- About Section -->
        <div class="content-section">
            <h2>Edit About Section</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="about_title">Title</label>
                    <input type="text" id="about_title" name="about_title" 
                           value="<?php echo isset($about_content['title']) ? htmlspecialchars($about_content['title']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="about_content">Content</label>
                    <textarea id="about_content" name="about_content" rows="10" required><?php 
                        echo isset($about_content['content']) ? htmlspecialchars($about_content['content']) : ''; 
                    ?></textarea>
                </div>
                <button type="submit" name="update_about" class="btn-submit">Update About Section</button>
            </form>
        </div>

        <!-- Resume Upload Section -->
        <div class="content-section">
            <h2>Manage Resume Files</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="resume_file">Upload Resume File</label>
                    <input type="file" id="resume_file" name="resume_file" accept=".pdf,.doc,.docx" required>
                </div>
                <div class="form-group">
                    <label for="resume_title">File Title (for display)</label>
                    <input type="text" id="resume_title" name="resume_title" placeholder="e.g., SQL Developer Resume" required>
                </div>
                <button type="submit" name="upload_resume" class="btn-submit">Upload Resume</button>
            </form>

            <div class="uploaded-files" style="margin-top: 20px;">
                <h3>Uploaded Resume Files</h3>
                <?php
                $resume_dir = "../resume/";
                if (is_dir($resume_dir)) {
                    $files = scandir($resume_dir);
                    foreach ($files as $file) {
                        if ($file != "." && $file != "..") {
                            echo '<div class="file-item" style="display: flex; justify-content: space-between; align-items: center; padding: 10px; margin: 5px 0; background: #f8f9fa; border-radius: 4px;">';
                            echo '<span>' . htmlspecialchars($file) . '</span>';
                            echo '<form method="POST" style="display: inline;">';
                            echo '<input type="hidden" name="delete_file" value="' . htmlspecialchars($file) . '">';
                            echo '<button type="submit" class="btn-delete" title="Delete file">×</button>';
                            echo '</form>';
                            echo '</div>';
                        }
                    }
                }
                ?>
            </div>
        </div>

        <!-- AI Chatbot Management Section -->
        <div class="content-section">
            <h2>AI Chatbot Management</h2>
            
            <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <h4 style="margin: 0 0 10px 0; color: #1976d2;">📊 Knowledge Base Status</h4>
                <div id="kb-status">Loading...</div>
            </div>
            
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <h4 style="margin: 0 0 15px 0; color: #1976d2;">📈 Chat Analytics</h4>
                <div id="chat-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                    <div style="background: white; padding: 15px; border-radius: 6px; text-align: center;">
                        <div style="font-size: 24px; font-weight: bold; color: #2196f3;" id="stat-total-msgs">-</div>
                        <div style="font-size: 12px; color: #666;">Total Messages</div>
                    </div>
                    <div style="background: white; padding: 15px; border-radius: 6px; text-align: center;">
                        <div style="font-size: 24px; font-weight: bold; color: #4caf50;" id="stat-sessions">-</div>
                        <div style="font-size: 12px; color: #666;">Sessions</div>
                    </div>
                    <div style="background: white; padding: 15px; border-radius: 6px; text-align: center;">
                        <div style="font-size: 24px; font-weight: bold; color: #ff9800;" id="stat-today">-</div>
                        <div style="font-size: 12px; color: #666;">Today's Messages</div>
                    </div>
                    <div style="background: white; padding: 15px; border-radius: 6px; text-align: center;">
                        <div style="font-size: 24px; font-weight: bold; color: #9c27b0;" id="stat-avg">-</div>
                        <div style="font-size: 12px; color: #666;">Avg/Session</div>
                    </div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <button onclick="ingestResumes()" class="btn-submit" style="padding: 15px;">
                    📥 Ingest All Resume Files
                </button>
                <button onclick="resetKnowledgeBase()" class="btn-delete" style="padding: 15px;">
                    🗑️ Reset Knowledge Base
                </button>
                <button onclick="viewChatLogs()" class="btn-submit" style="padding: 15px; background-color: #3498db;">
                    📋 View Chat Logs
                </button>
            </div>
            
            <div id="chatbot-message" style="margin-top: 20px;"></div>
            
            <div id="chat-logs" style="display: none; margin-top: 20px;">
                <h3>Recent Chat Conversations</h3>
                <div id="chat-logs-content" style="max-height: 400px; overflow-y: auto;"></div>
            </div>
            
            <script>
                // Load knowledge base status and analytics on page load
                document.addEventListener('DOMContentLoaded', function() {
                    loadKBStatus();
                    loadChatStats();
                });
                
                function loadChatStats() {
                    const apiBasePath = window.PORTFOLIO_CONFIG?.apiBasePath || '/portfolio';
                    fetch(`${apiBasePath}/api/analytics.php?action=stats`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                document.getElementById('stat-total-msgs').textContent = data.stats.total_messages;
                                document.getElementById('stat-sessions').textContent = data.stats.total_sessions;
                                document.getElementById('stat-today').textContent = data.stats.today_messages;
                                document.getElementById('stat-avg').textContent = data.stats.avg_messages_per_session;
                            }
                        })
                        .catch(error => {
                            console.error('Error loading stats:', error);
                        });
                }
                
                function loadKBStatus() {
                    const apiBasePath = window.PORTFOLIO_CONFIG?.apiBasePath || '/portfolio';
                    fetch(`${apiBasePath}/api/ingest.php?action=status`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                document.getElementById('kb-status').innerHTML = `
                                    <p style="margin: 5px 0;"><strong>Vector Store:</strong> ${data.collection}</p>
                                    <p style="margin: 5px 0;"><strong>Files:</strong> ${data.document_count}</p>
                                    <p style="margin: 5px 0; color: ${data.document_count > 0 ? '#27ae60' : '#e74c3c'};">
                                        <strong>Status:</strong> ${data.document_count > 0 ? '✓ Ready' : '⚠ Empty - Please upload resume files'}
                                    </p>
                                    ${data.vector_store_id ? `<p style="margin: 5px 0; font-size: 11px; color: #666;"><strong>Store ID:</strong> ${data.vector_store_id}</p>` : ''}
                                `;
                            } else {
                                document.getElementById('kb-status').innerHTML = `
                                    <p style="color: #e74c3c;">⚠ ${data.message}</p>
                                    <p style="font-size: 12px; margin-top: 10px;">Check OpenAI Vector Store configuration in .env file</p>
                                `;
                            }
                        })
                        .catch(error => {
                            document.getElementById('kb-status').innerHTML = `
                                <p style="color: #e74c3c;">⚠ Error connecting to OpenAI Vector Store</p>
                                <p style="font-size: 12px; margin-top: 10px;">Check your OpenAI API configuration</p>
                            `;
                        });
                }
                
                function ingestResumes() {
                    if (!confirm('This will process all resume files and add them to the knowledge base. Continue?')) {
                        return;
                    }
                    
                    showMessage('Processing resume files... This may take a few minutes.', 'info');
                    
                    const apiBasePath = window.PORTFOLIO_CONFIG?.apiBasePath || '/portfolio';
                    fetch(`${apiBasePath}/api/ingest.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'action=ingest_all'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showMessage(`✓ Successfully ingested ${data.chunks_added} chunks!`, 'success');
                            loadKBStatus();
                        } else {
                            showMessage(`✗ Error: ${data.message}`, 'error');
                        }
                    })
                    .catch(error => {
                        showMessage(`✗ Error: ${error.message}`, 'error');
                    });
                }
                
                function resetKnowledgeBase() {
                    if (!confirm('WARNING: This will delete all knowledge base data. Are you sure?')) {
                        return;
                    }
                    
                    const apiBasePath = window.PORTFOLIO_CONFIG?.apiBasePath || '/portfolio';
                    fetch(`${apiBasePath}/api/ingest.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'action=reset'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showMessage('✓ Knowledge base reset successfully!', 'success');
                            loadKBStatus();
                        } else {
                            showMessage(`✗ Error: ${data.message}`, 'error');
                        }
                    })
                    .catch(error => {
                        showMessage(`✗ Error: ${error.message}`, 'error');
                    });
                }
                
                function viewChatLogs() {
                    const logsDiv = document.getElementById('chat-logs');
                    const logsContent = document.getElementById('chat-logs-content');
                    
                    if (logsDiv.style.display === 'none') {
                        logsDiv.style.display = 'block';
                        loadChatLogs();
                    } else {
                        logsDiv.style.display = 'none';
                    }
                }
                
                function loadChatLogs() {
                    const logsContent = document.getElementById('chat-logs-content');
                    logsContent.innerHTML = '<p>Loading chat logs...</p>';
                    
                    // Fetch real chat logs from API
                    const apiBasePath = window.PORTFOLIO_CONFIG?.apiBasePath || '/portfolio';
                    fetch(`${apiBasePath}/api/analytics.php?action=logs&limit=20`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success' && data.logs.length > 0) {
                                let html = `
                                    <div style="margin-bottom: 15px; padding: 10px; background: #f0f0f0; border-radius: 4px;">
                                        <strong>Total Conversations:</strong> ${data.total} messages
                                    </div>
                                `;
                                
                                data.logs.forEach(log => {
                                    const isUser = log.role === 'user';
                                    const bgColor = isUser ? '#e3f2fd' : '#f5f5f5';
                                    const icon = isUser ? '👤' : '🤖';
                                    const time = new Date(log.created_at).toLocaleString();
                                    
                                    html += `
                                        <div style="margin-bottom: 15px; padding: 12px; background: ${bgColor}; border-radius: 8px; border-left: 4px solid ${isUser ? '#2196f3' : '#4caf50'};">
                                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                                <strong>${icon} ${log.role.toUpperCase()}</strong>
                                                <small style="color: #666;">${time}</small>
                                            </div>
                                            <div style="color: #333; white-space: pre-wrap;">${escapeHtml(log.message)}</div>
                                            ${log.sources ? '<small style="color: #666;">✓ Used knowledge base</small>' : ''}
                                        </div>
                                    `;
                                });
                                
                                logsContent.innerHTML = html;
                            } else {
                                logsContent.innerHTML = '<p style="text-align: center; color: #999; padding: 20px;">No chat logs found yet.</p>';
                            }
                        })
                        .catch(error => {
                            logsContent.innerHTML = `<p style="color: #e74c3c;">Error loading chat logs: ${error.message}</p>`;
                        });
                }
                
                function escapeHtml(text) {
                    const div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                }
                
                function showMessage(msg, type) {
                    const msgDiv = document.getElementById('chatbot-message');
                    const className = type === 'success' ? 'success' : (type === 'error' ? 'error' : 'info');
                    msgDiv.innerHTML = `<div class="${className}" style="padding: 15px; border-radius: 4px; margin-bottom: 20px;">${msg}</div>`;
                    
                    if (type === 'success') {
                        setTimeout(() => { msgDiv.innerHTML = ''; }, 5000);
                    }
                }
            </script>
            
            <style>
                .info {
                    background-color: #e3f2fd;
                    color: #1976d2;
                    border: 1px solid #90caf9;
                }
            </style>
        </div>

        <!-- Skills Section -->
        <div class="content-section">
            <h2>Manage Skills</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="skill_name">Skill Name</label>
                    <input type="text" id="skill_name" name="skill_name" required>
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <input type="text" id="category" name="category" required>
                </div>
                <div class="form-group">
                    <label for="display_order">Display Order</label>
                    <input type="number" id="display_order" name="display_order" required>
                </div>
                <button type="submit" name="add_skill" class="btn-submit">Add Skill</button>
            </form>

            <div class="skills-grid">
                <?php
                $current_category = '';
                $skills_by_category = [];
                
                // Group skills by category
                while ($skill = $skills_result->fetch_assoc()) {
                    $skills_by_category[$skill['category']][] = $skill;
                }
                
                // Display skills grouped by category
                foreach ($skills_by_category as $category => $skills): ?>
                    <div class="category-header" style="grid-column: 1 / -1; margin-top: 15px; margin-bottom: 10px;">
                        <h3 style="color: #2c3e50; font-size: 1.2em; margin: 0;"><?php echo htmlspecialchars($category); ?></h3>
                    </div>
                    <?php foreach ($skills as $skill): ?>
                        <div class="skill-item">
                            <div>
                                <span><?php echo htmlspecialchars($skill['skill_name']); ?></span>
                                <span class="skill-category"><?php echo htmlspecialchars($skill['category']); ?></span>
                            </div>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="skill_id" value="<?php echo $skill['id']; ?>">
                                <button type="submit" name="delete_skill" class="btn-delete" title="Delete skill">×</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
