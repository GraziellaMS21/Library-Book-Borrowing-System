const burger = document.querySelector(".burger");
const navLinks = document.querySelector(".nav-links");

burger.addEventListener("click", () => {
  burger.classList.toggle("active");
  navLinks.classList.toggle("active");
});

document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('borrowerType');
    const emailMsg = document.getElementById('emailMessage');

    function updateEmailMessage() {
      const val = select.value;
      if (val === '1' || val === '2') {
        emailMsg.textContent = 'Use Your WMSU Email Address';
        emailMsg.style.display = 'block';
      } else if (val === '3') {
        emailMsg.textContent = 'Use Your Personal Email Address';
        emailMsg.style.display = 'block';
      } else {
        emailMsg.textContent = '';
        emailMsg.style.display = 'none';
      }
    }

    select.addEventListener('change', updateEmailMessage);
    updateEmailMessage();
});