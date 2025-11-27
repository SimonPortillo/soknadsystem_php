<?php

namespace app\models;

use flight\database\PdoWrapper;

class User
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
        
        return $result;
    }

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        return $data;
    }

    /**
     * Verify password
     */
    public function verifyPassword(array $user, string $password): bool
    {
        return password_verify($password, $user['password']);
    }

    /**
     * Find user by ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        return $data;
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        return $data;
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



    /**
     * Update user role
     * 
     * @param int $userId The ID of the user to update
     * @param string $role The new role (student, employee, admin)
     * @return bool True on success, false on failure
     */
    public function updateRole(int $userId, string $role): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET role = :role, updated_at = NOW() WHERE id = :id');
        return $stmt->execute([':role' => $role, ':id' => $userId]);
    }

    /**
     * Get all users (for admin)
     *
     * @return array Array of user data
     */
    public function getAll(): array
    {
        $stmt = $this->db->prepare('SELECT id, username, email, full_name, phone, role, is_active, created_at, updated_at FROM users ORDER BY created_at DESC');
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}