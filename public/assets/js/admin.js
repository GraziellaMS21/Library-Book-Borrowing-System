document.addEventListener("DOMContentLoaded", () => {
  const closeBtns = document.querySelectorAll(".close");


  const closeModalAndRedirect = () => {
    const currentUrl = new URL(window.location.href);

    if (currentUrl.searchParams.has("modal")) {
      currentUrl.searchParams.delete("modal");
      currentUrl.searchParams.delete("id");

      window.location.href = currentUrl.toString();
    }
    
    if (currentUrl.searchParams.has("success")) {
      currentUrl.searchParams.delete("success");

      window.location.href = currentUrl.toString();
    }
  };
  // Close Modal using closeBtn
  closeBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      closeModalAndRedirect();
    });
  });

  //Close Modal when clicking outside
  window.addEventListener("click", (e) => {
    if (e.target.classList.contains("modal" || "success")) {
      closeModalAndRedirect();
    }
  });
});

document.addEventListener("DOMContentLoaded", () => {
  const closeBtns = document.querySelectorAll(".close");
  const modals = document.querySelectorAll(".modal");

  // Function to redirect/close modal by clearing the URL state
  const closeModalAndRedirect = (tab) => {
    const currentUrl = new URL(window.location.href);

    // Clear modal/id parameters
    currentUrl.searchParams.delete("modal");
    currentUrl.searchParams.delete("id");

    // Ensure the tab parameter is preserved or set back to default if needed
    if (tab && tab !== "pending") {
      currentUrl.searchParams.set("tab", tab);
    } else {
      // If tab is 'pending' (default) or not explicitly set, let the base PHP handle it
      currentUrl.searchParams.delete("tab");
    }

    window.location.href = currentUrl.toString();
  };

  // Close Modal using closeBtn
  closeBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      // Read the current tab from the button's data attribute
      const currentTab = btn.getAttribute("data-tab") || "pending";
      closeModalAndRedirect(currentTab);
    });
  });

  //Close Modal when clicking outside
  window.addEventListener("click", (e) => {
    if (e.target.classList.contains("modal")) {
      // Find the correct tab context from the open modal
      const openModal = e.target;
      const closeBtnInside = openModal.querySelector(".close");
      const currentTab = closeBtnInside
        ? closeBtnInside.getAttribute("data-tab")
        : "pending";

      closeModalAndRedirect(currentTab);
    }
  });
});
