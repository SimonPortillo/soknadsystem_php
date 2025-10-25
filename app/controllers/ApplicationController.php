<?php


namespace app\controllers;

use flight\Engine;
use app\models\Application;
use app\models\Position;
use app\models\User;
use app\models\Document;

class ApplicationController {

    /**
     * Controller for handling position applications.
     *
     * Responsibilities:
     * - Display and process position application forms.
     * - Handle document uploads and selection (CVs, cover letters).
     * - Enforce role-based access control for application actions.
     * - Manage session messages related to applications.
     *
     * This controller ensures only authenticated users can apply for positions,
     * select/upload required documents, and receive feedback on their application status.
     *
     * @package app\controllers
     */

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
        $applicationModel = new Application($this->app->db());
        $hasApplied = $applicationModel->hasApplied($id, $userId);

        // fetch user info
        $userModel = new User($this->app->db());
        $user = $userModel->findById($userId);
       
        // fetch user documents
        $docModel = new Document($this->app->db());
        $cvDocuments = $docModel->findByUser($userId, 'cv');
        $coverLetterDocuments = $docModel->findByUser($userId, 'cover_letter'); 

        // Get session messages and clear them
        $successMessage = $this->app->session()->get('application_success');
        $errorMessage = $this->app->session()->get('application_error');
        $this->app->session()->delete('application_success');
        $this->app->session()->delete('application_error');
        
        // Render the application form
        $this->app->latte()->render(__DIR__ . '/../views/user/apply-position.latte', [
            'isLoggedIn' => true,
            'username' => $user->getUsername(),
            'role' => $user->getRole(),
            'position' => $position,
            'hasApplied' => $hasApplied,
            'success_message' => $successMessage,
            'error_message' => $errorMessage,
            'cv_documents' => $cvDocuments, 
            'cover_letter_documents' => $coverLetterDocuments, 
            'user' => $user,
            'csp_nonce' => $this->app->get('csp_nonce')
        ]);
    }

    /**
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
        
        // Check if user selected existing documents or uploaded new ones
        $cvDocumentId = $data->cv_document_id ?? null;
        $coverLetterDocumentId = $data->cover_letter_document_id ?? null;
        
        // Handle CV - either use existing or upload new
        if (empty($cvDocumentId)) {
            // No existing document selected, check for file upload
            $cvFile = $_FILES['cv_file'] ?? null;
            
            if (!$cvFile || $cvFile['error'] === UPLOAD_ERR_NO_FILE) {
                $this->app->session()->set('application_error', 'Du må enten velge en eksisterende CV eller laste opp en ny.');
                $this->app->redirect('/positions/' . $id . '/apply');
                return;
            }
            
            // Upload new CV
            $documentController = new DocumentController($this->app);
            $cvResult = $documentController->processFileUpload($cvFile, $userId, 'cv');
            
            if (!$cvResult['success']) {
                $this->app->session()->set('application_error', 'Feil ved opplasting av CV: ' . $cvResult['error']);
                $this->app->redirect('/positions/' . $id . '/apply');
                return;
            }
            
            $cvDocumentId = $cvResult['document_id'];
        }
        
        // Handle Cover Letter - either use existing or upload new
        if (empty($coverLetterDocumentId)) {
            // No existing document selected, check for file upload
            $coverLetterFile = $_FILES['cover_letter_file'] ?? null;
            
            if (!$coverLetterFile || $coverLetterFile['error'] === UPLOAD_ERR_NO_FILE) {
                $this->app->session()->set('application_error', 'Du må enten velge et eksisterende søknadsbrev eller laste opp et nytt.');
                $this->app->redirect('/positions/' . $id . '/apply');
                return;
            }
            
            // Upload new cover letter
            $documentController = new DocumentController($this->app);
            $coverLetterResult = $documentController->processFileUpload($coverLetterFile, $userId, 'cover_letter');
            
            if (!$coverLetterResult['success']) {
                $this->app->session()->set('application_error', 'Feil ved opplasting av søknadsbrev: ' . $coverLetterResult['error']);
                $this->app->redirect('/positions/' . $id . '/apply');
                return;
            }
            
            $coverLetterDocumentId = $coverLetterResult['document_id'];
        }

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