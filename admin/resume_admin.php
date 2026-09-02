<?php
session_start();
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.php");
    exit();
}

include('../includes/config.php');

$message = '';

// Create resume_sections table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS resume_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_name VARCHAR(50) NOT NULL,
    heading VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    display_order INT NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating resume_sections table: " . $conn->error);
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_section'])) {
        $section_name = $_POST['section_name'];
        $heading = $_POST['heading'];
        $content = $_POST['content'];
        
        // Clean up paragraph tags for Summary section
        if ($section_name === 'Summary') {
            // Remove single paragraphs that wrap all content
            if (preg_match('/^<p>(.*)<\/p>$/s', trim($content), $matches)) {
                $content = $matches[1];
            }
        }
        
        $display_order = (int)$_POST['display_order'];
        
        $sql = "INSERT INTO resume_sections (section_name, heading, content, display_order) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $section_name, $heading, $content, $display_order);
        
        if ($stmt->execute()) {
            $message = '<div class="success">Resume section added successfully!</div>';
        } else {
            $message = '<div class="error">Error adding resume section: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }

    if (isset($_POST['update_section'])) {
        $section_id = $_POST['section_id'];
        $heading = $_POST['heading'];
        $content = $_POST['content'];
        
        // Get section name for the ID
        $section_query = "SELECT section_name FROM resume_sections WHERE id = ?";
        $stmt = $conn->prepare($section_query);
        $stmt->bind_param("i", $section_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $section = $result->fetch_assoc();
        $stmt->close();
        
        // Clean up paragraph tags for Summary section
        if ($section && $section['section_name'] === 'Summary') {
            // Remove single paragraphs that wrap all content
            if (preg_match('/^<p>(.*)<\/p>$/s', trim($content), $matches)) {
                $content = $matches[1];
            }
        }
        
        $display_order = (int)$_POST['display_order'];
        
        $sql = "UPDATE resume_sections SET heading = ?, content = ?, display_order = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $heading, $content, $display_order, $section_id);
        
        if ($stmt->execute()) {
            $message = '<div class="success">Resume section updated successfully!</div>';
        } else {
            $message = '<div class="error">Error updating resume section: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }

    if (isset($_POST['delete_section'])) {
        $section_id = $_POST['section_id'];
        
        $sql = "DELETE FROM resume_sections WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $section_id);
        
        if ($stmt->execute()) {
            $message = '<div class="success">Resume section deleted successfully!</div>';
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $message = '<div class="error">Error deleting resume section: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
}

// Get current resume sections
$sections_sql = "SELECT * FROM resume_sections ORDER BY display_order";
$sections_result = $conn->query($sections_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Resume - Portfolio Admin</title>
    <!-- Include TinyMCE -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.7.0/tinymce.min.js"></script>
    <script>
        function toggleJobDetails(element) {
            const details = element.nextElementSibling;
            const icon = element.querySelector('.toggle-icon');
            if (details.style.display === "none" || details.style.display === "") {
                details.style.display = "block";
                icon.textContent = "-";
            } else {
                details.style.display = "none";
                icon.textContent = "+";
            }
        }
    </script>
    <script>
        // Initialize TinyMCE for all textareas with class 'rich-editor'
        function addJobTemplate() {
            const template = `<div class="job">
    <div class="job-title" onclick="toggleJobDetails(this)"><span class="toggle-icon">+</span>
        Job Title - Company Name (Location) | Start Year - End Year
    </div>
    <div class="job-details">
        <ul>
            <li>Led development and implementation of [specific project/system]</li>
            <li>Designed and optimized [number] database procedures, improving performance by [X]%</li>
            <li>Collaborated with cross-functional teams to deliver [specific outcome]</li>
            <li>Managed and mentored team of [number] developers/analysts</li>
            <li>Implemented automation solutions resulting in [specific improvement]</li>
            <li>Developed and maintained documentation for [specific systems/processes]</li>
            <li>Created and executed test plans for [specific component/feature]</li>
            <li>Utilized [tools/technologies] to enhance [specific process/outcome]</li>
        </ul>
    </div>
</div>\n\n`; // Add extra newlines for spacing
            const editor = tinymce.activeEditor;
            const content = editor.getContent();
            
            // Find the closest parent job div
            const selection = editor.selection;
            const selectedElement = selection.getNode();
            let jobDiv = selectedElement.closest('.job');
            
            if (jobDiv) {
                // If we're inside a job entry, insert after that job's div
                jobDiv.insertAdjacentHTML('afterend', template);
                editor.setContent(editor.getContent()); // Refresh content
            } else {
                // If we're not inside a job entry, insert at cursor position
                editor.insertContent(template);
            }
        }

        // Function to submit form via AJAX
        function submitFormAjax(form, editor) {
            const formData = new FormData(form);
            
            // Add the update_section parameter to trigger the correct server action
            formData.append('update_section', '1');
            
            // Always get the latest content from either the editor or textarea
            let currentContent;
            if (editor && editor.isHidden()) {
                console.log('Getting content from textarea for submission');
                // Get raw content from textarea in plain text mode
                currentContent = editor.getElement().value;
            } else if (editor) {
                console.log('Getting content from editor for submission');
                // Get processed content from editor in rich text mode
                currentContent = editor.getContent();
            }
            
            if (currentContent !== undefined) {
                console.log('Submitting content:', currentContent);
                formData.set('content', currentContent);
            }
            
            // Log all form data being sent
            console.log('Form data being submitted:');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
            
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(html => {
                console.log('Response received, content:', html);
                // Create a temporary div to parse the response
                const div = document.createElement('div');
                div.innerHTML = html;
                
                // Find the success/error message in the response
                const message = div.querySelector('.success, .error');
                if (message) {
                    console.log('Message found:', message.textContent);
                    // Show the message at the top of the page
                    const existingMessage = document.querySelector('.success, .error');
                    if (existingMessage) {
                        existingMessage.replaceWith(message);
                    } else {
                        document.querySelector('h1').insertAdjacentElement('afterend', message);
                    }
                    
                    // Scroll to show the message
                    message.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    // If it's a success message, refresh the page after a delay
                    if (message.classList.contains('success')) {
                        console.log('Success message found, will refresh page');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                } else {
                    console.log('No message found in response');
                    // Create a temporary error message if none was found
                    const tempMessage = document.createElement('div');
                    tempMessage.className = 'error';
                    tempMessage.textContent = 'Update completed but no confirmation message received';
                    const existingMessage = document.querySelector('.success, .error');
                    if (existingMessage) {
                        existingMessage.replaceWith(tempMessage);
                    } else {
                        document.querySelector('h1').insertAdjacentElement('afterend', tempMessage);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Display error message to user
                const errorMessage = document.createElement('div');
                errorMessage.className = 'error';
                errorMessage.textContent = 'An error occurred while saving: ' + error.message;
                const existingMessage = document.querySelector('.success, .error');
                if (existingMessage) {
                    existingMessage.replaceWith(errorMessage);
                } else {
                    document.querySelector('h1').insertAdjacentElement('afterend', errorMessage);
                }
                errorMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        }

        // Add toggle button to switch between rich text and plain text
        function toggleEditorMode(editorId) {
            const editor = tinymce.get(editorId);
            const container = editor.getContainer();
            const textarea = editor.getElement();
            const form = textarea.closest('form');
            
            // Get a fresh reference to the form
            const currentForm = editor.getElement().closest('form');
            
            // Find update button and add direct click handler
            const updateBtn = currentForm.querySelector('button[name="update_section"]');
            if (updateBtn && !updateBtn.hasClickHandler) {
                updateBtn.hasClickHandler = true;
                // Remove the button's default submit behavior
                updateBtn.setAttribute('type', 'button');
                updateBtn.onclick = function(e) {
                    e.preventDefault();
                    console.log('Update button clicked');
                    
                    // Ensure all form fields are included
                    const sectionId = form.querySelector('input[name="section_id"]').value;
                    const heading = form.querySelector('input[name="heading"]').value;
                    const displayOrder = form.querySelector('input[name="display_order"]').value;
                    
                    console.log('Section ID:', sectionId);
                    console.log('Heading:', heading);
                    console.log('Display Order:', displayOrder);
                    
                    // Get the current content based on editor mode
                    let currentContent;
                    if (editor.isHidden()) {
                        console.log('Editor is hidden, getting content from textarea');
                        currentContent = textarea.value;
                    } else {
                        console.log('Editor is visible, getting content from editor');
                        currentContent = editor.getContent();
                    }
                    console.log('Current content:', currentContent);
                    
                    // Update both editor and textarea to ensure consistency
                    editor.setContent(currentContent);
                    textarea.value = currentContent;
                    editor.save();
                    
                    // Verify form has all required fields
                    if (!sectionId || !heading || !displayOrder || !currentContent) {
                        console.error('Missing required fields');
                        return;
                    }
                    
                    // Submit form via AJAX
                    submitFormAjax(form, editor);
                };
            }

            if (editor.isHidden()) {
                // Switch to Rich Text mode
                console.log('Switching to Rich Text mode');
                const activeTextarea = editor.getElement();
                const plainTextContent = activeTextarea.value;
                
                // Properly move focus before hiding
                activeTextarea.blur();
                editor.show();
                activeTextarea.style.display = 'none';
                
                // Remove any aria attributes
                activeTextarea.removeAttribute('aria-hidden');
                
                // Properly restore the content with formatting
                editor.setContent(plainTextContent, {format: 'raw'});
                editor.focus();
                
                // Update button text
                const toggleBtn = container.previousSibling;
                if (toggleBtn && toggleBtn.classList.contains('toggle-mode-btn')) {
                    toggleBtn.textContent = 'Switch to Plain Text';
                }
            } else {
                // Switch to Plain Text mode
                console.log('Switching to Plain Text mode');
                // Get the raw HTML content
                const richTextContent = editor.getContent();
                editor.hide();
                
                // Get the current textarea reference
                const activeTextarea = editor.getElement();
                
                // Remove any existing event listeners
                const newTextarea = activeTextarea.cloneNode(true);
                if (activeTextarea.parentNode) {
                    // Properly handle focus before replacing
                    if (document.activeElement === activeTextarea) {
                        activeTextarea.blur();
                    }
                    activeTextarea.parentNode.replaceChild(newTextarea, activeTextarea);
                }
                
                // Update the editor's element reference
                editor.targetElm = newTextarea;
                
                // Set content and ensure proper ARIA state
                newTextarea.value = richTextContent;
                newTextarea.removeAttribute('aria-hidden');
                activeTextarea.style.cssText = `
                    display: block;
                    width: 100%;
                    min-height: 400px;
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    background-color: #ffffff;
                    color: #000000;
                    font-size: 14px;
                    line-height: 1.6;
                    box-sizing: border-box;
                    resize: vertical;
                `;
                activeTextarea.removeAttribute('readonly');
                activeTextarea.removeAttribute('disabled');
                activeTextarea.removeAttribute('aria-hidden');
                
                const toggleBtn = container.previousSibling;
                if (toggleBtn && toggleBtn.classList.contains('toggle-mode-btn')) {
                    toggleBtn.textContent = 'Switch to Rich Text';
                }
            }
            
            // Set up fresh event handlers for plain text mode
            const activeTextarea = editor.getElement();
            
            // Ensure proper focus management
            if (editor.isHidden()) {
                setTimeout(() => {
                    activeTextarea.focus();
                }, 0);
            }
            
            const handleInput = function(e) {
                console.log('Textarea content changed');
                const content = this.value;
                // Store content without processing
                editor.setContent(content, {format: 'raw'});
            };
            
            activeTextarea.addEventListener('input', handleInput);
            activeTextarea.addEventListener('change', handleInput);
            activeTextarea.addEventListener('keyup', handleInput);
            activeTextarea.addEventListener('paste', handleInput);
            
            // Remove any form submit handlers to prevent default submission
            form.onsubmit = function(e) {
                e.preventDefault();
                return false;
            };
        }

        tinymce.init({
            selector: '.rich-editor',
            height: 400,
            forced_root_block: 'p',
            newline_behavior: 'block',
            extended_valid_elements: 'div[*],span[*]',
            custom_elements: 'div,span',
            valid_children: '+div[div],+div[span]',
            allow_html_in_named_anchor: true,
            allow_script_urls: true,
            verify_html: false,
            setup: function(editor) {
                editor.on('change', function() {
                    editor.save();
                });
                
                // Add custom button to toggle between rich text and plain text
                editor.on('init', function() {
                    const toggleBtn = document.createElement('button');
                    toggleBtn.textContent = 'Switch to Plain Text';
                    toggleBtn.className = 'toggle-mode-btn btn-submit';
                    toggleBtn.style.cssText = `
                        position: relative;
                        display: block;
                        margin: 10px 0;
                        padding: 8px 16px;
                        background: #3498db;
                        color: white;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 14px;
                        width: auto;
                    `;
                    toggleBtn.onclick = function(e) {
                        e.preventDefault();
                        toggleEditorMode(editor.id);
                    };
                    // Insert the button before the editor
                    const editorContainer = editor.getContainer();
                    editorContainer.parentNode.insertBefore(toggleBtn, editorContainer);
                });
                
                editor.on('init', function() {
                    // Add click handlers to all existing job titles
                    editor.getBody().querySelectorAll('.job-title').forEach(function(title) {
                        if (!title.hasAttribute('onclick')) {
                            title.setAttribute('onclick', 'toggleJobDetails(this)');
                        }
                    });
                });
                
                editor.on('SetContent', function() {
                    // Add click handlers to all job titles after content changes
                    editor.getBody().querySelectorAll('.job-title').forEach(function(title) {
                        if (!title.hasAttribute('onclick')) {
                            title.setAttribute('onclick', 'toggleJobDetails(this)');
                        }
                    });
                });

                // Add a custom button for job template
                editor.ui.registry.addButton('addjob', {
                    text: 'Add Job Entry',
                    onAction: function() {
                        addJobTemplate();
                    }
                });
            },
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'charmap', 'preview',
                'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'table', 'help', 'wordcount'
            ],
            toolbar1: 'undo redo | styles | bold italic | alignleft aligncenter alignright alignjustify | addjob',
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
                .job {
                    margin-bottom: 20px !important;
                    border: 1px solid #e9ecef !important;
                    border-radius: 8px !important;
                    overflow: hidden !important;
                    display: block !important;
                    clear: both !important;
                }
                .job-title {
                    background-color: #f8f9fa;
                    padding: 15px;
                    cursor: pointer;
                    font-weight: bold;
                }
                .job-details {
                    padding: 15px;
                }
                .toggle-icon {
                    display: inline-block;
                    width: 20px;
                    text-align: center;
                    margin-right: 10px;
                }
                ul {
                    margin: 0;
                    padding-left: 20px;
                }
                li {
                    margin-bottom: 8px;
                }
            `
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

        .resume-sections {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-top: 25px;
        }

        .resume-section {
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            padding: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .resume-section:hover {
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

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="nav">
            <a href="index.php">Dashboard</a>
            <a href="content.php">Manage Content</a>
            <a href="projects.php">Manage Projects</a>
            <a href="../index.php">View Site</a>
            <a href="logout.php">Logout</a>
        </div>

        <h1>Manage Resume</h1>
        
        <?php echo $message; ?>

        <!-- Add Resume Section Form -->
        <div class="content-section">
            <h2>Add New Resume Section</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="section_name">Section Name (e.g., work-experience, education)</label>
                    <input type="text" id="section_name" name="section_name" required minlength="3">
                </div>
                <div class="form-group">
                    <label for="heading">Section Heading</label>
                    <input type="text" id="heading" name="heading" required minlength="3">
                </div>
                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea id="content" name="content" class="rich-editor"></textarea>
                </div>
                <div class="form-group">
                    <label for="display_order">Display Order</label>
                    <input type="number" id="display_order" name="display_order" required>
                </div>
                <button type="submit" name="add_section" class="btn-submit">Add Section</button>
            </form>
        </div>

        <!-- Existing Resume Sections -->
        <div class="content-section">
            <h2>Existing Resume Sections</h2>
            <div class="resume-sections">
                <?php while ($section = $sections_result->fetch_assoc()): ?>
                    <div class="resume-section">
                        <form method="POST">
                            <input type="hidden" name="section_id" value="<?php echo $section['id']; ?>">
                            <div class="form-group">
                                <label>Section Name: <?php echo htmlspecialchars($section['section_name']); ?></label>
                            </div>
                            <div class="form-group">
                                <label for="heading_<?php echo $section['id']; ?>">Heading</label>
                                <input type="text" id="heading_<?php echo $section['id']; ?>" name="heading" 
                                    value="<?php echo htmlspecialchars($section['heading']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="content_<?php echo $section['id']; ?>">Content</label>
                                <textarea id="content_<?php echo $section['id']; ?>" name="content" 
                                    class="rich-editor"><?php echo htmlspecialchars($section['content']); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="order_<?php echo $section['id']; ?>">Display Order</label>
                                <input type="number" id="order_<?php echo $section['id']; ?>" name="display_order" 
                                    value="<?php echo $section['display_order']; ?>" required>
                            </div>
                            <div class="button-group">
                                <button type="submit" name="update_section" class="btn-submit">Update</button>
                                <button type="submit" name="delete_section" class="btn-delete" 
                                    onclick="return confirm('Are you sure you want to delete this section?')">Delete</button>
                            </div>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</body>
</html>
