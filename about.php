<?php
include('includes/config.php');

// Get about content
$about_sql = "SELECT title, content FROM content_sections WHERE section_name = 'about'";
$about_result = $conn->query($about_sql);
$about_content = $about_result->fetch_assoc();

// Get skills grouped by category
$skills_sql = "SELECT * FROM skills ORDER BY category, display_order";
$skills_result = $conn->query($skills_sql);
$skills = [];
while ($row = $skills_result->fetch_assoc()) {
    $skills[$row['category']][] = $row['skill_name'];
}

// If no content in database, use default content
if (!$about_content) {
    $about_content = [
        'title' => 'About Me',
        'content' => 'Default about content. Please update this in the admin panel.'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($about_content['title']); ?></title>
    <link rel="stylesheet" href="css/chatbot.css">
</head>
<body>
    <main>
        <h2><?php echo htmlspecialchars($about_content['title']); ?></h2>
        <div class="about-content">
            <?php echo $about_content['content']; ?>
        </div>

        <!-- <h3>My Skills</h3> -->
        <table>
            <?php
            if (empty($skills)) {
                // Use existing static skills as default
                echo '';
            } else {
                $columns = 4;
                $skills_flat = [];
                foreach ($skills as $category => $category_skills) {
                    foreach ($category_skills as $skill) {
                        $skills_flat[] = $skill;
                    }
                }
                
                for ($i = 0; $i < count($skills_flat); $i += $columns) {
                    echo '<tr>';
                    for ($j = 0; $j < $columns; $j++) {
                        if (isset($skills_flat[$i + $j])) {
                            echo '<td>' . htmlspecialchars($skills_flat[$i + $j]) . '</td>';
                        } else {
                            echo '<td></td>';
                        }
                    }
                    echo '</tr>';
                }
            }
            ?> 

        <!-- <h3>My Skills</h3> -->
        <!-- <table> -->
            <!-- <tr>
                <td>MS SQL Server</td>
                <td>T-SQL</td>
                <td>Query Optimization</td>
                <td>Dashboard</td>
            </tr>
            <tr>
                <td>MS Business Intelligence Suite (MSBI)</td>
                <td>SQL Server Reporting Services (SSRS)</td>
                <td>SQL Server Intigration Services (SSIS)</td>
                <td>Data Warehouse</td>
            </tr>
            <tr>
                <td>Power BI Desktop</td>
                <td>Development Technologies</td>
                <td>Project Management Tools</td>
                <td>Communication and Team Leadership</td>
            </tr>
            <tr>
                <td>Version Control</td>
                <td>CI/CD and Automation</td>
                <td>Analytical Leadership</td>
                <td>Crystal Reports Enterprise XI R2</td>
            </tr>
            <tr>
                <td>JIRA</td>
                <td>Gitbub</td>
                <td>Bitbucket</td>
                <td>Jenkins</td>
            </tr> -->
        </table>
        <br>
    </main>
</body>
</html>
