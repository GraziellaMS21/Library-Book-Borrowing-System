document.addEventListener("DOMContentLoaded", () => {
  const closeBtns = document.querySelectorAll(".close");
  const modals = document.querySelectorAll(".modal");

  const openModal = (modal) => {
    modal.style.display = "flex";
  };

  const closeModalAndRedirect = () => {
    const currentUrl = new URL(window.location.href);

    if (currentUrl.searchParams.has("modal")) {
      currentUrl.searchParams.delete("modal");
      currentUrl.searchParams.delete("id");

      window.location.href = currentUrl.toString();
    }
  };

  // Function to visually hide the modal (used for outside click only if we don't redirect)
  const closeModalVisual = (modal) => {
    modal.style.display = "none";
    modal.classList.remove("open");
  };

  // Close Modal using closeBtn
  closeBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      closeModalAndRedirect();
    });
  });

  //Close Modal when clicking outside
  window.addEventListener("click", (e) => {
    if (e.target.classList.contains("modal")) {
      // We use visual closing here, as forcing a redirect on a window click
      // can be jarring, though it might still be re-opened by PHP on the next action.
      // For *this* specific architecture, we should still redirect for consistency
      // since PHP controls the open state.
      closeModalAndRedirect();
    }
  });

  // Since the modals are opened by PHP adding the 'open' class and setting 'display: flex'
  // (likely in the CSS for .modal.open), we need to ensure the modals are visually present
  // if the 'open' class is applied on load. However, the PHP conditional rendering handles this.

  // We are removing the redundant openModal and closeModalVisual functions for the
  // explicit close action and enforcing a redirect for consistent state management.
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

  // ... (any other logic you might have)
});
