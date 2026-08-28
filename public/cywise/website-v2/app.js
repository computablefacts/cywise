document.addEventListener("DOMContentLoaded", () => {
  const header = document.querySelector(".site-header");

  window.addEventListener("scroll", () => {
    if (window.scrollY > 16) {
      header.classList.add("is-scrolled");
    } else {
      header.classList.remove("is-scrolled");
    }
  });

  document.querySelectorAll("#mainNav a:not(.dropdown-toggle)").forEach((link) => {
    link.addEventListener("click", () => {
      const nav = document.getElementById("mainNav");
      if (nav.classList.contains("show")) {
        bootstrap.Collapse.getOrCreateInstance(nav).hide();
      }
    });
  });
});
