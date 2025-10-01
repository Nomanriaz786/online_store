<?php
/**
 * User Model
 * Handles all user-related database operations
 */
require_once __DIR__ . '/BaseModel.php';

class User extends BaseModel
{
    protected $table = 'users';
    
    /**
     * Create a new user with validation
     */
    public function createUser($userData)
    {
        // Validate required fields
        $errors = $this->validate($userData, ['username', 'email', 'password', 'first_name', 'last_name']);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Sanitize data
        $userData = $this->sanitize($userData);
        
        // Additional validations
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'errors' => ['Invalid email format']];
        }
        
        if (strlen($userData['password']) < 6) {
            return ['success' => false, 'errors' => ['Password must be at least 6 characters']];
        }
        
        // Check if username or email already exists
        if ($this->findByUsername($userData['username'])) {
            return ['success' => false, 'errors' => ['Username already exists']];
        }
        
        if ($this->findByEmail($userData['email'])) {
            return ['success' => false, 'errors' => ['Email already exists']];
        }
        
        // Hash password and use correct column name
        $userData['password_hash'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        unset($userData['password']); // Remove the original password field
        $userData['created_at'] = date('Y-m-d H:i:s');
        $userData['role'] = $userData['role'] ?? 'user';
        
        $userId = $this->create($userData);
        
        if ($userId) {
            return ['success' => true, 'user_id' => $userId];
        } else {
            return ['success' => false, 'errors' => ['Failed to create user']];
        }
    }
    
    /**
     * Authenticate user login
     */
    public function authenticate($usernameOrEmail, $password)
    {
        // Find user by username or email
        $user = $this->findByUsername($usernameOrEmail) ?? $this->findByEmail($usernameOrEmail);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid password'];
        }
        
        // Update last login
        $this->update($user['id'], ['last_login' => date('Y-m-d H:i:s')]);
        
        // Remove password from returned data
        unset($user['password_hash']);
        
        return ['success' => true, 'user' => $user];
    }
    
    /**
     * Find user by username
     */
    public function findByUsername($username)
    {
        return $this->findOne(['username' => $username]);
    }
    
    /**
     * Find user by email
     */
    public function findByEmail($email)
    {
        return $this->findOne(['email' => $email]);
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $profileData)
    {
        // Remove sensitive fields that shouldn't be updated directly
        unset($profileData['password'], $profileData['password_hash'], $profileData['role'], $profileData['created_at']);
        
        // Sanitize data
        $profileData = $this->sanitize($profileData);
        
        // Validate email if provided
        if (isset($profileData['email'])) {
            if (!filter_var($profileData['email'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'errors' => ['Invalid email format']];
            }
            
            // Check if email is already used by another user
            $existingUser = $this->findByEmail($profileData['email']);
            if ($existingUser && $existingUser['id'] != $userId) {
                return ['success' => false, 'errors' => ['Email already in use']];
            }
        }
        
        $profileData['updated_at'] = date('Y-m-d H:i:s');
        
        if ($this->update($userId, $profileData)) {
            return ['success' => true, 'message' => 'Profile updated successfully'];
        } else {
            return ['success' => false, 'errors' => ['Failed to update profile']];
        }
    }
    
    /**
     * Change user password
     */
    public function changePassword($userId, $currentPassword, $newPassword)
    {
        $user = $this->find($userId);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        if (!password_verify($currentPassword, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'New password must be at least 6 characters'];
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        if ($this->update($userId, ['password_hash' => $hashedPassword, 'updated_at' => date('Y-m-d H:i:s')])) {
            return ['success' => true, 'message' => 'Password changed successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to change password'];
        }
    }
    
    /**
     * Update profile picture
     */
    public function updateProfilePicture($userId, $picturePath)
    {
        return $this->update($userId, [
            'profile_picture' => $picturePath,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
}