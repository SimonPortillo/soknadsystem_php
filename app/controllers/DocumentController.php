<?php

namespace App\Controllers;

use flight\Engine;
use app\models\Document;

class DocumentController
{
    protected Engine $app;

	public function __construct(Engine $app) {
		$this->app = $app;
	}

    public function upload()
    {
        // Check authentication
        if (!$this->app->session()->get('is_logged_in')) {
            $this->app->redirect('/login');
            return;
        }

        $userId = $this->app->session()->get('user_id');
        
        // Get uploaded files
        $cvFile = $_FILES['cv_file'] ?? null;
        $coverLetterFile = $_FILES['cover_letter_file'] ?? null;

        $uploadedCount = 0;
        $errors = [];

        // Process CV upload
        if ($cvFile && $cvFile['error'] !== UPLOAD_ERR_NO_FILE) {
            $result = $this->processFileUpload($cvFile, $userId, 'cv');
            if ($result['success']) {
                $uploadedCount++;
            } else {
                $errors[] = $result['error'];
            }
        }

        // Process cover letter upload
        if ($coverLetterFile && $coverLetterFile['error'] !== UPLOAD_ERR_NO_FILE) {
            $result = $this->processFileUpload($coverLetterFile, $userId, 'cover_letter');
            if ($result['success']) {
                $uploadedCount++;
            } else {
                $errors[] = $result['error'];
            }
        }

        // Set appropriate message
        if ($uploadedCount === 1 && empty($errors)) {
            $this->app->session()->set('success_message', 'Dokumentet ble lastet opp.');
        } elseif ($uploadedCount > 1 && empty($errors)) {
            $this->app->session()->set('success_message', 'Dokumenter ble lastet opp.');
        } elseif ($uploadedCount > 0 && !empty($errors)) {
            $this->app->session()->set('error_message', 'Noen dokumenter ble lastet opp, men det oppstod feil: ' . implode(', ', $errors));
        } elseif (!empty($errors)) {
            $this->app->session()->set('error_message', 'Feil ved opplasting: ' . implode(', ', $errors));
        } else {
            $this->app->session()->set('error_message', 'Ingen filer ble valgt.');
        }

        $this->app->redirect('/min-side');
    }

    /**
     * Process a single file upload
     * 
     * @param array $file The uploaded file array from $_FILES
     * @param int $userId The user ID
     * @param string $type The document type ('cv' or 'cover_letter')
     * @return array Result array with 'success' and 'error' keys
     */
    private function processFileUpload(array $file, int $userId, string $type): array
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Feil ved opplasting av fil.'];
        }

        // Validate file size (5MB max)
        $maxSize = 5 * 1024 * 1024; // 5MB in bytes
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'Filen er for stor (maks 5MB).'];
        }

        // Validate MIME type
        $allowedMimeTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedMimeTypes)) {
            return ['success' => false, 'error' => 'Ugyldig filtype. Kun PDF, DOC og DOCX er tillatt.'];
        }

        // Get file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['pdf', 'doc', 'docx'])) {
            return ['success' => false, 'error' => 'Ugyldig filtype.'];
        }

        // Create upload directory if it doesn't exist
        $uploadDir = __DIR__ . '/../../uploads/users/' . $userId . '/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return ['success' => false, 'error' => 'Kunne ikke opprette opplastingsmappe.'];
            }
        }

        // Generate unique filename
        $timestamp = date('Ymd_His');
        $filename = "{$type}_{$timestamp}.{$extension}";
        $targetPath = $uploadDir . $filename;
        $relativeFilePath = "users/{$userId}/{$filename}";

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => false, 'error' => 'Kunne ikke lagre filen.'];
        }

        // Delete old document of the same type
        $documentModel = new Document($this->app->db());
        

        // Save to database
        $success = $documentModel->create(
            $userId,
            $type,
            $relativeFilePath,
            $file['name'],
            $mimeType
        );

        if (!$success) {
            // Clean up uploaded file if database insert fails
            if (file_exists($targetPath)) {
                unlink($targetPath);
            }
            return ['success' => false, 'error' => 'Kunne ikke lagre dokumentinformasjon i databasen.'];
        }

        return ['success' => true, 'error' => null];
    }

    /**
     * Delete a document
     * 
     * Deletes a specific document by ID. Verifies user ownership before deletion.
     * 
     * @return void Redirects back to min-side with success/error message
     */
    public function delete() {
        // Check authentication
        if (!$this->app->session()->get('is_logged_in')) {
            $this->app->redirect('/login');
            return;
        }

        $userId = $this->app->session()->get('user_id');
        $documentId = $this->app->request()->data->document_id ?? null;

        if (!$documentId) {
            $this->app->session()->set('error_message', 'Ugyldig dokument-ID.');
            $this->app->redirect('/min-side');
            return;
        }

        // Delete the document
        $documentModel = new Document($this->app->db());
        $success = $documentModel->deleteById((int)$documentId, $userId);

        if ($success) {
            $this->app->session()->set('success_message', 'Dokumentet ble slettet.');
        } else {
            $this->app->session()->set('error_message', 'Kunne ikke slette dokumentet. Det tilhører kanskje ikke deg.');
        }

        $this->app->redirect('/min-side');
    }
        
}