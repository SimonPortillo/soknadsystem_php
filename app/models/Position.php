<?php

namespace app\models;

use flight\database\PdoWrapper;

class Position
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
     * Create a new position
     * 
     * @param int $creatorId The ID of the user creating the position
     * @param string $title The position title
     * @param string $department The department
     * @param string $location The location
     * @param string|null $description Optional detailed description of the position
     * @return bool True on success, false on failure
     */
    public function create(int $creatorId, string $title, string $department, string $location, int $amount, ?string $description = null): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO positions (creator_id, title, department, location, amount, description) 
             VALUES (:creator_id, :title, :department, :location, :amount, :description)'
        );
        
        $result = $stmt->execute([
            ':creator_id' => $creatorId,
            ':title' => $title,
            ':department' => $department,
            ':location' => $location,
            ':amount' => $amount,
            ':description' => $description
        ]);

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
     * Get all positions created by a specific user
     *
     * @param int $userId The user ID (creator_id)
     * @param bool $includeCreator Whether to include creator information
     * @param bool $includeApplicationCount Whether to include application count
     * @return array Array of positions
     */
        public function findByCreatorId(int $userId, bool $includeCreator = true, bool $includeApplicationCount = true): array {
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

        $sql .= ' WHERE p.creator_id = :user_id ORDER BY p.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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
        $allowedFields = ['title', 'department', 'location', 'amount', 'description'];
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
     * Get the total number of positions
     * 
     * used for displaying count in navbar for logged in users
     *
     * @return int
     */
    public function getCount(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) as count FROM positions');
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
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