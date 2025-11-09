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
            'message' => $successMessage,
            'errors' => $errorMessage,
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
        // restrict notes to employees only for communication
        $notes = null;
        
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

    /**
     * View applicants for a position
     * 
     * Shows all applicants who have applied to a specific position.
     * Only accessible to admin and employee roles, and only for positions they created.
     * 
     * Route: GET /positions/{id}/applicants
     * 
     * @param int $id The position ID
     * @return void
     */
    public function viewApplicants($id) {
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

        // Only allow admin and employee roles
        if (!in_array($user->getRole(), ['admin', 'employee'])) {
            $this->app->session()->set('error_message', 'Du har ikke tilgang til denne siden.');
            $this->app->redirect('/positions');
            return;
        }
        
        // Get position details
        $positionModel = new Position($this->app->db());
        $position = $positionModel->findById($id);
        
        if (!$position) {
            $this->app->session()->set('error_message', 'Stillingen ble ikke funnet.');
            $this->app->redirect('/min-side');
            return;
        }

        // Check if user is the creator of the position (add admin exception if admins should see all applicants)
        if ($position['creator_id'] !== $userId && $user->getRole() !== 'admin') {
            $this->app->session()->set('error_message', 'Du har ikke tilgang til å se søkere for denne stillingen.');
            $this->app->redirect('/min-side');
            return;
        }
        
        // Get all applicants for this position
        $applicationModel = new Application($this->app->db());
        $applicants = $applicationModel->getByPositionWithDetails($id);

        // Get session messages and clear them
        $successMessage = $this->app->session()->get('success_message');
        $errorMessage = $this->app->session()->get('error_message');
        $this->app->session()->delete('success_message');
        $this->app->session()->delete('error_message');
        
        // Render the applicants view
        $this->app->latte()->render(__DIR__ . '/../views/user/view-applicants.latte', [
            'isLoggedIn' => true,
            'username' => $user->getUsername(),
            'role' => $user->getRole(),
            'position' => $position,
            'applicants' => $applicants,
            'message' => $successMessage,
            'errors' => $errorMessage,
            'csp_nonce' => $this->app->get('csp_nonce')
        ]);
    }

    /**
     * Update application status
     * 
     * Updates the status of an application (pending, reviewed, accepted, rejected).
     * Only accessible to employee role, and only for positions they created.
     * 
     * Route: POST /positions/{id}/applicants/{applicationId}/status
     * 
     * @param int $id The position ID
     * @param int $applicationId The application ID
     * @return void
     */
    public function updateStatus($id, $applicationId) {
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

        // Only allow admin and employee roles
        if (!in_array($user->getRole(), ['admin', 'employee'])) {
            $this->app->session()->set('error_message', 'Du har ikke tilgang til denne handlingen.');
            $this->app->redirect('/positions');
            return;
        }
        
        // Get position details
        $positionModel = new Position($this->app->db());
        $position = $positionModel->findById($id);
        
        if (!$position) {
            $this->app->session()->set('error_message', 'Stillingen ble ikke funnet.');
            $this->app->redirect('/min-side');
            return;
        }

        // Check if user is the creator of the position (add admin exception if admins should update all applications)
        if ($position['creator_id'] !== $userId && $user->getRole() !== 'admin') {
            $this->app->session()->set('error_message', 'Du har ikke tilgang til å oppdatere søknader for denne stillingen.');
            $this->app->redirect('/min-side');
            return;
        }

        // Get form data
        $data = $this->app->request()->data;
        $status = $data->status ?? '';
        $notes = $data->notes ?? null;
        // Sanitize and validate notes input
        if ($notes !== null) {
            // Remove HTML tags
            $notes = strip_tags($notes);
            // Limit length to 1000 characters
            if (mb_strlen($notes) > 1000) {
                $notes = mb_substr($notes, 0, 1000);
            }
        }

        // Validate status
        if (!in_array($status, ['pending', 'reviewed', 'accepted', 'rejected'])) {
            $this->app->session()->set('error_message', 'Ugyldig status valgt.');
            $this->app->redirect('/positions/' . $id . '/applicants');
            return;
        }

        // Update the application status
        $applicationModel = new Application($this->app->db());
        $result = $applicationModel->updateStatus($applicationId, $status, $notes);

        if ($result) {
            $statusText = [
                'pending' => 'venter',
                'reviewed' => 'under vurdering',
                'accepted' => 'akseptert',
                'rejected' => 'avslått'
            ];
            $this->app->session()->set('success_message', 'Søknadsstatus oppdatert til: ' . $statusText[$status] . '.');
        } else {
            $this->app->session()->set('error_message', 'Kunne ikke oppdatere søknadsstatus. Prøv igjen.');
        }

        $this->app->redirect('/positions/' . $id . '/applicants');
    }

    /**
    * Withdraw an application
    * 
    * Allows a user to withdraw their application for a position.
    * 
    * Route: POST /applications/{applicationId}/withdraw
    * 
    * @param int $applicationId The application ID
    * @return void
    */
    public function withdraw($applicationId) {
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

        // Get application details
        $applicationModel = new Application($this->app->db());
        $application = $applicationModel->findById($applicationId);
        
        if (!$application || $application['user_id'] !== $userId) {
            $this->app->session()->set('error_message', 'Søknaden ble ikke funnet eller du har ikke tilgang til å trekke den tilbake.');
            $this->app->redirect('/min-side');
            return;
        }

        // Delete the application
        $result = $applicationModel->delete($applicationId, $userId);

        if ($result) {
            $this->app->session()->set('success_message', 'Søknaden din er trukket tilbake.');
        } else {
            $this->app->session()->set('error_message', 'Kunne ikke trekke tilbake søknaden. Prøv igjen senere.');
        }

        $this->app->redirect('/min-side');
    }
}