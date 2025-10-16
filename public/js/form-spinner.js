document.querySelectorAll('form').forEach(form => {
  form.addEventListener('submit', function(e) {
    const btn = form.querySelector('.btn-loader');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Laster...';
    }
  });
});