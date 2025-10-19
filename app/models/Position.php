<?php

namespace app\models;

use flight\database\PdoWrapper;

class Position
{
    /**
     * @var int
     */
    public $id;
    
    /**
     * @var int
     */
    public $creator_id;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $department;

    /**
     * @var string
     */
    public $location;

    /**
     * @var string|null
     */
    public $description;

    /**
     * @var string
     */
    public $created_at;

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
     * Create a new position
     * 
     * @param int $creatorId The ID of the user creating the position
     * @param string $title The position title
     * @param string $department The department
     * @param string $location The location
     * @param string|null $description Optional detailed description of the position
     * @return bool True on success, false on failure
     */
    public function create(int $creatorId, string $title, string $department, string $location, ?string $description = null): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO positions (creator_id, title, department, location, description) 
             VALUES (:creator_id, :title, :department, :location, :description)'
        );
        
        $result = $stmt->execute([
            ':creator_id' => $creatorId,
            ':title' => $title,
            ':department' => $department,
            ':location' => $location,
            ':description' => $description
        ]);
        
        if ($result) {
            $this->id = (int) $this->db->lastInsertId();
            $this->creator_id = $creatorId;
            $this->title = $title;
            $this->department = $department;
            $this->location = $location;
            $this->description = $description;
        }
        
        return $result;
    }

    /**
     * Get all positions
     * 
     * @param bool $includeCreator Whether to include creator information
     * @param bool $includeApplicationCount Whether to include application count
     * @return array Array of positions
     */
    public function getAll(bool $includeCreator = true, bool $includeApplicationCount = true): array
    {
        $sql = 'SELECT p.*';
        
        if ($includeCreator) {
            $sql .= ', u.username as creator_username, u.full_name as creator_full_name';
        }
        
        if ($includeApplicationCount) {
            $sql .= ', (SELECT COUNT(*) FROM applications a WHERE a.position_id = p.id) as application_count';
        }
        
        $sql .= ' FROM positions p';
        
        if ($includeCreator) {
            $sql .= ' LEFT JOIN users u ON p.creator_id = u.id';
        }
        
        $sql .= ' ORDER BY p.created_at DESC';
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Find position by ID
     * 
     * @param int $id The position ID
     * @param bool $includeCreator Whether to include creator information
     * @param bool $includeApplicationCount Whether to include application count
     * @return array|null Position data or null if not found
     */
    public function findById(int $id, bool $includeCreator = true, bool $includeApplicationCount = true): ?array
    {
        $sql = 'SELECT p.*';
        
        if ($includeCreator) {
            $sql .= ', u.username as creator_username, u.full_name as creator_full_name';
        }
        
        if ($includeApplicationCount) {
            $sql .= ', (SELECT COUNT(*) FROM applications a WHERE a.position_id = p.id) as application_count';
        }
        
        $sql .= ' FROM positions p';
        
        if ($includeCreator) {
            $sql .= ' LEFT JOIN users u ON p.creator_id = u.id';
        }
        
        $sql .= ' WHERE p.id = :id LIMIT 1';
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Update position
     * 
     * @param int $id The position ID
     * @param array $data Array of fields to update
     * @return bool True on success, false on failure
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = ['title', 'department', 'location', 'description'];
        $updates = [];
        $params = [':id' => $id];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $sql = 'UPDATE positions SET ' . implode(', ', $updates) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }

    /**
     * Delete position
     * 
     * @param int $id The position ID
     * @return bool True on success, false on failure
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM positions WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }
}