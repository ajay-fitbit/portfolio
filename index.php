<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/burgerMenu.css">
    <link rel="stylesheet" href="css/float.css">
    <link rel="stylesheet" href="css/model.css">
    <link rel="stylesheet" href="css/chatbot.css">
    <!-- <link rel="stylesheet" href="css/resume.css"> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <title>Ajay Singh - Portfolio</title>
    <script src="api/config.php"></script>
</head>
<body>
    <!-- Burger Menu -->
    <div class="burger-menu" onclick="toggleMenu()">
        &#9776; <!-- This is the hamburger icon -->
    </div>

    <!-- Hidden Menu for Burger -->
    <div class="dropdown-content" id="hiddenMenu">
        <a href="#about" onclick="openModal('aboutModal')">About</a>
        <a href="#resume" onclick="openModal('resumeModal')">Resume</a>
        <a href="#projects" onclick="openModal('projectModal')">Projects</a>
    </div>

    <!-- Floating Menu -->
    <div id="floating-menu" class="floating-menu">
        <div class="floating-button" onclick="openModal('aboutModal')"> About </div>
        <div class="floating-button" onclick="openModal('resumeModal')"> Resume</div>
        <div class="floating-button" onclick="openModal('projectModal')">Projects</div>
        <!-- <ul>
            <li><div class="floating-button" onclick="openModal('aboutModal')"> About </div></li>
            <li><div class="floating-button" onclick="openModal('resumeModal')"> Resume</div></li>
            <li><div class="floating-button" onclick="openModal('projectModal')">Projects</div></li>
        </ul> -->
    </div>    
    <?php
    include('includes/config.php');
    
    // Get main content
    $main_sql = "SELECT * FROM content_sections WHERE section_name = 'main'";
    $main_result = $conn->query($main_sql);
    $main_content = $main_result->fetch_assoc();
    ?>
    <!-- Main Content -->
    <div class="content">
        <section id="about">
            <img src="image/me.png" alt="Ajay Singh" class="profile-image">
            <p style="font-size:25px;"><?php echo isset($main_content['title']) ? htmlspecialchars($main_content['title']) : 'Hello! I\'m Ajay Singh'; ?></p>
            <div class="intro-text">
                <?php 
                if (isset($main_content['content'])) {
                    echo $main_content['content'];
                } else {
                    // Default content as fallback
                    echo '<p>A passionate IT professional with close to <strong>19 years</strong> of experience in various domains, including <strong>Pharma</strong>, <strong>Energy</strong>, and <strong>Insurance</strong>. I have nearly <strong>6 years</strong> of experience working in the <strong>United States</strong>, primarily focusing on building <strong>Business Analytics applications</strong>.
                    My expertise includes working with technologies such as <strong>Microsoft Business Intelligence Suite</strong>, <strong>SSRS</strong>, <strong>SSIS</strong>, and occasionally <strong>SSAS</strong> for <strong>OLAP applications</strong>. I have also leveraged <strong>Power BI</strong> to create insightful dashboards. Throughout my career, I have collaborated closely with <strong>DBAs</strong> to design and establish connections between <strong>Transactional Databases</strong> and <strong>Data Warehouses</strong> for reporting purposes. I enjoy engaging with business users to translate their requirements into comprehensive functional and technical documents, ensuring clarity and alignment in project objectives. As a team lead, I managed the <strong>Backoffice Reporting Team</strong>, training members on <strong>SQL</strong> and <strong>Crystal Reports</strong> to enhance the quality of work and optimize delivery times.            
                    Additionally, I have been part of projects involving the migration of numerous <strong>Crystal Reports</strong> to <strong>SSRS</strong>, which included procedure conversion and creation. My tools of choice for project management include <strong>JIRA</strong> for tracking progress in an <strong>Agile framework</strong> and <strong>TFS</strong> for version control and issue resolution. I have also utilized <strong>Jenkins</strong> for project integration.</p>';
                }
                ?>
            </div>
        </section>
    </div>
    <!-- About Modal Starts -------------------------------------------------------------------------------------------->
    <div id="aboutModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('aboutModal')">&times;</span>
            <?php  
            include("about.php"); 
            ?> 
        </div>
    </div>
    <!-- About Modal Ends -------------------------------------------------------------------------------------------->

    <!-- Resume Modal Starts -------------------------------------------------------------------------------------------->
    <div id="resumeModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('resumeModal')">&times;</span>
            <?php  
            include("resume.php"); 
            ?> 
        </div>
    </div>
    <!-- Resume Modal Ends -------------------------------------------------------------------------------------------->

    <!-- Project Modal Starts -------------------------------------------------------------------------------------------->
    <div id="projectModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('projectModal')">&times;</span>
            <h2>Projcts</h2>
            <?php  
            include("projects.php"); 
            ?> 
        </div>
    </div>
    <!-- Project Modal Ends -------------------------------------------------------------------------------------------->

    <footer>
        <p>        
        <a href="download.php?file=resume/ajay_singh_resume.pdf" target="_blank">
            <img src="image/resume.png" alt="Resume Icon" class="footer-icon">Resume
        </a>
        <a class="badge-base__link LI-simple-link" href="https://in.linkedin.com/in/ajay-singh-ab40082?trk=profile-badge" target="_blank">
            <img src="image/linkedin-icon.png" alt="LinkedIn Icon" class="footer-icon">Linked<span class="blue-background">in</span>
        </a>
        <a href="mailto:mr.ajaysingh@gmail.com">
            <img src="image/email-icon.png" alt="Email Icon" class="footer-icon">Email
        </a>
        </p>
    </footer>

    <!-- <div class="badge-base LI-profile-badge" data-locale="en_US" data-size="medium" data-theme="light" data-type="HORIZONTAL" data-vanity="ajay-singh-ab40082" data-version="v1">
        <a class="badge-base__link LI-simple-link" href="https://in.linkedin.com/in/ajay-singh-ab40082?trk=profile-badge"></a>
    </div> -->

    <script src="js/script.js"></script>
    <script src="js/model.js"></script>
    <script src="js/burgerMenu.js"></script>
    <script src="js/chatbot.js?v=2.8"></script>
    <!-- <script src="https://platform.linkedin.com/badges/js/profile.js" async defer type="text/javascript"></script>     -->
</body>
</html>
