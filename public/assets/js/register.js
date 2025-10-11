//email message js
document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('borrowerType');
    const emailMsg = document.getElementById('emailMessage');
    const college = document.getElementById('college');
    const department = document.getElementById('department');
    const position = document.getElementById('position');
    const collegeLabel = document.getElementById('collegeLabel');
    const positionLabel = document.querySelector('positionLabel');

    function updateFields() {
      const val = select.value

      college.classList.add('hidden');
      department.classList.add('hidden');
      position.classList.add('hidden');


      collegeLabel.innerHTML = 'College:';
      positionLabel.innerHTML = 'Position:';

        if (selectedValue === 'student') {
            college.classList.remove('hidden');
            collegeLabel.innerHTML = 'College<span>*</span>:';
        } else if (selectedValue === 'staff') {
            college.classList.remove('hidden');
            department.classList.remove('hidden');
            position.classList.remove('hidden');
            positionLabel.innerHTML = 'Position<span>*</span>:';
        }
    }

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

    updateEmailMessage();
    updateFields();
    select.addEventListener('change', updateEmailMessage);
    select.addEventListener('change', updateFields);
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