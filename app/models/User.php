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
    public $email;

    /**
     * @var string|null
     */
    public $full_name;

    /**
     * @var string|null
     */
    public $phone;

    /**
     * @var string
     */
    public $role;

    /**
     * @var bool
     */
    public $is_active;

    /**
     * @var string
     */
    public $created_at;

    /**
     * @var string
     */
    public $updated_at;

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
     * 
     * @param string $username The username
     * @param string $password The plain-text password (will be hashed)
     * @param string $email The email address
     * @param string|null $full_name The full name (optional)
     * @param string|null $phone The phone number (optional)
     * @param string $role The user role (defaults to 'student')
     */
    public function create(
        string $username, 
        string $password, 
        string $email, 
        ?string $full_name = null, 
        ?string $phone = null, 
        string $role = 'student'
    ): bool {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, password, email, full_name, phone, role, is_active) 
             VALUES (:username, :password, :email, :full_name, :phone, :role, 1)'
        );
        $result = $stmt->execute([
            ':username' => $username,
            ':password' => $hashedPassword,
            ':email' => $email,
            ':full_name' => $full_name,
            ':phone' => $phone,
            ':role' => $role
        ]);
        
        if ($result) {
            $this->id = (int) $this->db->lastInsertId();
            $this->username = $username;
            $this->password = $hashedPassword;
            $this->email = $email;
            $this->full_name = $full_name;
            $this->phone = $phone;
            $this->role = $role;
            $this->is_active = true;
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
        $user->id = (int) $data['id'];
        $user->username = $data['username'];
        $user->password = $data['password'];
        $user->email = $data['email'];
        $user->full_name = $data['full_name'];
        $user->phone = $data['phone'];
        $user->role = $data['role'];
        $user->is_active = (bool) $data['is_active'];
        $user->created_at = $data['created_at'];
        $user->updated_at = $data['updated_at'];
        
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
        $user->id = (int) $data['id'];
        $user->username = $data['username'];
        $user->password = $data['password'];
        $user->email = $data['email'];
        $user->full_name = $data['full_name'];
        $user->phone = $data['phone'];
        $user->role = $data['role'];
        $user->is_active = (bool) $data['is_active'];
        $user->created_at = $data['created_at'];
        $user->updated_at = $data['updated_at'];
        
        return $user;
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?self
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        $user = new self($this->db);
        $user->id = (int) $data['id'];
        $user->username = $data['username'];
        $user->password = $data['password'];
        $user->email = $data['email'];
        $user->full_name = $data['full_name'];
        $user->phone = $data['phone'];
        $user->role = $data['role'];
        $user->is_active = (bool) $data['is_active'];
        $user->created_at = $data['created_at'];
        $user->updated_at = $data['updated_at'];
        
        return $user;
    }
}