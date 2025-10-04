<?php

namespace app\models;

use flight\database\PdoWrapper;

class User
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $username;

    /**
     * @var string
     */
    public $password;

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
     * Create a new user
     */
    public function create(string $username, string $password): bool
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare('INSERT INTO users (username, password) VALUES (:username, :password)');
        $result = $stmt->execute([
            ':username' => $username,
            ':password' => $hashedPassword
        ]);
        
        if ($result) {
            $this->id = (int) $this->db->lastInsertId();
            $this->username = $username;
            $this->password = $hashedPassword;
        }
        
        return $result;
    }

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?self
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        $user = new self($this->db);
        $user->id = $data['id'];
        $user->username = $data['username'];
        $user->password = $data['password'];
        $user->created_at = $data['created_at'];
        
        return $user;
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    /**
     * Find user by ID
     */
    public function findById(int $id): ?self
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        $user = new self($this->db);
        $user->id = $data['id'];
        $user->username = $data['username'];
        $user->password = $data['password'];
        $user->created_at = $data['created_at'];
        
        return $user;
    }
}