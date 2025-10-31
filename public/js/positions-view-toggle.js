document.addEventListener('DOMContentLoaded', function () {
    var gridBtn = document.getElementById('gridViewBtn');
    var tableBtn = document.getElementById('tableViewBtn');
    var gridView = document.getElementById('positionsGridView');
    var tableView = document.getElementById('positionsTableView');
    // Restore view mode from localStorage
    var mode = localStorage.getItem('positionsViewMode') || 'grid';
    if (mode === 'table') {
        gridBtn.classList.remove('active');
        tableBtn.classList.add('active');
        gridView.classList.add('d-none');
        tableView.classList.remove('d-none');
    } else {
        gridBtn.classList.add('active');
        tableBtn.classList.remove('active');
        gridView.classList.remove('d-none');
        tableView.classList.add('d-none');
    }
    gridBtn.addEventListener('click', function () {
        gridBtn.classList.add('active');
        tableBtn.classList.remove('active');
        gridView.classList.remove('d-none');
        tableView.classList.add('d-none');
        localStorage.setItem('positionsViewMode', 'grid');
    });
    tableBtn.addEventListener('click', function () {
        tableBtn.classList.add('active');
        gridBtn.classList.remove('active');
        tableView.classList.remove('d-none');
        gridView.classList.add('d-none');
        localStorage.setItem('positionsViewMode', 'table');
    });
});