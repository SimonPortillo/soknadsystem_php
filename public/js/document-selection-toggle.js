// Handle CV selection toggle
const cvFileInput = document.getElementById('cv_file');
const cvDocumentIdInput = document.getElementById('cv_document_id');
const cvToggleBtns = document.querySelectorAll('.cv-toggle-btn');

cvToggleBtns.forEach(function(btn) {
    btn.addEventListener('click', function() {
        const parentDiv = this.closest('.cv-document-option');
        const cvId = parentDiv.getAttribute('data-cv-id');
        const isSelected = parentDiv.classList.contains('alert-success');
        
        if (isSelected) {
            // Deselect
            parentDiv.classList.remove('alert-success', 'border-success', 'border-2');
            parentDiv.classList.add('alert-light');
            this.innerHTML = '<i class="bi bi-file-earmark-plus"></i> Bruk';
            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-outline-primary');
            cvDocumentIdInput.value = '';
            cvFileInput.disabled = false;
        } else {
            // Deselect all others first
            document.querySelectorAll('.cv-document-option').forEach(function(el) {
                el.classList.remove('alert-success', 'border-success', 'border-2');
                el.classList.add('alert-light');
                const otherBtn = el.querySelector('.cv-toggle-btn');
                otherBtn.innerHTML = '<i class="bi bi-file-earmark-plus"></i> Bruk';
                otherBtn.classList.remove('btn-outline-secondary');
                otherBtn.classList.add('btn-outline-primary');
            });
            
            // Select this one
            parentDiv.classList.remove('alert-light');
            parentDiv.classList.add('alert-success', 'border-success', 'border-2');
            this.innerHTML = '<i class="bi bi-x-circle"></i> Fjern valg';
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-outline-secondary');
            cvDocumentIdInput.value = cvId;
            cvFileInput.disabled = true;
            cvFileInput.value = '';
        }
    });
});

// Clear CV selection if user chooses to upload a new file
cvFileInput.addEventListener('change', function() {
    if (this.files.length > 0) {
        cvDocumentIdInput.value = '';
        document.querySelectorAll('.cv-document-option').forEach(function(el) {
            el.classList.remove('alert-success', 'border-success', 'border-2');
            el.classList.add('alert-light');
            const btn = el.querySelector('.cv-toggle-btn');
            btn.innerHTML = '<i class="bi bi-file-earmark-plus"></i> Bruk';
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-outline-primary');
        });
        cvFileInput.disabled = false;
    }
});

// Handle Cover Letter selection toggle
const coverLetterFileInput = document.getElementById('cover_letter_file');
const coverLetterDocumentIdInput = document.getElementById('cover_letter_document_id');
const coverLetterToggleBtns = document.querySelectorAll('.cover-letter-toggle-btn');

coverLetterToggleBtns.forEach(function(btn) {
    btn.addEventListener('click', function() {
        const parentDiv = this.closest('.cover-letter-document-option');
        const coverLetterId = parentDiv.getAttribute('data-cover-letter-id');
        const isSelected = parentDiv.classList.contains('alert-success');
        
        if (isSelected) {
            // Deselect
            parentDiv.classList.remove('alert-success', 'border-success', 'border-2');
            parentDiv.classList.add('alert-light');
            this.innerHTML = '<i class="bi bi-file-earmark-plus"></i> Bruk';
            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-outline-primary');
            coverLetterDocumentIdInput.value = '';
            coverLetterFileInput.disabled = false;
        } else {
            // Deselect all others first
            document.querySelectorAll('.cover-letter-document-option').forEach(function(el) {
                el.classList.remove('alert-success', 'border-success', 'border-2');
                el.classList.add('alert-light');
                const otherBtn = el.querySelector('.cover-letter-toggle-btn');
                otherBtn.innerHTML = '<i class="bi bi-file-earmark-plus"></i> Bruk';
                otherBtn.classList.remove('btn-outline-secondary');
                otherBtn.classList.add('btn-outline-primary');
            });
            
            // Select this one
            parentDiv.classList.remove('alert-light');
            parentDiv.classList.add('alert-success', 'border-success', 'border-2');
            this.innerHTML = '<i class="bi bi-x-circle"></i> Fjern valg';
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-outline-secondary');
            coverLetterDocumentIdInput.value = coverLetterId;
            coverLetterFileInput.disabled = true;
            coverLetterFileInput.value = '';
        }
    });
});

// Clear cover letter selection if user chooses to upload a new file
coverLetterFileInput.addEventListener('change', function() {
    if (this.files.length > 0) {
        coverLetterDocumentIdInput.value = '';
        document.querySelectorAll('.cover-letter-document-option').forEach(function(el) {
            el.classList.remove('alert-success', 'border-success', 'border-2');
            el.classList.add('alert-light');
            const btn = el.querySelector('.cover-letter-toggle-btn');
            btn.innerHTML = '<i class="bi bi-file-earmark-plus"></i> Bruk';
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-outline-primary');
        });
        coverLetterFileInput.disabled = false;
    }
});