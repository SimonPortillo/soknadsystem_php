<?php

namespace app\models;

use flight\database\PdoWrapper;

class Application
{
    /**
     * @var PdoWrapper
     */
    private $db;

    /**
     * Constructor
     */
    public function __construct(PdoWrapper $db)
    {
        $this->db = $db;
    }
    /**
     * Create a new application
     * 
     * @param int $positionId The position ID
     * @param int $userId The user ID
     * @param int $cvDocumentId The CV document ID (required)
     * @param int $coverLetterId The cover letter document ID (required)
     * @param string|null $notes Optional notes with the application
     * @return bool True on success, false on failure
     */
    public function create(int $positionId, int $userId, int $cvDocumentId, int $coverLetterId, ?string $notes = null): bool
    {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO applications (position_id, user_id, cv_document_id, cover_letter_document_id, notes) 
                VALUES (:position_id, :user_id, :cv_document_id, :cover_letter_document_id, :notes)'
            );
            
            $result = $stmt->execute([
                ':position_id' => $positionId,
                ':user_id' => $userId,
                ':cv_document_id' => $cvDocumentId,
                ':cover_letter_document_id' => $coverLetterId,
                ':notes' => $notes
            ]);
            
            return $result;
        } catch (\PDOException $e) {
            // Handle duplicate application
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
    }

    /**
     * Find application by ID
     * 
     * @param int $applicationId The application ID
     * @return array|null Application data or null if not found
     */
    public function findById(int $applicationId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM applications WHERE id = :id'
        );
        $stmt->execute([':id' => $applicationId]);
        
        $application = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $application ?: null;
    }

    /**
     * Get applications by position ID
     * 
     * @param int $positionId The position ID
     * @return array Array of applications
     */
    public function getByPosition(int $positionId): array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, u.username, u.full_name, u.email
             FROM applications a
             JOIN users u ON a.user_id = u.id
             WHERE a.position_id = :position_id
             ORDER BY a.application_date DESC'
        );
        $stmt->execute([':position_id' => $positionId]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get applications with full details by position ID
     * Includes applicant information and document details
     * 
     * @param int $positionId The position ID
     * @return array Array of applications with applicant and document details
     */
    public function getByPositionWithDetails(int $positionId): array
    {
        $stmt = $this->db->prepare(
            'SELECT 
                a.id, 
                a.position_id, 
                a.user_id, 
                a.cv_document_id, 
                a.cover_letter_document_id, 
                a.notes, 
                a.status, 
                a.application_date,
                u.username, 
                u.full_name, 
                u.email, 
                u.phone,
                cv.original_name as cv_name,
                cv.file_path as cv_path,
                cl.original_name as cover_letter_name,
                cl.file_path as cover_letter_path
             FROM applications a
             JOIN users u ON a.user_id = u.id
             LEFT JOIN documents cv ON a.cv_document_id = cv.id
             LEFT JOIN documents cl ON a.cover_letter_document_id = cl.id
             WHERE a.position_id = :position_id
             ORDER BY a.application_date DESC'
        );
        $stmt->execute([':position_id' => $positionId]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get applications by user ID
     * 
     * @param int $userId The user ID
     * @return array Array of applications with position details
     */
    public function getByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, p.title, p.department, p.location
             FROM applications a
             JOIN positions p ON a.position_id = p.id
             WHERE a.user_id = :user_id
             ORDER BY a.application_date DESC'
        );
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Update application status
     * 
     * @param int $applicationId The application ID
     * @param string $status The new status (pending, reviewed, accepted, rejected)
     * @param string|null $notes Optional notes about the status update
     * @return bool True on success, false on failure
     */
    public function updateStatus(int $applicationId, string $status, ?string $notes = null): bool
    {
        if (!in_array($status, ['pending', 'reviewed', 'accepted', 'rejected'])) {
            return false;
        }
        
        $params = [
            ':id' => $applicationId,
            ':status' => $status
        ];
        
        $sql = 'UPDATE applications SET status = :status';
        
        if ($notes !== null) {
            $sql .= ', notes = :notes';
            $params[':notes'] = $notes;
        }
        
        $sql .= ' WHERE id = :id';
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Check if a user has already applied to a position
     * 
     * @param int $positionId The position ID
     * @param int $userId The user ID
     * @return bool True if user has applied, false otherwise
     */
    public function hasApplied(int $positionId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM applications 
             WHERE position_id = :position_id AND user_id = :user_id'
        );
        $stmt->execute([
            ':position_id' => $positionId,
            ':user_id' => $userId
        ]);
        
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Delete an application
     * 
     * @param int $applicationId The application ID
     * @param int $userId The user ID (for security check)
     * @return bool True on success, false on failure
     */
    public function delete(int $applicationId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM applications 
             WHERE id = :id AND user_id = :user_id'
        );
        return $stmt->execute([
            ':id' => $applicationId,
            ':user_id' => $userId
        ]);
    }

    /**
     * Delete an application by ID (admin only)
     * 
     * @param int $applicationId The application ID
     * @return bool True on success, false on failure
     */
    public function deleteById(int $applicationId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM applications WHERE id = :id');
        return $stmt->execute([':id' => $applicationId]);
    }

    /**
     * Delete all applications for a user
     * 
     * @param int $userId The user ID
     * @return bool True on success, false on failure
     */
    public function deleteAllByUserId(int $userId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM applications WHERE user_id = :user_id');
        return $stmt->execute([':user_id' => $userId]);
    }

    /**
     * Get all applications (for admin)
     *
     * @return array Array of applications with position and user details
     */
    public function getAll(): array
    {
        $stmt = $this->db->prepare(
            'SELECT 
                a.id, 
                a.position_id, 
                a.user_id, 
                a.status,
                a.notes,
                a.application_date,
                p.title as position_title,
                p.department,
                p.location,
                u.username,
                u.email,
                u.full_name
             FROM applications a
             JOIN positions p ON a.position_id = p.id
             JOIN users u ON a.user_id = u.id
             ORDER BY a.application_date DESC'
        );
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
