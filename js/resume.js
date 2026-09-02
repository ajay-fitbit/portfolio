// Function to toggle job details
function toggleJobDetails(element) {
    const details = element.nextElementSibling;
    const icon = element.querySelector('.toggle-icon');

    if (details.style.display === "block") {
        details.style.display = "none";
        icon.textContent = "+";
        icon.style.transform = "rotate(0deg)"; // Reset icon rotation
    } else {
        details.style.display = "block";
        icon.textContent = "-";
        icon.style.transform = "rotate(45deg)"; // Rotate icon for minus
    }
}