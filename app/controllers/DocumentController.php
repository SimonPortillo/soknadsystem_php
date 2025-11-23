<?php

namespace App\Controllers;

use flight\Engine;
use app\models\Document;

/**
 * Controller for handling document operations.
 *
 * Responsibilities:
 * - Upload and validate user documents (CV, cover letters).
 * - Delete user documents with ownership verification.
 * - Serve/download documents with proper authorization checks.
 * - Enforce file type, size, and MIME type validation.
 * - Manage document storage and database records.
 *
 * This controller ensures only authorized users can access, upload, and delete documents,
 * with proper security checks including path traversal prevention and MIME type validation.
 *
 * @package app\controllers
 */
class DocumentController
{
    protected Engine $app;

	public function __construct(Engine $app) {
		$this->app = $app;
	}

    /**
     * Upload documents (CV and/or cover letter)
     * 
     * Handles file uploads from authenticated users. Validates file size, type, and MIME type.
     * Creates user-specific upload directories and saves document metadata to the database.
     * 
     * Route: POST /documents/upload
     * 
     * @return void Redirects to min-side with success/error message
     */
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
     * @return array Result array with 'success', 'document_id', and 'error' keys
     */
    public function processFileUpload(array $file, int $userId, string $type): array
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
            return ['success' => false, 'document_id' => null, 'error' => 'Kunne ikke lagre dokumentinformasjon i databasen.'];
        }

        // Get the inserted document ID
        $documentId = (int) $this->app->db()->lastInsertId();

        return ['success' => true, 'document_id' => $documentId, 'error' => null];
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

    /**
     * Serve/download a document file
     * 
     * Serves a document file for download. Includes authorization checks to ensure
     * only authorized users can download files.
     * 
     * Route: GET /documents/{documentId}/download
     * 
     * @param int $documentId The document ID
     * @return void Serves the file or redirects with error
     */
    public function download($documentId) {
        // Check authentication
        if (!$this->app->session()->get('is_logged_in')) {
            $this->app->halt(403, 'Ikke autorisert');
            return;
        }

        $userId = $this->app->session()->get('user_id');
        $userRole = $this->app->session()->get('role') ?? 'student';
        $documentModel = new Document($this->app->db());
        
        // Check if document exists
        $document = $documentModel->findById((int)$documentId);
        if (!$document) {
            $this->app->halt(404, 'Dokumentet ble ikke funnet');
            return;
        }

        // Check if user can access this document
        if (!$documentModel->canUserAccessDocument((int)$documentId, $userId, $userRole)) {
            $this->app->halt(403, 'Du har ikke tilgang til dette dokumentet');
            return;
        }

        // Build the full file path securely
        $uploadsDir = realpath(__DIR__ . '/../../uploads');
        $requestedPath = $uploadsDir . DIRECTORY_SEPARATOR . $document['file_path'];
        $filePath = realpath($requestedPath);

        // Validate that the resolved file path is within the uploads directory
        if ($filePath === false || strpos($filePath, $uploadsDir) !== 0) {
            $this->app->halt(404, 'Filen ble ikke funnet på serveren');
            return;
        }
        if (!file_exists($filePath)) {
            $this->app->halt(404, 'Filen ble ikke funnet på serveren');
            return;
        }

        // Serve the file
        // Validate MIME type against allowlist
        $allowedMimeTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'image/jpeg',
            'image/png',
            'image/gif',
            'text/plain'
        ];
        $mimeType = in_array($document['mime_type'], $allowedMimeTypes, true)
            ? $document['mime_type']
            : 'application/octet-stream';
        header('Content-Type: ' . $mimeType);
        // Sanitize filename for ASCII 'filename' parameter
        $asciiFilename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $document['original_name']);
        // Encode filename for RFC 5987 'filename*' parameter
        $utf8Filename = rawurlencode($document['original_name']);
        header('Content-Disposition: attachment; filename="' . $asciiFilename . '"; filename*=UTF-8\'\'' . $utf8Filename);
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        readfile($filePath);
        exit;
    }
        
}