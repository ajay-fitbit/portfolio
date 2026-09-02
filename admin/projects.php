<?php
session_start();
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.php");
    exit();
}

include('../includes/config.php');

$message = '';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log("POST request received at: " . date('Y-m-d H:i:s'));
    error_log("POST data: " . print_r($_POST, true));
    error_log("Raw POST data: " . file_get_contents('php://input'));
    
    if (isset($_POST['add_project'])) {
        error_log("Add project form submitted");
        
        // Validate required fields
        $required_fields = ['title', 'description', 'technologies', 'date_completed', 'display_order'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            error_log("Missing required fields: " . implode(', ', $missing_fields));
            $message = '<div class="error">Please fill in all required fields: ' . implode(', ', $missing_fields) . '</div>';
        } else {
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $technologies = trim($_POST['technologies']);
            $date_completed = trim($_POST['date_completed']);
            $display_order = (int)$_POST['display_order'];
        
            error_log("Project data: " . json_encode([
                'title' => $title,
                'technologies' => $technologies,
                'date_completed' => $date_completed,
                'display_order' => $display_order
            ]));
            
            error_log("Attempting database insert with values: " . print_r([
                'title' => $title,
                'technologies' => $technologies,
                'date_completed' => $date_completed,
                'display_order' => $display_order
            ], true));

            // Verify database connection
            if (!$conn || $conn->connect_error) {
                error_log("Database connection error: " . ($conn ? $conn->connect_error : "No connection"));
                $message = '<div class="error">Database connection error. Please try again later.</div>';
            } else {
                $sql = "INSERT INTO projects (title, description, technologies, date_completed, display_order) VALUES (?, ?, ?, ?, ?)";
                error_log("Preparing SQL: " . $sql);
                
                $stmt = $conn->prepare($sql);
                
                if (!$stmt) {
                    error_log("Failed to prepare statement: " . $conn->error);
                    $message = '<div class="error">Database error: Failed to prepare statement</div>';
                } else {
                    error_log("Statement prepared successfully");
                    $stmt->bind_param("ssssi", $title, $description, $technologies, $date_completed, $display_order);
                
                    if ($stmt->execute()) {
                        error_log("Project added successfully with ID: " . $conn->insert_id);
                        $message = '<div class="success">Project added successfully!</div>';
                    } else {
                        error_log("Error executing statement: " . $stmt->error);
                        $message = '<div class="error">Error adding project: ' . $stmt->error . '</div>';
                    }
                    $stmt->close();
                }
            }
        }
    }

    if (isset($_POST['delete_project'])) {
        $project_id = $_POST['project_id'];
        
        $sql = "DELETE FROM projects WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $project_id);
        
        if ($stmt->execute()) {
            $message = '<div class="success">Project deleted successfully!</div>';
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $message = '<div class="error">Error deleting project: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }

    if (isset($_POST['update_project'])) {
        $project_id = $_POST['project_id'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $technologies = $_POST['technologies'];
        $date_completed = $_POST['date_completed'];
        $display_order = $_POST['display_order'];
        
        $sql = "UPDATE projects SET title = ?, description = ?, technologies = ?, date_completed = ?, display_order = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssii", $title, $description, $technologies, $date_completed, $display_order, $project_id);
        
        if ($stmt->execute()) {
            $message = '<div class="success">Project updated successfully!</div>';
        } else {
            $message = '<div class="error">Error updating project: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
}

// Get current projects
$projects_sql = "SELECT * FROM projects ORDER BY display_order";
$projects_result = $conn->query($projects_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Projects - Portfolio Admin</title>
    <!-- Include TinyMCE -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.7.0/tinymce.min.js"></script>
    <script>
        // Store form data in localStorage
        function saveFormData() {
            const form = document.getElementById('addProjectForm');
            const editor = tinymce.get('description');
            const formData = {
                title: form.title.value,
                technologies: form.technologies.value,
                date_completed: form.date_completed.value,
                display_order: form.display_order.value,
                description: editor ? editor.getContent() : form.description.value
            };
            localStorage.setItem('projectFormData', JSON.stringify(formData));
        }

        // Restore form data from localStorage
        function restoreFormData() {
            const savedData = localStorage.getItem('projectFormData');
            if (savedData) {
                const formData = JSON.parse(savedData);
                const form = document.getElementById('addProjectForm');
                form.title.value = formData.title || '';
                form.technologies.value = formData.technologies || '';
                form.date_completed.value = formData.date_completed || '';
                form.display_order.value = formData.display_order || '';
                
                // Wait for TinyMCE to initialize
                tinymce.get('description') ? 
                    tinymce.get('description').setContent(formData.description || '') :
                    setTimeout(() => tinymce.get('description').setContent(formData.description || ''), 1000);
            }
        }

        // Clear saved form data
        function clearFormData() {
            localStorage.removeItem('projectFormData');
        }

        // Form submission handler
        function submitProjectForm(formId) {
            // Make sure TinyMCE saves its content to the textarea
            tinymce.triggerSave();
            
            const form = document.getElementById(formId);
            const editor = tinymce.get('description');
            
            // Save form data before validation
            saveFormData();
            
            // Validate TinyMCE content
            if (!editor.getContent()) {
                alert('Please enter a project description');
                editor.focus();
                return false;
            }
            
            // If validation passes, clear saved data
            clearFormData();
            return true;
        }

        // Set up form data persistence
        document.addEventListener('DOMContentLoaded', function() {
            restoreFormData();
            
            // Save form data when input changes
            const form = document.getElementById('addProjectForm');
            form.addEventListener('input', saveFormData);
        });

        tinymce.init({
            selector: '.rich-editor',
            height: 400,
            setup: function(editor) {
                editor.on('change', function() {
                    editor.save(); // Save content to textarea
                    saveFormData(); // Save to localStorage when content changes
                });
                editor.on('init', function() {
                    // Copy initial content if any
                    editor.save();
                });
            },
            forced_root_block: 'p',
            invalid_elements: 'script',
            verify_html: true,
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

        .project-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-top: 25px;
            max-width: 100%;
        }

        .project-item {
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            padding: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
        }

        .project-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
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

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .setup-container {
                padding: 10px;
            }

            .content-section {
                padding: 15px;
            }

            .project-grid {
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
            <a href="content.php">Manage Content</a>
            <a href="resume_admin.php">Manage Resume</a>
            <a href="../index.php">View Site</a>
            <a href="logout.php">Logout</a>
        </div>

        <h1>Manage Projects</h1>
        
        <?php echo $message; ?>

        <!-- Add Project Form -->
        <div class="content-section">
            <h2>Add New Project</h2>
            <form method="POST" id="addProjectForm" onsubmit="return submitProjectForm('addProjectForm')">
                <div class="form-group">
                    <label for="title">Project Title</label>
                    <input type="text" id="title" name="title" required minlength="3">
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="rich-editor"></textarea>
                    <input type="hidden" name="description_required" id="description_required">
                </div>
                <div class="form-group">
                    <label for="technologies">Technologies Used</label>
                    <input type="text" id="technologies" name="technologies" required minlength="2"></div>
                <div class="form-group">
                    <label for="date_completed">Date Completed</label>
                    <input type="date" id="date_completed" name="date_completed" required>
                </div>
                <div class="form-group">
                    <label for="display_order">Display Order</label>
                    <input type="number" id="display_order" name="display_order" required>
                </div>
                <button type="submit" name="add_project" class="btn-submit">Add Project</button>
            </form>
            <script>
                // Add debug logging for form submission
                document.getElementById('addProjectForm').addEventListener('submit', function(e) {
                    console.log('Form submit event triggered');
                });
            </script>
        </div>

        <!-- Existing Projects -->
        <div class="content-section">
            <h2>Existing Projects</h2>
            <div class="project-grid">
                <?php while ($project = $projects_result->fetch_assoc()): ?>
                    <div class="project-item">
                        <form method="POST">
                            <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                            <div class="form-group">
                                <label for="title_<?php echo $project['id']; ?>">Title</label>
                                <input type="text" id="title_<?php echo $project['id']; ?>" name="title" 
                                    value="<?php echo htmlspecialchars($project['title']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="description_<?php echo $project['id']; ?>">Description</label>
                                <textarea id="description_<?php echo $project['id']; ?>" name="description" 
                                    class="rich-editor" required><?php echo htmlspecialchars($project['description']); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="technologies_<?php echo $project['id']; ?>">Technologies</label>
                                <input type="text" id="technologies_<?php echo $project['id']; ?>" name="technologies" 
                                    value="<?php echo htmlspecialchars($project['technologies']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="date_<?php echo $project['id']; ?>">Date Completed</label>
                                <input type="date" id="date_<?php echo $project['id']; ?>" name="date_completed" 
                                    value="<?php echo $project['date_completed']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="order_<?php echo $project['id']; ?>">Display Order</label>
                                <input type="number" id="order_<?php echo $project['id']; ?>" name="display_order" 
                                    value="<?php echo $project['display_order']; ?>" required>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <button type="submit" name="update_project" class="btn-submit">Update</button>
                                <button type="submit" name="delete_project" class="btn-delete" 
                                    onclick="return confirm('Are you sure you want to delete this project?')">Delete</button>
                            </div>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</body>
</html>
