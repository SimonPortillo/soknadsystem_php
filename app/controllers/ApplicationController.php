<?php


namespace app\controllers;

use flight\Engine;
use app\models\Application;
use app\models\Position;
use app\models\User;
use app\models\Document;

class ApplicationController {

    protected Engine $app;

    public function __construct(Engine $app) {
        $this->app = $app;
    }

     /**
     * Display the position application form
     * 
     * Shows the form for applying to a position. Only accessible to authenticated users.
     * 
     * Route: GET /positions/{id}/apply
     * 
     * @param int $id The position ID
     * @return void
     */
    public function index($id) {
        // Redirect to login if not authenticated
        if (!$this->app->session()->get('is_logged_in')) {
            $this->app->redirect('/login');
            return;
        }

        // Get the current user
        $userId = $this->app->session()->get('user_id');
        $userModel = new \app\models\User($this->app->db());
        $user = $userModel->findById($userId);
        
        if (!$user) {
            $this->app->redirect('/login');
            return;
        }
        
        // Get position details
        $positionModel = new Position($this->app->db());
        $position = $positionModel->findById($id);
        
        if (!$position) {
            $this->app->redirect('/positions');
            return;
        }
        
        // Check if user has already applied
        $applicationModel = new \app\models\Application($this->app->db());
        $hasApplied = $applicationModel->hasApplied($id, $userId);

        // Get session messages and clear them
        $successMessage = $this->app->session()->get('application_success');
        $errorMessage = $this->app->session()->get('application_error');
        $this->app->session()->delete('application_success');
        $this->app->session()->delete('application_error');
        
        // Render the application form
        $this->app->latte()->render(__DIR__ . '/../views/auth/apply-position.latte', [
            'isLoggedIn' => true,
            'username' => $user->username,
            'role' => $user->role,
            'position' => $position,
            'hasApplied' => $hasApplied,
            'success_message' => $successMessage,
            'error_message' => $errorMessage,
            'csp_nonce' => $this->app->get('csp_nonce')
        ]);
    }    /**
     * Process position application
     * 
     * Handles form submission for applying to a position.
     * Only accessible to authenticated users.
     * 
     * Route: POST /positions/{id}/apply
     * 
     * @param int $id The position ID
     * @return void
     */
    public function apply($id) {
        // Redirect to login if not authenticated
        if (!$this->app->session()->get('is_logged_in')) {
            $this->app->redirect('/login');
            return;
        }

        // Get the current user
        $userId = $this->app->session()->get('user_id');
        $userModel = new User($this->app->db());
        $user = $userModel->findById($userId);
        
        if (!$user) {
            $this->app->redirect('/login');
            return;
        }
        
        // Get position details
        $positionModel = new Position($this->app->db());
        $position = $positionModel->findById($id);
        
        if (!$position) {
            $this->app->redirect('/positions');
            return;
        }
        
        // Check if user has already applied
        $applicationModel = new Application($this->app->db());
        if ($applicationModel->hasApplied($id, $userId)) {
            $this->app->session()->set('application_error', 'Du har allerede søkt på denne stillingen.');
            $this->app->redirect('/positions');
            return;
        }
        
        // Get form data
        $data = $this->app->request()->data;
        $notes = $data->notes ?? null;
        
        // Handle file uploads
        $cvFile = $_FILES['cv_file'] ?? null;
        $coverLetterFile = $_FILES['cover_letter_file'] ?? null;
        
        // Validate CV file is present
        if (!$cvFile || $cvFile['error'] === UPLOAD_ERR_NO_FILE) {
            $this->app->session()->set('application_error', 'CV er påkrevd for å søke på stillingen.');
            $this->app->redirect('/positions/' . $id . '/apply');
            return;
        }
        
        // Validate cover letter file is present
        if (!$coverLetterFile || $coverLetterFile['error'] === UPLOAD_ERR_NO_FILE) {
            $this->app->session()->set('application_error', 'Søknadsbrev er påkrevd for å søke på stillingen.');
            $this->app->redirect('/positions/' . $id . '/apply');
            return;
        }
        
        // Upload CV using DocumentController
        $documentController = new DocumentController($this->app);
        $cvResult = $documentController->processFileUpload($cvFile, $userId, 'cv');
        
        if (!$cvResult['success']) {
            $this->app->session()->set('application_error', 'Feil ved opplasting av CV: ' . $cvResult['error']);
            $this->app->redirect('/positions/' . $id . '/apply');
            return;
        }
        
        $cvDocumentId = $cvResult['document_id'];
        
        // Upload cover letter (required)
        $coverLetterResult = $documentController->processFileUpload($coverLetterFile, $userId, 'cover_letter');
        
        if (!$coverLetterResult['success']) {
            $this->app->session()->set('application_error', 'Feil ved opplasting av søknadsbrev: ' . $coverLetterResult['error']);
            $this->app->redirect('/positions/' . $id . '/apply');
            return;
        }
        
        $coverLetterDocumentId = $coverLetterResult['document_id'];

        // Create the application with document IDs
        $result = $applicationModel->create($id, $userId, $cvDocumentId, $coverLetterDocumentId, $notes);
        
        if ($result) {
            $this->app->session()->set('application_success', 'Søknaden din er sendt inn.');
        } else {
            $this->app->session()->set('application_error', 'Kunne ikke sende inn søknaden. Prøv igjen senere.');
        }
        
        $this->app->redirect('/positions');
    }
}