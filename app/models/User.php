<?php

namespace app\models;

use flight\database\PdoWrapper;

class User
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string|null
     */
    private $full_name;

    /**
     * @var string|null
     */
    private $phone;

    /**
     * @var string
     */
    private $role;

    /**
     * @var bool
     */
    private $is_active;

    /**
     * @var string
     */
    private $created_at;

    /**
     * @var string
     */
    private $updated_at;

    /**
     * @var int
     */
    private $failed_attempts;

    /**
     * @var string|null
     */
    private $lockout_until;

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

    // Getters

    public function getUsername(): string {
        return $this->username;
    }
    public function getEmail(): string {
        return $this->email;
    }
    public function getId(): int {
        return $this->id;
    }
    public function getRole(): string {
        return $this->role;
    }
    public function getFullName(): ?string {
        return $this->full_name;
    }
    public function getCreatedAt(): string {
        return $this->created_at;
    }
    public function getPhone(): ?string {
        return $this->phone;
    }
    public function getFailedAttempts(): int {
        return $this->failed_attempts;
    }
    public function getLockoutUntil(): ?string {
        return $this->lockout_until;
    }

    // Setters


    // Other methods

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
    public function create(string $username, string $password, string $email, ?string $full_name = null, ?string $phone = null, string $role = 'student'): bool {

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $username = strtolower($username);
        $email = strtolower($email);
        
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
        $user->failed_attempts = (int) $data['failed_attempts'];
        $user->lockout_until = $data['lockout_until'];
        
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
        $user->failed_attempts = (int) $data['failed_attempts'];
        $user->lockout_until = $data['lockout_until'];  
        
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
        $user->failed_attempts = (int) $data['failed_attempts'];
        $user->lockout_until = $data['lockout_until'];
        
        return $user;
    }

    /**
     * Update user profile
     * 
     * @param int $userId The user ID to update
     * @param array $data Array of fields to update (only full_name and phone are allowed)
     * @return bool True on success, false on failure
     */
    public function update(int $userId, array $data): bool
    {
        // Only allow updating specific fields
        $allowedFields = ['full_name', 'phone'];
        $updates = [];
        $params = [':id' => $userId];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $sql = 'UPDATE users SET ' . implode(', ', $updates) . ', updated_at = NOW() WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }
    /**
     * Increment the failed login attempts counter for a user.
     *
     * @param int $userId The ID of the user whose failed attempts should be incremented
     * @return void
    */
    public function incrementFailedAttempts(int $userId): void {
        $stmt = $this->db->prepare('UPDATE users SET failed_attempts = failed_attempts + 1 WHERE id = :id');
        $stmt->execute([':id' => $userId]);
    }

    /**
     * reset the failed login attempts counter for a user.
     *
     * @param int $userId The ID of the user whose failed attempts should be reset
     * @return void
    */
    public function resetFailedAttempts(int $userId): void {
        $stmt = $this->db->prepare('UPDATE users SET failed_attempts = 0, lockout_until = NULL WHERE id = :id');
        $stmt->execute([':id' => $userId]);
    }

    /**
     * lock the user account for a specified number of minutes.
     *
     * @param int $userId The ID of the user whose account should be locked
     * @param int $minutes The number of minutes to lock the account for
     * @return void
    */
    public function lockAccount(int $userId): void {
        $lockoutUntil = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $stmt = $this->db->prepare('UPDATE users SET lockout_until = :lockout_until, failed_attempts = 0 WHERE id = :id');
        $stmt->execute([':lockout_until' => $lockoutUntil, ':id' => $userId]);
    }

    /**
     * Create a password reset token
     * 
     * Generates a unique token for password reset and stores it in the database
     * along with an expiration time (1 hour from creation).
     * 
     * @param int $userId The ID of the user requesting the password reset
     * @return string The generated reset token
     */
    public function createPasswordResetToken(int $userId): string
    {
        $token = bin2hex(random_bytes(16));
    
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $this->db->prepare(
            'UPDATE users SET reset_token = :reset_token, reset_token_expires_at = :expires_at WHERE id = :id'
        );
        $stmt->execute([
            ':reset_token' => $token,
            ':expires_at' => $expiresAt,
            ':id' => $userId
        ]);

        return $token;
    }

    /**
     * Get user ID by reset token
     * 
     * Validates the reset token and checks if it's still valid (not expired).
     * 
     * @param string $token The password reset token
     * @return int|null User ID if token is valid, null otherwise
     */
    public function getUserIdByResetToken(string $token): ?int
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM users 
             WHERE reset_token = :token 
             AND reset_token_expires_at > NOW() 
             LIMIT 1'
        );
        $stmt->execute([':token' => $token]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ? (int) $result['id'] : null;
    }

    /**
     * Invalidate a reset token
     * 
     * Clears the reset token and expiration time after it has been used
     * or when it needs to be invalidated for security reasons.
     * 
     * @param string $token The password reset token to invalidate
     * @return bool True on success, false on failure
     */
    public function invalidateResetToken(string $token): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users 
             SET reset_token = NULL, reset_token_expires_at = NULL 
             WHERE reset_token = :token'
        );
        return $stmt->execute([':token' => $token]);
    }

    /**
     * Update user password
     * 
     * Updates the user's password with a new hashed password.
     * Also resets failed login attempts and clears any account lockout.
     * 
     * @param int $userId The ID of the user
     * @param string $newPassword The new plain-text password (will be hashed)
     * @return bool True on success, false on failure
     */
    public function updatePassword(int $userId, string $newPassword): bool
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare(
            'UPDATE users 
             SET password = :password, 
                 failed_attempts = 0, 
                 lockout_until = NULL,
                 updated_at = NOW() 
             WHERE id = :id'
        );
        
        return $stmt->execute([
            ':password' => $hashedPassword,
            ':id' => $userId
        ]);
    }

    /**
     * Delete a user by ID
     *
     * @param int $userId The ID of the user to delete
     * @return bool True on success, false on failure
     */
    public function delete(int $userId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id = :id');
        return $stmt->execute([':id' => $userId]);
    }
}