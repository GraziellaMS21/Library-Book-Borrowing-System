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