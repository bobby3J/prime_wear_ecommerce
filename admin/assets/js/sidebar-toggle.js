/*
Manual sidebar toggle controller.
Behavior:
1) Sidebar is expanded by default.
2) Toggle button collapses/expands sidebar.
3) Preference persists in localStorage.
*/
(function () {
  const STORAGE_KEY = "admin_sidebar_collapsed";

  function applyCollapsedState(isCollapsed) {
    document.body.classList.toggle("sidebar-collapsed", isCollapsed);

    const toggleButton = document.getElementById("sidebarToggle");
    if (!toggleButton) return;
    const icon = toggleButton.querySelector("i");

    toggleButton.setAttribute("aria-expanded", isCollapsed ? "false" : "true");
    toggleButton.setAttribute(
      "title",
      isCollapsed ? "Expand sidebar" : "Collapse sidebar"
    );
    toggleButton.setAttribute(
      "aria-label",
      isCollapsed ? "Expand sidebar" : "Collapse sidebar"
    );

    if (icon) {
      icon.className = isCollapsed
        ? "fa-solid fa-angles-right"
        : "fa-solid fa-angles-left";
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    const toggleButton = document.getElementById("sidebarToggle");
    if (!toggleButton) return;

    const stored = localStorage.getItem(STORAGE_KEY);
    const isCollapsed = stored === "1";
    applyCollapsedState(isCollapsed);

    toggleButton.addEventListener("click", function () {
      const next = !document.body.classList.contains("sidebar-collapsed");
      applyCollapsedState(next);
      localStorage.setItem(STORAGE_KEY, next ? "1" : "0");
    });
  });
})();
