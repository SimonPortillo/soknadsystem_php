// Handle user role updates in admin panel
document.addEventListener('DOMContentLoaded', function () {
    // Find all user role select dropdowns
    const roleSelects = document.querySelectorAll('.user-role-select');
    
    roleSelects.forEach(function(select) {
        select.addEventListener('change', function() {
            // Find the closest form and submit it
            const form = this.closest('.user-role-form');
            if (form) {
                form.submit();
            }
        });
    });
});
