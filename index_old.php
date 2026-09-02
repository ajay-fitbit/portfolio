<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles.css">
    <title>Ajay Singh - Portfolio</title>
</head>
<body>
    <header>
        <h1>Welcome to My Portfolio</h1>
    </header>
    
    <div class="tab-container">
        <div class="tab active" onclick="openTab('about')">About Me</div>
        <div class="tab" onclick="openTab('resume')">Resume</div>
        <div class="tab" onclick="openTab('projects')">Projects</div>
    </div>

    <div id="tabContent" class="tab-content active">
        <h2>About Me</h2>
            <p>I Have close to 19 years of experience in Information Technology on the various domain, such as Pharama, Energy, Insurance etc.
Out of which close to 6 years of US work experience. I have Mostly worked as building Business Analytics application which includes (Microsoft Business Intelligence Suit), SSRS, SSIS and for sort period of time SSAS as well for OLAP applications. Including PowerBI for sometimes.
Worded with DBAs to design and networking between Transactional DB to Warehouse which is Reporting DB

Worked with different business users and converting their requirement into functional and technical documents.

Managed Backoffice reporting team along with BO Operation manager in order to train and guide them on their day to day activity, Also trained part of the reporting team on SQL and Crystal reports for enhancing the quality of work and reducing the delivery time and meeting company standard.

Was also part of a team in migrating 100s of Crystal report into SSRS reports, this includes procedure conversion and creation as well.

Have used JIRA work management tool for project progress tracking in Agile framework model and TFS for version control, issue resolving and application management.
Used Jenkins for project intigration.</p>
        <h3>My Skills</h3>
        <table>
            <tr>
                <td>MS SQL Server</td>
                <td>T-SQL</td>
                <td>Query Optimization</td>
                <td>Dashboard</td>
            </tr>
            <tr>
                <td>MS Business Intelligence Suite (MSBI)</td>
                <td>SQL Server Reporting Services (SSRS)</td>
                <td>SQL Server Intigration Services (SSIS)</td>
                <td>DataWarehouse</td>
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
            </tr>
        </table>
        <br>
    </div>
    <footer>
        <p>© 2024 Ajay Singh</p>
    </footer>
    <script>
        function openTab(tabName) {
            // Hide the current content
            var tabContent = document.getElementById('tabContent');
            //tabContent.innerHTML = ''; // Clear previous content

            // Remove active class from all tabs
            var tabs = document.querySelectorAll('.tab');
            tabs.forEach(function(tab) {
                tab.classList.remove('active');
            });

            // Mark the clicked tab as active
            event.currentTarget.classList.add('active');

            // Load content based on the clicked tab
            if (tabName === 'about') {
                // Load default content for About Me
                //tabContent.innerHTML = '<h2>About Me</h2><p>This is where your About Me content will go.</p>';
                var tabContent = document.getElementById('tabContent');
                tabContent.classList.add('active'); // Show the content
            } else {
                tabContent.classList.remove('active'); // Hide content initially
                var xhr = new XMLHttpRequest();
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        tabContent.innerHTML = xhr.responseText; // Load response into tabContent
                        tabContent.classList.add('active'); // Show the content
                    }
                };
                if (tabName === 'resume') {
                    xhr.open('GET', 'resume.php', true);
                } else if (tabName === 'projects') {
                    xhr.open('GET', 'projects.php', true);
                }
                xhr.send();
            }
        }

        // Load the About Me content by default on initial page load
        window.onload = function() {
            openTab('about');
        };
    </script>
</body>
</html>
