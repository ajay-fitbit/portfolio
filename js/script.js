// script.js
document.querySelectorAll('.floating-menu a').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href')).scrollIntoView({
            behavior: 'smooth'
        });
    });
});

function openSidebar() {
    document.getElementById("sidebar").classList.add("open");
}

function closeSidebar() {
    document.getElementById("sidebar").classList.remove("open");
}

 /* function toggleDetails(jobId) {
    var details = document.getElementById(jobId);
    //const icon = jobId.querySelector('.toggle-icon');
    if (details.style.display === "none" || details.style.display === "") {
        details.style.display = "block";
        //icon.textContent = "-";
        //icon.style.transform = "rotate(45deg)"; // Rotate icon for minus
    } else {
        details.style.display = "none";
        //icon.textContent = "+";
        //icon.style.transform = "rotate(0deg)"; // Reset icon rotation
    }
}  */
