function toggleMenu() {
    const hiddenMenu = document.getElementById('hiddenMenu');
    hiddenMenu.style.display = hiddenMenu.style.display === 'block' ? 'none' : 'block'; // Toggle hidden menu
}

/* function toggleDropdown() {
    const dropdownContent = document.getElementById('dropdownContent');
    dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block'; // Toggle dropdown
} */

function closeMenu() {
    const hiddenMenu = document.getElementById('hiddenMenu');
    hiddenMenu.style.display = 'none'; // Close the menu after clicking an item
}

// Close the dropdown menu on window resize
window.addEventListener('resize', function() {
    const hiddenMenu = document.getElementById('hiddenMenu');
    hiddenMenu.style.display = 'none'; // Hide the menu on resize
});