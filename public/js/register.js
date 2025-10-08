//buger js
const burger = document.querySelector(".burger");
const navLinks = document.querySelector(".nav-links");

burger.addEventListener("click", () => {
  burger.classList.toggle("active");
  navLinks.classList.toggle("active");
});

//email message js
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

//modal js
const openModal = document.getElementById("openModal");
const closeModal = document.getElementById("closeModal");
const closeBtn = document.getElementById("closeBtn");
const modal = document.getElementById("termsModal");  
openModal.addEventListener("click", ()=> {
  modal.classList.remove('hidden');
})

closeModal.addEventListener("click", ()=> {
  modal.classList.add('hidden');
})

closeBtn.addEventListener("click", ()=> {
  modal.classList.add('hidden');
})

modal.addEventListener("click", (e) => {
      if (e.target === modal) {
        modal.classList.add("hidden");
      } 
    });