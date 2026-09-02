<?php
include('includes/config.php');

// Get projects
$projects_sql = "SELECT * FROM projects ORDER BY display_order";
$projects_result = $conn->query($projects_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/chatbot.css">
    <style>
        
        h1 {
            color: #333;
        }
        h2 {
            color: #4a90e2;
        }
        p {
            line-height: 1.6;
        }
        ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .project-container {
            background: white;
            padding: 20px;
            border-radius: 7px;
            box-shadow: 0 7px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px; /* Space between projects */
        }
        details {
            margin-bottom: 10px; /* Space between collapsible sections */
            border-radius: 5px;
        }
        summary {
            cursor: pointer;
            font-weight: bold;
            outline: none; /* Remove outline on focus */
        }
        summary::-webkit-details-marker {
            display: none; /* Hide default marker */
        }
        summary::before {
            content: '▶'; /* Custom marker */
            display: inline-block;
            margin-right: 5px;
            transition: transform 0.2s; /* Smooth transition */
        }
        details[open] summary::before {
            transform: rotate(90deg); /* Rotate marker when open */
        }
    </style>
</head>
<body>
<section class="download">
    <a href="download.php?file=resume/Ajay_Singh_resume_with_projects.pdf" download class="download-button">
        <i class="fa fa-file-pdf-o" aria-hidden="true"></i> Download PDF
    </a>
    <!-- <a href="download.php?file=resume/SQL_Developer.docx" download class="download-button">
        <i class="fa fa-file-word-o" aria-hidden="true"></i> Download Word
    </a> -->
</section>
<?php while ($project = $projects_result->fetch_assoc()): ?>
<div class="project-container">
    <details>
        <summary><?php echo htmlspecialchars($project['title']); ?></summary>
            <h2></h2>
            <p><strong>Technologies Used:</strong> <?php echo htmlspecialchars($project['technologies']); ?></p>
            <div class="project-description">
                <?php echo $project['description']; ?>
            </div>
            <?php if($project['date_completed']): ?>
            <div class="date-completed">
                Completed: <?php echo date('F Y', strtotime($project['date_completed'])); ?>
            </div>
            <?php endif; ?>
    </details>
</div>

<?php endwhile; ?>

</body>
</html>
      <!--   <h2 id="evolvers"></h2>

        <p><strong>Technologies Used:</strong> SSRS, Crystal Reports, SQL Server, T-SQL, SQL Server Management Studio</p>
        
        <h4>Project Description:</h4>
        <p>
            In a transformative initiative at Energy Transfer, I spearheaded a comprehensive project focusing on the development and enhancement of SSRS reports, migration of legacy Crystal Reports to SSRS, and optimization of SQL Server databases to support advanced reporting capabilities. The project aimed to elevate the reporting framework, ensuring robust performance, enhanced data visualization, and streamlined report maintenance to support decision-making processes effectively.
        </p>

        <h4>Responsibilities:</h4>
        <ul>
            <li><strong>SSRS Report Development and Enhancement:</strong>
                <ul>
                    <li>Designed and developed a wide array of SSRS reports, including complex drill-through, parameterized, tabular, and matrix reports, to meet diverse business needs across departments.</li>
                    <li>Enhanced existing SSRS reports for improved performance and user experience by optimizing T-SQL queries and utilizing advanced SSRS features for dynamic data presentation.</li>
                    <li>Collaborated closely with business analysts and stakeholders to gather and translate business requirements into effective reporting solutions, ensuring alignment with organizational objectives.</li>
                </ul>
            </li>
            <li><strong>Crystal to SSRS Migration:</strong>
                <ul>
                    <li>Led the migration of over 100+ legacy Crystal Reports to SSRS, carefully assessing each report to ensure a seamless transition without data loss or functionality compromise.</li>
                    <li>Developed a migration strategy that included a comprehensive review of existing report logic, data sources, and visualization requirements to ensure accurate replication in SSRS.</li>
                    <li>Implemented best practices for SSRS report design and development, establishing a robust framework for future reporting needs and facilitating easier report maintenance.</li>
                </ul>
            </li>
            <li><strong>T-SQL Procedure Performance Optimization:</strong>
                <ul>
                    <li>Conducted thorough performance analysis and optimization of SQL queries underlying SSRS reports, significantly reducing report generation times and enhancing user satisfaction.</li>
                    <li>Utilized SQL Server Management Studio (SSMS) for query optimization, indexing strategies, and database performance tuning.</li>
                </ul>
            </li>
            <li><strong>Stakeholder Engagement and Training:</strong>
                <ul>
                    <li>Provided comprehensive training and documentation to end-users and the IT team on navigating and utilizing the new SSRS reporting platform.</li>
                    <li>Acted as a key point of contact for report-related queries, offering ongoing support and enhancements based on user feedback and evolving business requirements.</li>
                </ul>
            </li>
        </ul>

        <h4>Achievements:</h4>
        <ul>
            <li>Successfully migrated 100+ Crystal Reports to SSRS and optimized SQL Server databases, leading to a 40% increase in report generation efficiency and significantly enhancing report interaction and accessibility for end-users.</li>
            <li>Established standardized reporting templates and best practices, reducing future report development time by 30% and ensuring consistency across the reporting ecosystem.</li>
            <li>Recognized by senior management for exceptional project leadership and contributions to enhancing the organization's data reporting and analytics capabilities.</li>
            <li>This project underscored my expertise in SQL Server and SSRS, showcasing my ability to manage complex database environments, develop and optimize advanced reporting solutions, and drive significant improvements in business intelligence and analytics practices.</li>
        </ul>
    </details>
</div>

<div class="project-container">
    <details>
        <summary>Crystal Reports XIR2 Maintenance and .Net Projects with SharePoint Migration</summary>
        <h2 id="c3i1"></h2>
        <p><strong>Technologies Used:</strong> Crystal Reports, MS SQL, .Net Framework, SharePoint</p>        
        <h4>Project Description:</h4>
        <p>
            In a multifaceted role at C3i, I managed and maintained Crystal Reports, developed and documented end-user report requirements, and migrated legacy reports to Interaction Level Queries (ILQ) using MS SQL Stored Procedures. Additionally, I worked on various .Net projects, including client web portals and internal systems, and participated in the migration and administration of SharePoint portals.
        </p>

        <h4>Responsibilities:</h4>
        <ul>
            <li><strong>Crystal Reports XIR2 Maintenance and Automation:</strong>
                <ul>
                    <li>Management of report requests assigned via internal “IT Request System” along with additional responsibility of web portals.</li>
                    <li>Point of contact for C3i’s Operations Teams for the development and documentation of end-user report requirements (both new and existing).</li>
                    <li>Responsible for the maintenance of several Crystal Reports and documentation library of the code/logic used to develop Crystal Reports.</li>
                    <li>Involved in migrating old Crystal Reports to ILQ (Interaction Level Queries) and providing business logic to the reporting team for preparing SSRS reports. ILQs are maintained in the terms of MS SQL Stored Procedures.</li>
                    <li>Participated in identifying new ways to automate reporting processes for time-saving, accuracy, and cost reductions.</li>
                </ul>
            </li>
            <li><strong>.Net Framework Client and Internal Projects:</strong>
                <ul>
                    <li>Worked on several client and internal projects using .Net Framework such as Merck Grants, ITRS (Ticketing Requesting System) web portal, Employee Management System (web portal).</li>
                    <li>Worked on several client web portals (such as XBiotech, Merck, Novonordisk, etc.) as per the requirement.</li>
                </ul>
            </li>
            <li><strong>SharePoint Portal Migration and Administration:</strong>
                <ul>
                    <li>Involved in migrating SharePoint 2010 (existing portal) to SharePoint 2013, configuration, and administration.</li>
                </ul>
            </li>
        </ul>
    </details>
</div>

<div class="project-container">
    <details>
        <summary>Crystal Reports Development and MS SQL Optimization</summary>
        <h2 id="c3i2"></h2>
        <p><strong>Technologies Used:</strong> IT Request System, Crystal Reports, MS SQL Server, T-SQL, Stored Procedures</p>
        
        <h4>Project Description:</h4>
        <p>
            The project involved managing and developing Crystal Reports, maintaining documentation, and optimizing SQL Stored Procedures. The key focus was on ensuring timely delivery of report requests through the IT Request System, improving the performance of SQL queries, and mentoring the back-office report development team.
        </p>

        <h4>Responsibilities:</h4>
        <ul>
            <li><strong>Report Request Management and Documentation:</strong>
                <ul>
                    <li>Managed report requests assigned via the internal "IT Request System," ensuring timely delivery of Crystal Reports.</li>
                    <li>Maintained a comprehensive documentation library of the code used to develop and enhance Crystal Reports, ensuring ease of reference and reuse.</li>
                </ul>
            </li>
            <li><strong>SQL Procedure Optimization and Performance Tuning:</strong>
                <ul>
                    <li>Provided optimization and performance tuning for MS SQL Stored Procedures to ensure reports were generated efficiently and accurately.</li>
                    <li>Enhanced SQL queries and T-SQL code to improve overall database performance and reporting speed.</li>
                </ul>
            </li>
            <li><strong>Team Training and Mentorship:</strong>
                <ul>
                    <li>Provided ongoing support, training, and mentorship to the back-office report development team on Crystal Reports development and MS SQL Stored Procedures, including best practices in query optimization and performance tuning.</li>
                </ul>
            </li>
            <li><strong>Requirements Gathering and Application Enhancement:</strong>
                <ul>
                    <li>Collaborated with Ops and Client teams to gather requirements and develop enhancements for existing reporting applications, ensuring the system met evolving business needs.</li>
                </ul>
            </li>
        </ul>
    </details>
</div>

<div class="project-container">
    <details>
        <summary>Crystal Reports Development for Clinical, Horizon Call Center, Service Ware, and Aporopos</summary>
        <h2 id="c3i3"></h2>
        <p><strong>Technologies Used:</strong> Crystal Reports, PL/SQL, Oracle, SQL Server</p>
        
        <h4>Project Description:</h4>
        <p>
            This project focused on the development of new Crystal Reports for Clinical, Horizon Call Center, Service Ware, and Aporopos, using PL/SQL procedures and Oracle as the backend. The aim was to improve report efficiency, maintain a structured documentation library, and implement automated solutions to reduce manual effort.
        </p>

        <h4>Responsibilities:</h4>
        <ul>
            <li><strong>Crystal Reports Development:</strong>
                <ul>
                    <li>Developed new Crystal Reports for key applications, including Clinical, Horizon Call Center, Service Ware, and Aporopos, leveraging PL/SQL procedures with Oracle as the backend.</li>
                    <li>Ensured reports were accurate, timely, and met the needs of business stakeholders.</li>
                </ul>
            </li>
            <li><strong>Documentation Maintenance:</strong>
                <ul>
                    <li>Created and maintained a comprehensive documentation library of the code used in the development of Crystal Reports, ensuring smooth knowledge transfer and ease of future maintenance.</li>
                </ul>
            </li>
            <li><strong>Requirements Gathering and Application Enhancements:</strong>
                <ul>
                    <li>Partnered with Operations and Client teams to gather detailed requirements for report enhancements and new reporting functionalities, ensuring the application was continuously improved based on user feedback and business needs.</li>
                </ul>
            </li>
            <li><strong>Process Automation and Optimization:</strong>
                <ul>
                    <li>Participated in initiatives to identify and implement new automation solutions to streamline reporting processes, enhance accuracy, and achieve significant time and cost savings.</li>
                </ul>
            </li>
        </ul>
    </details>
</div>

<div class="project-container">
    <details>
        <summary>Report Management, Data Warehouse Integration, and Process Automation</summary>
        <h2 id="c3i4"></h2>
        <p><strong>Technologies Used:</strong> Lotus Notes, Crystal Reports, MS SQL Server, MS Access, T-SQL, PL/SQL, Data Warehouse, Snowflake</p>
        
        <h4>Project Description:</h4>
        <p>
            This project focused on managing report requests through the Lotus Notes ticket application, integrating with the Data Warehouse, and automating various reporting templates. The objective was to streamline reporting processes, enhance accuracy, and reduce operational costs. Additionally, the project involved maintaining and improving custom tools and applications such as the eHOC application and QC Tool, while ensuring data consistency through Snowflake integration.
        </p>

        <h4>Responsibilities:</h4>
        <ul>
            <li><strong>Report Request Management and Data Warehouse Integration:</strong>
                <ul>
                    <li>Managed report requests generated via Lotus Notes ticketing system, ensuring alignment with data warehouse solutions such as Snowflake for seamless reporting.</li>
                    <li>Acted as the main point of contact for C3i’s Operations Teams, handling the development and documentation of end-user report requirements for both new and existing reports, integrated with data.</li>
                </ul>
            </li>
            <li><strong>Report Development, Automation, and Data Extraction:</strong>
                <ul>
                    <li>Developed and troubleshot macros, code, and automated report templates from data stored in MS SQL Server and MS Access as requested by clients or internal operations.</li>
                    <li>Automated and optimized existing report templates, sourcing data from the Data Warehouse, leading to improved accuracy and time savings.</li>
                </ul>
            </li>
            <li><strong>Tool Maintenance and Data Consistency:</strong>
                <ul>
                    <li>Maintained and enhanced custom tools like the eHOC application and back-end databases, ensuring data consistency across platforms, including Snowflake and SQL Server databases.</li>
                    <li>Gathered requirements from Ops and Client teams to enhance applications such as the Grant Training Tracker, leveraging data from the Data Warehouse for real-time reporting.</li>
                </ul>
            </li>
            <li><strong>Team Support and Training:</strong>
                <ul>
                    <li>Provided ongoing support, training, and mentorship to the Back Office report development team on Crystal Reports, T-SQL, PL/SQL, and Data Warehouse concepts such as Snowflake integration.</li>
                </ul>
            </li>
            <li><strong>Process Automation and Improvements:</strong>
                <ul>
                    <li>Identified new opportunities to automate reporting processes by leveraging Data Warehouse solutions for time savings, increased data accuracy, and reduced operational costs.</li>
                </ul>
            </li>
        </ul>

        <h4>Achievements:</h4>
        <ul>
            <li>Successfully automated several reporting templates, using data from the Snowflake Data Warehouse, significantly reducing manual effort and improving data accuracy.</li>
            <li>Enhanced custom tools integrated with Snowflake, leading to more efficient processes for both internal and client teams.</li>
            <li>Provided training and mentorship that improved the technical skills of the Back Office team and ensured a smooth integration with Snowflake.</li>
        </ul>
    </details>
</div>

<div class="project-container">
    <details>
        <summary>Application and Report Template Development, Troubleshooting, and Post-Production Support</summary>
        <h2 id="c3i5"></h2>
        <p><strong>Technologies Used:</strong> MS SQL Server, MS Access, Excel Macros, Crystal Reports</p>
        
        <h4>Project Description:</h4>
        <p>
            This project focused on the development, implementation, and support of custom applications, report templates, and tools. The aim was to enhance in-house and third-party tools, address scalability, concurrency, and availability challenges, and provide end-user training and technical documentation.
        </p>

        <h4>Responsibilities:</h4>
        <ul>
            <li><strong>Application and Report Template Development:</strong>
                <ul>
                    <li>Developed, implemented, and enhanced various applications, report templates, Excel Macros, and MS Access programming solutions to streamline business processes.</li>
                    <li>Automated report templates and troubleshooting logic for optimizing report generation.</li>
                </ul>
            </li>
            <li><strong>Documentation and Technical Writing:</strong>
                <ul>
                    <li>Created detailed process and technical documentation for all in-house and third-party tools, including application code and logic to ensure consistency and future maintenance ease.</li>
                    <li>Established and maintained a documentation library for macros, coding, and system enhancements.</li>
                </ul>
            </li>
            <li><strong>Solutions for Scalability, Concurrency, and Availability:</strong>
                <ul>
                    <li>Provided solutions for critical issues related to scalability, concurrency, and availability across various systems, ensuring system reliability and performance.</li>
                </ul>
            </li>
            <li><strong>Custom Tools Maintenance and Support:</strong>
                <ul>
                    <li>Managed and enhanced custom tools, including eHOC application, MS SQL Server Stored Procedures, MS Access back-end database, QC Tool, and others, providing ongoing post-production support.</li>
                    <li>Administered MS SQL Server, MS Access, and Crystal Reports for maintaining operational efficiency and resolving technical issues.</li>
                </ul>
            </li>
            <li><strong>End-User Support and Training:</strong>
                <ul>
                    <li>Delivered training sessions and technical documentation to users on the functionality and usage of tools, including AMS, Tech Scorecard, Grading Tool, and Bonus Payout Tool.</li>
                    <li>Acted as a key point of contact for all application-related queries and enhancements from the Operations and Client teams.</li>
                </ul>
            </li>
        </ul>
    </details>
</div>

<div class="project-container">
    <details>
        <summary>Dynamic Crystal Reports Prototyping and Ad-Hoc Query Development</summary>
        <h2 id="c3i6"></h2>
        <p><strong>Technologies Used:</strong> Crystal Reports, MS Access, SQL</p>
        
        <h4>Project Description:</h4>
        <p>
            Developed dynamic Crystal Reports prototypes and ad-hoc queries based on client business requirements, while maintaining and troubleshooting existing reports.
        </p>

        <h4>Responsibilities:</h4>
        <ul>
            <li>Built dynamic Crystal Reports prototypes from client requirements and sample design templates.</li>
            <li>Developed ad-hoc queries for both client needs and internal reporting support, tracking report generation statistics.</li>
            <li>Created a front-end Crystal Lite Web-page dashboard, enabling users to select and run reports from multiple databases like cmsholding, horizonP, and ServicewareP.</li>
        </ul>
    </details>
</div>

<div class="project-container">
    <details>
        <summary>Website Development and Maintenance</summary>
        <h2 id="php1"></h2>
        <h3></h3>
        <p><strong>Technologies Used:</strong>PHP, MySQL, CMS, Payment Gateway Integration</p>
        
        <h4>Project Description:</h4>
        <p>
            Developed and maintained multiple websites, including 
            <strong>newtrendmosaic.com</strong>, 
            <strong>flywire.com.au</strong>, 
            <strong>webdesigners.net.au</strong>, and 
            <strong>phoenixtraining.info</strong>. The focus was on integrating Content Management Systems (CMS) and implementing payment gateway systems to facilitate online transactions.
        </p>
        
        <h4>Responsibilities:</h4>
        <ul>
            <li><strong>Requirements Gathering:</strong> Collaborated with clients to gather requirements and develop specific modules tailored to their needs.</li>
            <li><strong>Website Maintenance:</strong> Regularly maintained and updated existing web pages based on requests from the Project Manager.</li>
            <li><strong>CMS Implementation:</strong> Implemented and enhanced CMS functionalities in the admin sections to streamline content management processes.</li>
            <li><strong>Payment Gateway Integration:</strong> Integrated various payment gateway systems into client websites, enabling secure online transactions.</li>
        </ul>
    </details>
</div>

<div class="project-container">
    <details>
        <summary>Database Migration Project</summary>
        <h2 id="database">Situation</h2>
        <p>The project involved upgrading our database system, requiring the conversion of data types from <code>smallint</code> and <code>tinyint</code> to <code>Int</code> across roughly <strong>25-30 tables</strong>.</p>

        <h2>Task</h2>
        <p>My primary task was to manage the conversion process while ensuring <strong>data integrity</strong> and maintaining all existing constraints and functionalities associated with these data types.</p>

        <h2>Action</h2>
        <ul>
            <li><strong>Automated Identification:</strong> Developed a procedure to automatically identify columns with <code>smallint</code> and <code>tinyint</code> data types.</li>
            <li><strong>Constraint Handling:</strong>
                <ul>
                    <li>Stored all constraints and default values in a temporary table.</li>
                    <li>Planned out a strategy to temporarily drop these constraints to enable the data type conversion.</li>
                </ul>
            </li>
            <li><strong>Data Type Conversion:</strong> Successfully converted the data types to <code>Int</code>.</li>
            <li><strong>Restoration of Constraints:</strong> Post-conversion, re-implemented all the original constraints and default settings from the temporary storage.</li>
        </ul>

        <h2>Result</h2>
        <p>The migration was accomplished seamlessly with all data types correctly converted and constraints restored. This process greatly reduced manual effort and time, demonstrating scalability for future database upgrades. The project highlighted my capabilities in handling complex database migrations, problem-solving, and ensuring data integrity during significant system changes.</p>
    </details>
</div>

<div class="project-container">
    <details>
        <summary>OBIE Dashboard Project</summary>
        <h2 id="OBIE">Project Explanation</h2>
        <p>In my recent role, I worked on an Oracle Business Intelligence (OBI) dashboard project aimed at enhancing the data analysis and reporting capabilities for our actuarial processes. The primary objective was to collaborate closely with various business units to gain a comprehensive understanding of their reporting needs. Here’s a breakdown of the key phases and outcomes of the project:</p>

        <h2>Steps Followed</h2>
        <ul>
            <li><strong>Requirement Gathering:</strong>
                <ul>
                    <li>Conducted workshops and interviews with stakeholders to identify specific reporting needs.</li>
                    <li>Documented user requirements in a structured format for clarity and alignment.</li>
                </ul>
            </li>
            <li><strong>Technology Recommendations:</strong>
                <ul>
                    <li>Proposed technology-driven improvements to streamline reporting processes.</li>
                    <li>Leveraged OBI’s advanced data visualization tools to enhance data interaction and accessibility.</li>
                </ul>
            </li>
            <li><strong>Technical Design:</strong>
                <ul>
                    <li>Created detailed data models and established the architecture for dashboard development.</li>
                    <li>Ensured scalability and flexibility in design to accommodate future enhancements.</li>
                </ul>
            </li>
            <li><strong>Dashboard Development:</strong>
                <ul>
                    <li>Built interactive dashboards that provided real-time insights into key performance indicators (KPIs).</li>
                    <li>Designed visualizations to highlight trends and patterns, facilitating quick decision-making.</li>
                </ul>
            </li>
            <li><strong>Data Governance and Security:</strong>
                <ul>
                    <li>Implemented best practices to ensure data accuracy and security.</li>
                    <li>Established user access controls to protect sensitive information.</li>
                </ul>
            </li>
            <li><strong>Testing and Validation:</strong>
                <ul>
                    <li>Conducted rigorous testing to validate the accuracy and performance of the dashboards.</li>
                    <li>Gathered user feedback and made iterative improvements to enhance usability.</li>
                </ul>
            </li>
        </ul>

        <h2>Outcomes</h2>
        <ul>
            <li>Optimized reporting processes, significantly reducing analysis time for actuarial teams.</li>
            <li>Facilitated innovation in legacy systems, enabling a smooth transition to a modern reporting environment.</li>
            <li>Empowered teams with tools for data-driven decision-making, enhancing overall operational efficiency.</li>
        </ul>

        <p>Overall, this OBIE dashboard project not only enhanced our actuarial reporting capabilities but also laid the groundwork for future scalability and improvements in our data practices. I am proud to have contributed to this initiative, which has had a lasting positive impact on the organization.</p>
    </details>
</div>
<div class="project-container">
    <details>
        <summary>Energy Volume Management Dashboard (EVMD)</summary>
        <h2 id="powerBI">Project Explanation</h2>
        <p>During my tenure at <strong>Energy Transfer</strong>, I took the lead on a Power BI project aimed at developing the Energy Volume Management Dashboard (EVMD). This dashboard was designed to provide real-time insights into energy scheduling, nominations, and consumption, leveraging data from our centralized data warehouse.</p>

        <h2>Project Objectives</h2>
        <ul>
            <li>Create a visual representation of key metrics related to Scheduled Volume, Nominated Volume, and Actual Volume.</li>
            <li>Enhance decision-making capabilities by providing easy access to Forecasted Volume and other critical performance indicators.</li>
            <li>Improve reporting efficiency and accuracy for compliance and operational analysis.</li>
        </ul>

        <h2>Project Overview</h2>
        <p>The project involved connecting Power BI to our centralized data warehouse, which was populated with data through a robust ETL process using SSIS. This integration allowed us to pull in clean, transformed data for analysis and visualization.</p>

        <h2>Key Components of the Project</h2>
        <ol>
            <li><strong>Data Connection:</strong> Established a direct connection between Power BI and the data warehouse, which housed critical tables such as:
                <ul>
                    <li>Energy Transaction Records: Containing details of energy trades, including Nominated Volume and Actual Volume.</li>
                    <li>Load Profiles: Storing historical load data necessary for demand forecasting and analysis.</li>
                    <li>Regulatory Compliance Records: Tracking compliance with energy regulations, including capacity reservations and audit results.</li>
                </ul>
            </li>
            <li><strong>Data Modeling:</strong> Created a data model within Power BI that effectively represented relationships between different tables, allowing for:
                <ul>
                    <li>DAX Calculations: Implementing measures to calculate KPIs, including:
                        <ul>
                            <li>Energy Efficiency Ratio</li>
                            <li>Average Capacity Factor</li>
                            <li>Balancing Volume</li>
                            <li>Carbon Emissions</li>
                        </ul>
                    </li>
                    <li>Hierarchies: Establishing hierarchies for time and geography to facilitate drill-down capabilities in reports.</li>
                </ul>
            </li>
            <li><strong>Dashboard Development:</strong> Designed an interactive dashboard featuring various visualizations to present data insightfully:
                <ul>
                    <li>Line Charts to visualize trends in Scheduled Volume versus Actual Volume over time.</li>
                    <li>Bar Charts to compare Nominated Volume against forecasted and actual values.</li>
                    <li>Card Visuals to display key KPIs.</li>
                </ul>
            </li>
            <li><strong>User Interaction and Filtering:</strong> Implemented slicers and filters to allow users to view data by specific parameters.</li>
            <li><strong>Data Refresh and Automation:</strong> Configured scheduled refreshes in Power BI to ensure the dashboard reflected the most current data.</li>
            <li><strong>Collaboration and Reporting:</strong> Collaborated with various stakeholders to gather requirements and ensure the dashboard met their needs.</li>
        </ol>

        <h2>Outcome and Impact</h2>
        <p>The EVMD project significantly enhanced our ability to analyze and visualize energy data. By utilizing Power BI and leveraging the structured data from our data warehouse, we created a tool that improved operational transparency and facilitated data-driven decision-making. The dashboard reduced the time spent on manual reporting by over 50%, allowing teams to focus on analysis rather than data gathering.</p>
    </details>
</div>
<div class="project-container">
    <details>
        <summary>Energy Volume Management System (EVMS)</summary>
        <h2 id="ETL">Project Explanation</h2>
        <p>During my tenure at <strong>Energy Transfer</strong>, I had the opportunity to lead an SSIS project aimed at developing the Energy Volume Management System (EVMS). This system was designed to effectively manage and optimize energy scheduling, nominations, and delivery while ensuring compliance with regulatory standards.</p>

        <h2>Project Objectives</h2>
        <ul>
            <li>Streamline the processes for managing Scheduled Volume, Nominated Volume, and Actual Volume to ensure accurate energy delivery.</li>
            <li>Enhance the accuracy of Forecasted Volume to better align supply with demand.</li>
            <li>Improve reporting capabilities to facilitate better decision-making and regulatory compliance.</li>
        </ul>

        <h2>Project Overview</h2>
        <p>The project involved creating a series of SSIS packages to automate data extraction, transformation, and loading (ETL) processes from various sources, including operational databases, market data feeds, and external APIs.</p>

        <h2>Key Components of the Project</h2>
        <ol>
            <li><strong>Data Extraction:</strong> We extracted data from multiple sources, including:
                <ul>
                    <li>Transmission and distribution databases for Scheduled Volume and Actual Volume.</li>
                    <li>Market data sources for Nominated Volume and Market Clearing Price.</li>
                    <li>Forecasting tools to gather Forecasted Volume data.</li>
                </ul>
            </li>
            <li><strong>Data Transformation:</strong> Using SSIS, I implemented various transformations to cleanse and aggregate the data:
                <ul>
                    <li>Merging data from different sources to create a comprehensive view of energy transactions.</li>
                    <li>Calculating Balancing Volume to ensure that our energy supply met the demand accurately.</li>
                    <li>Creating derived fields to capture Delivery Points and Transmission Losses for better reporting.</li>
                </ul>
            </li>
            <li><strong>Data Loading:</strong> The transformed data was then loaded into our centralized data warehouse using incremental loading to ensure that only new or changed records were processed.</li>
            <li><strong>Automation and Scheduling:</strong> I scheduled the SSIS packages using SQL Server Agent to automate the data refresh cycle.</li>
            <li><strong>Reporting and Analytics:</strong> Collaborated with the business intelligence team to develop dashboards and reports that provided insights into energy consumption and market performance.</li>
        </ol>

        <h2>Outcome and Impact</h2>
        <p>The EVMS project significantly improved our operational efficiency and data accuracy. By automating the ETL processes and utilizing incremental loading, we reduced the time spent on manual data entry and reporting by over 40%. This allowed our team to focus on strategic decision-making rather than operational tasks.</p>
    </details>
</div>

</body>
</html> -->
