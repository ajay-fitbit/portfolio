<?php
session_start();
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.php");
    exit();
}

include('../includes/config.php');

// Handle content updates
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $section = $_POST['section'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    
    $sql = "INSERT INTO content_sections (section_name, title, content) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            title = VALUES(title), 
            content = VALUES(content)";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $section, $title, $content);
    
    if ($stmt->execute()) {
        $success = "Content updated successfully!";
    } else {
        $error = "Error updating content: " . $conn->error;
    }
}

// Get existing content
$sql = "SELECT * FROM content_sections";
$result = $conn->query($sql);
$sections = [];
while ($row = $result->fetch_assoc()) {
    $sections[$row['section_name']] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio Admin Dashboard</title>
    <!-- Include TinyMCE -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.7.0/tinymce.min.js"></script>
    <script>
        tinymce.init({
            selector: '.rich-editor',
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

        .admin-container {
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

        .section-editor {
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

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
        }

        .form-group input:focus, .form-group textarea:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 2px rgba(52,152,219,0.2);
        }

        .form-group textarea {
            height: 200px;
            resize: vertical;
        }

        .btn-save {
            background-color: #2ecc71;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .btn-save:hover {
            background-color: #27ae60;
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
            .admin-container {
                padding: 10px;
            }

            .section-editor {
                padding: 15px;
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
    <div class="admin-container">
        <div class="nav">
            <a href="content.php">Manage Content</a>
            <a href="../index.php">View Site</a>
            <a href="logout.php">Logout</a>
        </div>

        <h1>Dashboard</h1>
        
        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Main Page Editor -->
        <div class="section-editor">
            <h2>Edit Main Page Content</h2>
            <form method="POST">
                <input type="hidden" name="section" value="main">
                <div class="form-group">
                    <label for="main-title">Introduction Title</label>
                    <input type="text" id="main-title" name="title" 
                           value="<?php echo isset($sections['main']) ? htmlspecialchars($sections['main']['title']) : 'Hello! I\'m Ajay Singh'; ?>" required>
                </div>
                <div class="form-group">
                    <label for="main-content">Introduction Text</label>
                    <textarea id="main-content" name="content" class="rich-editor"><?php 
                        echo isset($sections['main']) ? htmlspecialchars($sections['main']['content']) : ''; 
                    ?></textarea>
                </div>
                <button type="submit" class="btn-save">Save Main Content</button>
            </form>
        </div>
        
        <!-- About Section Editor -->
        <div class="section-editor">
            <h2>Edit About Section</h2>
            <form method="POST">
                <input type="hidden" name="section" value="about">
                <div class="form-group">
                    <label for="about-title">Title</label>
                    <input type="text" id="about-title" name="title" 
                           value="<?php echo isset($sections['about']) ? htmlspecialchars($sections['about']['title']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="about-content">Content</label>
                    <textarea id="about-content" name="content" class="rich-editor"><?php echo isset($sections['about']) ? htmlspecialchars($sections['about']['content']) : ''; ?></textarea>
                </div>
                <button type="submit" class="btn-save">Save About Section</button>
            </form>
        </div>
        
        <!-- Projects Section Editor -->
        <div class="section-editor">
            <h2>Edit Projects Section</h2>
            <form method="POST">
                <input type="hidden" name="section" value="projects">
                <div class="form-group">
                    <label for="projects-title">Title</label>
                    <input type="text" id="projects-title" name="title" 
                           value="<?php echo isset($sections['projects']) ? htmlspecialchars($sections['projects']['title']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="projects-content">Content</label>
                    <textarea id="projects-content" name="content" class="rich-editor"><?php echo isset($sections['projects']) ? htmlspecialchars($sections['projects']['content']) : ''; ?></textarea>
                </div>
                <button type="submit" class="btn-save">Save Projects Section</button>
            </form>
        </div>
        
        <!-- Resume Section Editor -->
        <div class="section-editor">
            <h2>Edit Resume Section</h2>
            <form method="POST">
                <input type="hidden" name="section" value="resume">
                <div class="form-group">
                    <label for="resume-title">Title</label>
                    <input type="text" id="resume-title" name="title" 
                           value="<?php echo isset($sections['resume']) ? htmlspecialchars($sections['resume']['title']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="resume-content">Content</label>
                    <textarea id="resume-content" name="content"><?php echo isset($sections['resume']) ? htmlspecialchars($sections['resume']['content']) : ''; ?></textarea>
                </div>
                <button type="submit" class="btn-save">Save Resume Section</button>
            </form>
        </div>
    </div>
</body>
</html>
