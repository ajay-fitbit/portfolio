<?php
// Function to convert section name to CSS class name
function sectionNameToClass($name) {
    // Convert to lowercase and replace spaces with hyphens
    $class = strtolower(str_replace(' ', '-', $name));
    // Remove any non-alphanumeric characters except hyphens
    $class = preg_replace('/[^a-z0-9-]/', '', $class);
    // Add default class if empty
    return empty($class) ? 'section' : $class;
}

// Function to get geolocation data from IP address
function getGeoLocation($ip_address) {
    // Use a geolocation API (e.g., ip-api.com)
    $url = "http://ip-api.com/json/$ip_address"; // Example API

    // Use file_get_contents with error handling
    $response = @file_get_contents($url);
    
    // Check if the response is false
    if ($response === false) {
        // Log the error and return null
        error_log("Error fetching geolocation data for IP: $ip_address");
        return null;
    }

    // Decode the JSON response
    $geo_data = json_decode($response, true);

    // Check if the response contains an error
    if (isset($geo_data['status']) && $geo_data['status'] === 'fail') {
        // Log the error message from the API
        error_log("Geolocation API error for IP: $ip_address - " . $geo_data['message']);
        return null;
    }

    return $geo_data;
}

// Track visitor information
$ip_address = $_SERVER['REMOTE_ADDR'];
$timestamp = date("Y-m-d H:i:s");

// Get geolocation data
$geo_data = getGeoLocation($ip_address);

// Initialize location variables
$country = 'Unknown';
$city = 'Unknown';
$region = 'Unknown';

// Check if geo_data is valid before accessing it
if ($geo_data) {
    $country = isset($geo_data['country']) ? $geo_data['country'] : 'Unknown';
    $city = isset($geo_data['city']) ? $geo_data['city'] : 'Unknown';
    $region = isset($geo_data['regionName']) ? $geo_data['regionName'] : 'Unknown';
}

// Create log entry
$log_entry = "IP: $ip_address - Visited on: $timestamp - Location: $city, $region, $country\n";

// Append to log file with error handling
if (file_put_contents('visitors.log', $log_entry, FILE_APPEND) === false) {
    error_log("Error writing to log file for IP: $ip_address");
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/resume.css">
    <link rel="stylesheet" href="css/chatbot.css">
    <title>Ajay Singh - Resume</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        section h2 {
            background: linear-gradient(to right, #2c3e50, #3498db);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-top: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .job-title {
            cursor: pointer;
            padding: 12px 15px;
            background: linear-gradient(to right, #f8f9fa, #e9ecef);
            border: 1px solid #dee2e6;
            border-radius: 6px;
            margin-bottom: 8px;
            user-select: none;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .job-title:hover {
            background: linear-gradient(to right, #e9ecef, #dee2e6);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transform: translateY(-1px);
        }
        .job-details {
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            margin-bottom: 20px;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .toggle-icon {
            display: inline-block;
            width: 24px;
            height: 24px;
            text-align: center;
            font-weight: bold;
            margin-right: 10px;
            background-color: #3498db;
            color: white;
            border-radius: 50%;
            line-height: 24px;
        }
        .job {
            margin-bottom: 20px;
        }
    </style>

    <script>
        function toggleJobDetails(element) {
            var details = element.nextElementSibling;
            var icon = element.querySelector('.toggle-icon');
            if (details.style.display === "none" || details.style.display === "") {
                details.style.display = "block";
                icon.textContent = "-";
            } else {
                details.style.display = "none";
                icon.textContent = "+";
            }
        }

        // Initialize all job details sections to be hidden on page load
        document.addEventListener('DOMContentLoaded', function() {
            var jobDetails = document.querySelectorAll('.job-details');
            jobDetails.forEach(function(detail) {
                detail.style.display = "none";
            });
        });
    </script>
</head>
<body>
    <main>
        <section class="download">
            <a href="download.php?file=resume/ajay_singh_resume.pdf" download class="download-button">
                <i class="fa fa-file-pdf-o" aria-hidden="true"></i> Download PDF
            </a>
            <!-- <a href="download.php?file=resume/SQL_Developer.docx" download class="download-button">
                <i class="fa fa-file-word-o" aria-hidden="true"></i> Download Word
            </a> -->
        </section>
        <?php
        include('includes/config.php');
        
        // Debug information
        if (!isset($conn) || $conn->connect_error) {
            die("Database connection failed");
        }
        
        // Get all resume sections ordered by display_order
        $sections_sql = "SELECT * FROM resume_sections ORDER BY display_order";
        
        // Debug - print sections
        // echo "<pre>"; print_r($sections_sql); echo "</pre>";
        $sections_result = $conn->query($sections_sql);
        
        // Store sections in an array
        $sections = array();
        while ($section = $sections_result->fetch_assoc()) {
            $sections[$section['section_name']] = $section;
        }
        ?>
        <?php foreach ($sections as $section): ?>
        <section class="<?php echo strtolower(str_replace(' ', '-', $section['section_name'])); ?>">
            <?php echo $section['content']; ?>
        </section>
        <?php endforeach; ?>
        
        <?php if (empty($sections)): // Fallback content in case no sections are in the database ?>
            <section class="summary">
                <h1>Ajay Singh</h1>
                <h4>Hyderabad, India | 9000239990 | <a href="mailto:mr.ajaysingh@gmail.com">gmail</a> | <a href="https://www.linkedin.com/in/ajay-singh-ab40082/" target=new>LinkedIn</a></h4>
                <h2>Summary</h2>
                With over 19 years of IT experience, in which 5 years of US work experience, which includes SQL Development experience, I offer proven expertise in engaging with business users, analyzing requirements, and devising optimal solutions.
            </section>
            
            <section class="skills">
                <h2>Technical Skills</h2>
                <table>
                    <tbody>
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
                        <td>Data Warehouse</td>
                    </tr>
                    <tr>
                        <td>Power BI Desktop</td>
                        <td>SQL Development</td>
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
                </tbody>
            </table>
            <br>
        <!--/section>
        <section class="skills"-->
            <!--h2>Technical Skills</h2-->
            <table>
                <thead>
                    <tr>
                        <th style="width:30%">Skills</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>SQL Server Management Studio</strong></td>
                        <td>Utilized for Query Optimization, ensuring superior performance and serving as a robust backend for diverse reporting tools.</td>
                    </tr>
                    <tr>
                        <td><strong>T-SQL Developer</strong></td>
                        <td>Spearheaded optimization and creation of procedures, upgrading and enhancing existing functionalities for seamless reporting. Played a vital role in several successful reporting migrations.</td>
                    </tr>
                    <tr>
                        <td><strong>PL/SQL</strong></td>
                        <td>Optimized, maintained, and migrated procedures and queries for the development of new Crystal Reports, including applications like Clinical, Horizon Call Center, Service Ware, and Aporopos.</td>
                    </tr>
                    <tr>
                        <td><strong>MS Business Intelligence Suite</strong></td>
                        <td>Leveraged SSRS, SSIS, and SSAS for data integration, ETL, and in-depth analysis.</td>
                    </tr>
                    <tr>
                        <td><strong>SQL Server Reporting Services (SSRS)</strong></td>
                        <td>Developed a variety of reports, including Drill-through reports, Parameterized reports, Tabular reports, and Matrix reports. Migrated Crystal Reports to SSRS, creating and maintaining reports such as Invoices and Monthly Summary Reports for clients.</td>
                    </tr>
                    <tr>
                        <td><strong>Crystal Reports Enterprise XI R2</strong></td>
                        <td>Managed administration and Crystal Enterprise Server, utilizing tools like InfoView, Central Management Console, Business View Manager, Central Configuration Manager, and Instance Manager. Developed over 100 Crystal reports, optimizing SQL Stored Procedures for enhanced reporting performance and providing training to streamline tasks.</td>
                    </tr>
                    <tr>
                        <td><strong>Power BI</strong></td>
                        <td>Implemented Power BI for Dashboard Reports, Power View, gateway, and DAX. Crafted analytical dashboards featuring KPI visualizations and measures with DAX, incorporating diverse sources for extensive modeling.</td>
                    </tr>
                    <tr>
                        <td><strong>Development Technologies</strong></td>
                        <td>Extensive hands-on experience in application development using C#, HTML, CSS, VBA, MS Access, SQL, T-SQL, PL/SQL, Oracle, and SQL Server.</td>
                    </tr>
                    <tr>
                        <td><strong>Project Management and Collaboration Tools</strong></td>
                        <td>Proficient in using JIRA for Agile project management, issue tracking, and Scrum methodologies to ensure efficient project execution.</td>
                    </tr>
                    <tr>
                        <td><strong>Version Control and Collaboration</strong></td>
                        <td>Hands-on experience in GitHub with Bitbucket, Team Foundation Server (TFS), and Visual Source Safe, ensuring smooth version control and team collaboration.</td>
                    </tr>
                    <tr>
                        <td><strong>CI/CD and Automation</strong></td>
                        <td>Experience with Jenkins for continuous integration and delivery, automating build, test, and deployment processes.</td>
                    </tr>
                    <tr>
                        <td><strong>Analytical Leadership</strong></td>
                        <td>Directed quantitative and qualitative company analysis, overseeing the production of databases, models, presentations, and reports to publishable standards. Liaised with Clients/OPs Team to manage report delivery development. Provided strategic guidance for the team's output and knowledge development.</td>
                    </tr>
                    <tr>
                        <td><strong>Communication and Team Leadership</strong></td>
                        <td>Led team interactions with internal and external clients, utilizing Microsoft Office Tools (Excel, Word, PowerPoint) for presentations, training, team utilization, and higher management engagement.</td>
                    </tr>
                </tbody>
            </table>
        </section>
        <section class="skills">
            <h2>Work History</h2>
            <div class="job">
                <div class="job-title" onclick="toggleJobDetails(this)"><span class="toggle-icon">+</span>
                    Custom Database Unit Testing Framework & CI Integration | 2024 - Present
                </div>
                <div class="job-details">
                    <ul>
                        <li>Designed and implemented a custom unit test framework using T-SQL to validate stored procedures, functions, and views</li>
                        <li>Utilized SQL Server Profiler Trace to map procedures involved in functional flows, enabling precise test coverage</li>
                        <li>Enabled dynamic test data generation for each test case, supporting robust and repeatable testing</li>
                        <li>Integrated GitHub Copilot Agent with VS Code, improving efficiency in identifying test cases and automating code patterns</li>
                        <li>Leveraged Visual Studio debugging tools to trace complex SQL execution and validate results</li>
                        <li>Used Bitbucket and GitHub for source control; collaborated with the team via SourceTree, GitBash for streamlined code check-ins</li>
                        <li>Managed tasks in an Agile environment using JIRA, ensuring sprint alignment and traceability</li>
                        <li>Familiar with TEAMCITY to execute automated unit tests across multiple environments</li>
                    </ul>
                </div>
            </div>

            <div class="job">
                <div class="job-title" onclick="toggleJobDetails(this)"><span class="toggle-icon">+</span>
                    Sr. Technical Business Analyst - TechTriad (Client: Prudential Financial (NJ)) (Hyderabad) | 2021 - 2024
                </div>
                <div class="job-details">
                    <ul>
                        <li>Partnering with business areas to assess reporting, analysis and data visualization needs</li>
                        <li>Developing knowledge of Actuarial processes and recommending technology-driven process improvements</li>
                        <li>Assisting in the documentation of requirements and specifications</li>
                        <li>Partnering with technical subject matter experts to determine the optimal design</li>
                        <li>Developing reports and dashboards using Oracle BI</li>
                        <li>Writing direct sql using SQL Developer for OBI Dashboards and reports.</li>
                        <li>Performing UATs and creating test scripts</li>
                        <li>Facilitating change and innovation to legacy systems and processes</li>

                    </ul>
                </div>
            </div>

            <div class="job">
                <div class="job-title" onclick="toggleJobDetails(this)"><span class="toggle-icon">+</span>
                    Programmer Analyst - The Evolvers Group (Client: Energy Transfer, City of Arlington) (Houston, TX, USA) | 2016 - 2021
                </div>
                <div class="job-details">
                    <ul>
                        <li>Analyze, design and develop reporting applications using SSRS and Crystal reports.</li>
                        <li>Perform data analysis and develop analytical reports using Business Objects – Crystal Reports Enterprise, Microsoft Business Intelligence Suite (SSRS, SSIS, SSAS), TOAD, MS SQL Server and Oracle platforms.</li>
                        <li>Perform analysis of the Data Strategy project’s requirements</li>
                        <li>Perform reverse engineering of the various SQL Server and Oracle databases</li>
                        <li>Create and maintain SSIS packages for data transformation into data-mart for business needs.</li>
                        <li>Wrote scripts to extract and create data dictionaries from the database schema</li>
                        <li>Documented current state and capability of the software systems and databases</li>
                        <li>Designed and developed applications using C#.NET</li>
                        <li>Migrated Universe / Webi reports to Crystal Reports</li>
                        <li>Migrated Crystal reports to SSRS.</li>
                        <li>Built ad-hoc reports using SSRS.</li>
                        <li>Created various analytical dashboards using Power BI, including KPI visualizations, measures with DAX. Created data models for power pivot and power view.</li>
                        <li>Optimization of Stored Procedures and writing new procedures for reporting need.</li>
                        <li>Maintained/supported pre-production infrastructure (built on MS Azure cloud) using DevOps methodology for release management, testing, and Continuous Integration and Delivery (CI/CD). </li>

                    </ul>
                </div>
            </div>

            <div class="job">
                <div class="job-title" onclick="toggleJobDetails(this)"><span class="toggle-icon">+</span>
                    Sr. Application and Reporting Expert - C3i HealthCare Connections (Hyderabad) | 2013 - 2016
                </div>
                <div class="job-details">
                    <ul>
                        <li>Management of report requests assigned Via internal “IT Request System” along with additional responsibility of web portals.</li>
                        <li>Point of contact for C3i’s Operations Teams for the development and documentation of end user report requirements (both new and existing)</li>
                        <li>Responsible for the maintenance of several Crystal Reports and documentation library of the code/ logic used to develop Crystal reports</li>
                        <li>Involved in migrating Old Crystal reports to ILQ(Interaction level queries) and providing Business logic to Reporting team for preparing SSRS reports. ILQs are maintained in the terms of MS SQL Stored Procedures.</li>
                        <li>Participate in identifying new ways to automate reporting processes for time saving, accuracy, and cost reductions.</li>
                        <li>Worked on several client and internal projects using .net framework such as Merck Grants, ITRS (Ticketing Requesting System) web portal, Employee management System (web portal)</li>
                        <li>Worked on several Client web portals (such as, XBiotech, Merck, Novonordisk etc.) as per the requirement.</li>
                        <li>Involved in migrating SharePoint 2010 (existing portal) to SharePoint 2013, Configuration and Administration.</li>
                    </ul>
                </div>
            </div>

            <div class="job">
                <div class="job-title" onclick="toggleJobDetails(this)"><span class="toggle-icon">+</span>
                    Sr. Client Reporting Data Expert - C3i HealthCare Connections (Hyderabad) | 2012 - 2013
                </div>
                <div class="job-details">
                    <ul>
                        <li>Manage report requests assigned Via internal “IT Request System”.</li>
                        <li>Maintain documentation library of the code used to develop Crystal Reports</li>
                        <li>Provide ongoing support, training and mentorship to back office report development team on Crystal Reports and MS SQL Stored Procedures, optimization, tuning etc.</li>
                        <li>Gather requirements from Ops and Client teams for enhancing applications.</li>
                    </ul>
                </div>
            </div>

            <div class="job">
                <div class="job-title" onclick="toggleJobDetails(this)"><span class="toggle-icon">+</span>
                    Sr. Manager - Development and Analytics - C3i HealthCare Connections (Hyderabad) | 2011 - 2012
                </div>
                <div class="job-details">
                    <ul>
                        <li>Manage report requests assigned Via internal “IT Request System”.</li>
                        <li>Responsible for the development of all new Clinical, Horizon Call Center, Service Ware and Aporopos Crystal Reports using PL-SQL procedures and Oracle as backend.</li>
                        <li>Maintain documentation library of the code used to develop Crystal reports.</li>
                        <li>Gather requirements from Ops and Client teams for enhancing applications.</li>
                        <li>Participate in identifying new ways to automate reporting processes for time saving, accuracy, and cost reductions.</li>                
                    </ul>
                </div>
            </div>

            <div class="job">
                <div class="job-title" onclick="toggleJobDetails(this)"><span class="toggle-icon">+</span>
                    Manager - Development and Analytics - C3i HealthCare Connections (Hyderabad) | 2008 - 2011
                </div>
                <div class="job-details">
                    <ul>
                        <li>Manage report requests created in Lotus Notes ticket application.</li>
                        <li>Point of contact for C3i’s Operations Teams for the development and documentation of end user report requirements (both new and existing).</li>
                        <li>Develop and troubleshoot macros, coding and automating report templates requested by C3i clients or internal Operations (Crystal Reports, MS SQL Server, MS Access).</li>
                        <li>Maintain and enhance custom tools (eHOC application and back end database, QC Tool)</li>
                        <li>Provide ongoing support, training and mentorship to Back Office report development team on Crystal Reports and T-SQL and PL-SQL</li>
                        <li>Gather requirements from Ops and Client teams for enhancing applications (Grant Training Tracker)</li>
                        <li>Participate in identifying new ways to automate reporting processes for time saving, accuracy and cost reductions.</li>                
                    </ul>
                </div>
            </div>

            <div class="job">
                <div class="job-title" onclick="toggleJobDetails(this)"><span class="toggle-icon">+</span>
                    Sr. Developer Analytics - C3i HealthCare Connections (Hyderabad) | 2006 - 2008
                </div>
                <div id="job7" class="job-details">
                    <ul>
                        <li>Develop, implement, troubleshoot, enhance and provide post-production support on applications, packages, report templates, Excel Macros & MS Access programming.</li>
                        <li>Create all process/ systems and technical documentation for in-house/ third party tools and packages being used.</li>
                        <li>Provide solutions on scalability, concurrency and availability challenges.</li>
                        <li>Develop and troubleshoot macros, coding and automating report templates, maintaining a documentation library of the code/ logic used, maintaining and enhancing custom tools (eHOC application,MS SQL Server Stored Procedures and MS Access back end database, QC Tool etc).</li>
                        <li>Administration of MS SQL Server, MS Access, Crystal Reports, in-house applications</li>
                        <li>Provide support on AMS, Tech Scorecard, Grading Tool, Bonus Payout Tool, solve issues related to scalability, concurrency and availability.</li>
                        <li>Provide training to the user base on functionality of these tools – technician specs documentation.</li>
                        <li>Gather requirements from Ops and Client teams for enhancing applications</li>                
                    </ul>
                </div>
            </div>

            <div class="job">
                <div class="job-title" onclick="toggleJobDetails(this)"><span class="toggle-icon">+</span>
                    Crystal Report Specialist - C3i HealthCare Connections (Hyderabad) | 2005 - 2006
                </div>
                <div id="job8" class="job-details">
                    <ul>
                        <li>Build dynamic crystal reports report prototypes from client business and functional requirements, sample design templates, and QA MS Access database data sets.</li>
                        <li>Maintain and/or troubleshoot existing reports.</li>
                        <li>Build ad-hoc queries from client business requirements.</li>
                        <li>Build ad-hoc internal support queries to record report generation timing, duration, and frequency statistics.</li>
                        <li>Build front-end Crystal Lite Web-page reports dashboard to allow the user to:</li>
                        <li>Select a report from among cmsholding extract, horizonP, or ServicewareP database report list.</li>                    
                    </ul>
                </div>
            </div>

            <div class="job">
                <div class="job-title" onclick="toggleJobDetails(this)"><span class="toggle-icon">+</span>
                    PHP Programmer - Net Solutions India (Nasik) | 2004 - 2005
                </div>
                <div id="job9" class="job-details">
                    <ul>
                        <li>Gather requirements from clients for a given module.</li>
                        <li>Maintain existing WebPages as requests come from the Project manager.</li>
                        <li>Create/Add CMS (Content Management System) into the admin section for the websites.</li>
                        <li>Add Payment Gateway systems to the websites.</li>                    
                    </ul>
                </div>
            </div>

        </section>

        <section class="skills">
            <h2>Education</h2>
            MCA From Vinayak Mission Research Foundation Deemed University, Chennai, India.<br>
            Bachelor of Commerce from Tilka Manjhi Bhagalpur University, Bhagalpur, India.
        </section>
    <?php endif; ?>
    </main>
    <script src="js/resume.js"></script>
</body>
</html>

