document.addEventListener('DOMContentLoaded', function () {
    // Restore open accordion from localStorage
    var openAccordion = localStorage.getItem('openAccordion');
    if (openAccordion) {
        var collapse = document.getElementById(openAccordion);
        if (collapse) {
            var bsCollapse = new bootstrap.Collapse(collapse, { toggle: true });
        }
    }

    // Listen for accordion open events and save the ID
    document.querySelectorAll('.accordion .accordion-collapse').forEach(function (el) {
        el.addEventListener('show.bs.collapse', function () {
            localStorage.setItem('openAccordion', el.id);
        });
    });
});