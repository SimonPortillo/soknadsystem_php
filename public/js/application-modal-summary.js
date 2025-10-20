document.addEventListener('DOMContentLoaded', function() {
    var applicationModal = new bootstrap.Modal(document.getElementById('application-modal'));
    var openModalBtn = document.getElementById('openApplicationModal');
    var confirmBtn = document.getElementById('confirmApplicationSubmit');
    var form = document.querySelector('form[action^="/positions/"]');
    var chosenDocumentsList = document.getElementById('chosen-documents-list');

    openModalBtn.addEventListener('click', function(e) {
        // Gather selected document names
        var cvName = '';
        var coverLetterName = '';
        // CV
        var cvId = document.getElementById('cv_document_id').value;
        if (cvId) {
            var cvElem = document.querySelector('.cv-document-option[data-cv-id="' + cvId + '"]');
            if (cvElem) cvName = cvElem.getAttribute('data-cv-name');
        } else {
            var cvFile = document.getElementById('cv_file').files[0];
            if (cvFile) cvName = cvFile.name;
        }
        // Cover letter
        var clId = document.getElementById('cover_letter_document_id').value;
        if (clId) {
            var clElem = document.querySelector('.cover-letter-document-option[data-cover-letter-id="' + clId + '"]');
            if (clElem) coverLetterName = clElem.getAttribute('data-cover-letter-name');
        } else {
            var clFile = document.getElementById('cover_letter_file').files[0];
            if (clFile) coverLetterName = clFile.name;
        }
        // If less than 2 documents, submit directly for backend error
        var docCount = 0;
        if (cvName) docCount++;
        if (coverLetterName) docCount++;
        if (docCount < 2) {
            form.submit();
            return;
        }
        // Otherwise, show modal with document names
        chosenDocumentsList.innerHTML = '';
        if (cvName) {
            var li = document.createElement('li');
            li.className = 'list-group-item';
            li.textContent = 'CV: ' + cvName;
            chosenDocumentsList.appendChild(li);
        }
        if (coverLetterName) {
            var li = document.createElement('li');
            li.className = 'list-group-item';
            li.textContent = 'Søknadsbrev: ' + coverLetterName;
            chosenDocumentsList.appendChild(li);
        }
        applicationModal.show();
    });

    confirmBtn.addEventListener('click', function(e) {
        applicationModal.hide();
        form.submit();
    });
});