document.addEventListener("DOMContentLoaded", function () {
  const dashboardBtn = document.getElementById("dashboardBtn");
  const booksBtn = document.getElementById("booksBtn");
  const borrowersBtn = document.getElementById("borrowersBtn");
  const detailsBtn = document.getElementById("detailsBtn");
  const reportsBtn = document.getElementById("reportsBtn");

  const dashboardSection = document.querySelector(".dashboardSection");
  const booksSection = document.querySelector(".booksSection");
  const borrowersSection = document.querySelector(".borrowersSection");
  const detailsSection = document.querySelector(".detailsSection");
  const reportsSection = document.querySelector(".reportsSection");

  function showSection(sectionToShow) {
    const allSections = [
      dashboardSection,
      booksSection,
      borrowersSection,
      detailsSection,
      reportsSection,
    ];

    allSections.forEach((section) => {
      if (section) {
        section.classList.add("hidden");
      }
    });
    if (sectionToShow) {
      sectionToShow.classList.remove("hidden");
    }
  }

  showSection(dashboardSection);

  dashboardBtn.onclick = () => showSection(dashboardSection);
  booksBtn.onclick = () => showSection(booksSection);
  borrowersBtn.onclick = () => showSection(borrowersSection);
  detailsBtn.onclick = () => showSection(detailsSection);
  reportsBtn.onclick = () => showSection(reportsSection);
});

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
