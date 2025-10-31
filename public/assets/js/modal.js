document.addEventListener("DOMContentLoaded", () => {
  const closeBtns = document.querySelectorAll(".close");
  const modals = document.querySelectorAll(".modal");

  // Unified function to close modal and optionally preserve tab
  const closeModalAndRedirect = (tab = null) => {
    const currentUrl = new URL(window.location.href);

    // Remove modal-related params
    ["modal", "id", "success"].forEach(param => currentUrl.searchParams.delete(param));

    // Optional: preserve or remove tab
    if (tab) {
      // Keep the given tab
      if (tab !== "pending") {
        currentUrl.searchParams.set("tab", tab);
      } else {
        currentUrl.searchParams.delete("tab");
      }
    } else {
      // If no tab context, remove any existing tab parameter
      currentUrl.searchParams.delete("tab");
    }

    window.location.href = currentUrl.toString();
  };

  // --- Close Modal via buttons ---
  closeBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      const currentTab = btn.getAttribute("data-tab") || null;
      closeModalAndRedirect(currentTab);
    });
  });

  // --- Close Modal when clicking outside ---
  window.addEventListener("click", (e) => {
    if (e.target.classList.contains("modal")) {
      const modal = e.target;
      const closeBtn = modal.querySelector(".close");
      const currentTab = closeBtn ? closeBtn.getAttribute("data-tab") || null : null;
      closeModalAndRedirect(currentTab);
    }
  });
});
