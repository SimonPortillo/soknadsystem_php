document.addEventListener("DOMContentLoaded", function () {
  // Restore open main accordion from localStorage
  var openAccordion = localStorage.getItem("openAccordion");
  if (openAccordion) {
    var collapse = document.getElementById(openAccordion);
    if (collapse) {
      var bsCollapse = new bootstrap.Collapse(collapse, { toggle: true });
    }
  }

  // Restore open user accordion from localStorage (inside admin section)
  var openUserAccordion = localStorage.getItem("openUserAccordion");
  if (openUserAccordion) {
    var userCollapse = document.getElementById(openUserAccordion);
    if (userCollapse) {
      // Wait a bit for the parent accordion to open first
      setTimeout(function () {
        var bsUserCollapse = new bootstrap.Collapse(userCollapse, {
          toggle: true,
        });
      }, 300);
    }
  }

  // Listen for main accordion open events and save the ID
  document
    .querySelectorAll(".accordion > .accordion-item > .accordion-collapse")
    .forEach(function (el) {
      el.addEventListener("show.bs.collapse", function () {
        localStorage.setItem("openAccordion", el.id);
      });
    });

  // Listen for user accordion open events (nested inside admin section)
  document
    .querySelectorAll("#usersAccordion .accordion-collapse")
    .forEach(function (el) {
      el.addEventListener("show.bs.collapse", function () {
        localStorage.setItem("openUserAccordion", el.id);
      });

      el.addEventListener("hide.bs.collapse", function () {
        // Clear the stored user accordion when it's closed
        if (localStorage.getItem("openUserAccordion") === el.id) {
          localStorage.removeItem("openUserAccordion");
        }
      });
    });
});
