document.addEventListener("DOMContentLoaded", function () {
  const manageBooksBtn = document.getElementById("manageBooksBtn");
  const manageCatBtn = document.getElementById("manageCategoriesBtn");

  const manageBook = document.querySelector(".manage_books");
  const manageCatSection = document.querySelector(".manage_categories");

  function showBooksNCat(sectionToShow) {
    const allManageSections = [manageBook, manageCatSection];
    allManageSections.forEach((section) => {
      if (section) {
        section.classList.add("hidden");
      }
    });
    if (sectionToShow) {
      sectionToShow.classList.remove("hidden");
    }
  }

  showBooksNCat(manageBook);

  manageBooksBtn.onclick = () => {
    showBooksNCat(manageBook);
    manageBooksBtn.style.backgroundColor = "#931c19";
    manageBooksBtn.style.color = "#fff";
    manageCatBtn.style.backgroundColor = "#fff";
    manageCatBtn.style.color = "#000";
  };
  manageCatBtn.onclick = () => {
    showBooksNCat(manageCatSection);
    manageCatBtn.style.backgroundColor = "#931c19";
    manageCatBtn.style.color = "#fff";
    manageBooksBtn.style.backgroundColor = "#fff";
    manageBooksBtn.style.color = "#000";
  };
});


  //modal js
  const openModal = document.getElementById("openModal");
  const closeModal = document.getElementById("closeModal");
  const closeBtn = document.getElementById("closeBtn");
  const modal = document.getElementById("termsModal");  

  document.querySelectorAll(".openModal").forEach(button => {
  button.addEventListener("click", () => {
    const id = button.dataset.id;
    fetch(`../../../app/controllers/getBookDetails.php?id=${id}`)
      .then(res => res.text())
      .then(html => {
        document.getElementById("bookDetails").innerHTML = html;
        document.getElementById("termsModal").classList.remove("hidden");
      });
  });
});


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