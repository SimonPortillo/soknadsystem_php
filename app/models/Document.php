<?php
namespace app\models;

use flight\database\PdoWrapper;

class Document 
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $user_id;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $file_path;

    /**
     * @var string
     */
    public $original_name;

    /**
     * @var string
     */
    public $mime_type;

    /**
     * @var string
     */
    public $uploaded_at;

    
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
     * Create a new document record
     * 
     * @param int $userId The user ID
     * @param string $type The document type ('cv' or 'cover_letter')
     * @param string $filePath The relative file path
     * @param string $originalName The original filename
     * @param string $mimeType The MIME type
     * @return bool True on success, false on failure
     */
    public function create(int $userId, string $type, string $filePath, string $originalName, string $mimeType): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO documents (user_id, type, file_path, original_name, mime_type) 
             VALUES (:user_id, :type, :file_path, :original_name, :mime_type)'
        );
        
        return $stmt->execute([
            ':user_id' => $userId,
            ':type' => $type,
            ':file_path' => $filePath,
            ':original_name' => $originalName,
            ':mime_type' => $mimeType
        ]);
    }

    /**
     * Find documents by user ID and type
     * 
     * @param int $userId The user ID
     * @param string|null $type Optional document type filter
     * @return array Array of documents
     */
    public function findByUser(int $userId, ?string $type = null): array
    {
        if ($type) {
            $stmt = $this->db->prepare('SELECT * FROM documents WHERE user_id = :user_id AND type = :type ORDER BY uploaded_at DESC');
            $stmt->execute([':user_id' => $userId, ':type' => $type]);
        } else {
            $stmt = $this->db->prepare('SELECT * FROM documents WHERE user_id = :user_id ORDER BY uploaded_at DESC');
            $stmt->execute([':user_id' => $userId]);
        }
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Delete old document of the same type for a user
     * 
     * @param int $userId The user ID
     * @param string $type The document type
     * @return bool True on success, false on failure
     */
    public function deleteByUserAndType(int $userId, string $type): bool
    {
        $stmt = $this->db->prepare('DELETE FROM documents WHERE user_id = :user_id AND type = :type');
        return $stmt->execute([':user_id' => $userId, ':type' => $type]);
    }

    /**
     * Delete all documents for a user
     * 
     * This method deletes both the physical files and database records
     * for all documents belonging to a specific user.
     * 
     * @param int $userId The user ID
     * @return bool True on success, false on failure
     */
    public function deleteByUser(int $userId): bool
    {
        // First, fetch all documents for the user
        $documents = $this->findByUser($userId);
        
        // Delete physical files
        foreach ($documents as $doc) {
            $filePath = __DIR__ . '/../../uploads/' . $doc['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        // Try to remove the user's directory if it's empty
        $userDir = __DIR__ . '/../../uploads/users/' . $userId . '/';
        if (is_dir($userDir)) {
            // Remove directory only if empty
            @rmdir($userDir);
        }
        
        // Delete database records
        $stmt = $this->db->prepare('DELETE FROM documents WHERE user_id = :user_id');
        return $stmt->execute([':user_id' => $userId]);
    }

    /**
     * Find a document by ID
     * 
     * @param int $documentId The document ID
     * @return array|null Document data or null if not found
     */
    public function findById(int $documentId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM documents WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $documentId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Delete a specific document by ID
     * 
     * This method deletes both the physical file and database record.
     * It verifies that the document belongs to the specified user for security.
     * 
     * @param int $documentId The document ID
     * @param int $userId The user ID (for verification)
     * @return bool True on success, false on failure
     */
    public function deleteById(int $documentId, int $userId): bool
    {
        // First, fetch the document to verify ownership and get file path
        $document = $this->findById($documentId);
        
        if (!$document || $document['user_id'] !== $userId) {
            return false; // Document not found or doesn't belong to user
        }
        
        // Delete physical file
        $filePath = __DIR__ . '/../../uploads/' . $document['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Delete database record
        $stmt = $this->db->prepare('DELETE FROM documents WHERE id = :id AND user_id = :user_id');
        return $stmt->execute([':id' => $documentId, ':user_id' => $userId]);
    }
    
}