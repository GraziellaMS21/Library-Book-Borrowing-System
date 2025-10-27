function toggleMenu() {
  const menu = document.getElementById("mobile-menu");
  if (menu.classList.contains("-translate-y-full")) {
    // Open menu
    menu.classList.remove("-translate-y-full");
    menu.classList.add("translate-y-0");
  } else {
    // Close menu
    menu.classList.remove("translate-y-0");
    menu.classList.add("-translate-y-full");
  }
}

// New function to handle the desktop Account dropdown
function toggleDropdown(dropdownId) {
  const dropdown = document.getElementById(dropdownId);
  // Toggle the 'hidden' class to show/hide the dropdown
  dropdown.classList.toggle("hidden");
}

// Optional: Close dropdown when clicking outside of it
document.addEventListener("click", (event) => {
  const dropdown = document.getElementById("account-dropdown");
  const button = document.getElementById("account-dropdown-btn");

  // Check if the click occurred outside the button and the dropdown
  if (
    dropdown &&
    button &&
    !dropdown.contains(event.target) &&
    !button.contains(event.target)
  ) {
    dropdown.classList.add("hidden");
  }
});

//open modal
function openModal(modalID) {
  document.getElementById(modalID).classList.remove("hidden");
  document.getElementById(modalID).classList.add("flex");
}

//close modal
function closeModal(modalID) {
  document.getElementById(modalID).classList.add("hidden");
  document.getElementById(modalID).classList.remove("flex");
}
