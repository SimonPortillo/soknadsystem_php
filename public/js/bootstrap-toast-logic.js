document.addEventListener('DOMContentLoaded', function () {
    // Get all toast elements
    var toastElList = [].slice.call(document.querySelectorAll('.toast'));
    
    // Initialize and show each toast
    toastElList.forEach(function (toastEl) {
        var toast = new bootstrap.Toast(toastEl, {
            autohide: true,
            delay: 4000
        });
        toast.show();
    });
});