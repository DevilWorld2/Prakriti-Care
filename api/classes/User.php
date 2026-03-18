<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/JWT.php';

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function register($data) {
        try {
            // Validate required fields
            if (empty($data['fullname']) || empty($data['email']) || empty($data['password'])) {
                return ['success' => false, 'message' => 'All fields are required'];
            }

            // Check if email already exists
            $existing = $this->db->selectOne("SELECT id FROM users WHERE email = ?", [$data['email']]);
            if ($existing) {
                return ['success' => false, 'message' => 'Email already registered'];
            }

            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

            // Insert user
            $userId = $this->db->insert(
                "INSERT INTO users (fullname, email, password, age, gender, phone, address) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $data['fullname'],
                    $data['email'],
                    $hashedPassword,
                    $data['age'] ?? null,
                    $data['gender'] ?? null,
                    $data['phone'] ?? null,
                    $data['address'] ?? null
                ]
            );

            return [
                'success' => true,
                'message' => 'Registration successful',
                'user' => [
                    'id' => $userId,
                    'fullname' => $data['fullname'],
                    'email' => $data['email']
                ]
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }

    public function login($email, $password) {
        try {
            // Get user by email
            $user = $this->db->selectOne("SELECT * FROM users WHERE email = ? AND is_active = 1", [$email]);

            if (!$user || !password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }

            // Generate JWT token
            $token = JWT::encode([
                'user_id' => $user['id'],
                'email' => $user['email'],
                'type' => 'user'
            ]);

            // Store session
            $this->createSession($user['id'], $token);

            return [
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'fullname' => $user['fullname'],
                    'email' => $user['email']
                ]
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }

    public function getProfile($userId) {
        try {
            $user = $this->db->selectOne(
                "SELECT id, fullname, email, age, gender, phone, address, medical_history, allergies, lifestyle_habits, created_at FROM users WHERE id = ? AND is_active = 1",
                [$userId]
            );

            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }

            return [
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'fullname' => $user['fullname'],
                    'email' => $user['email'],
                    'age' => $user['age'],
                    'gender' => $user['gender'],
                    'phone' => $user['phone'],
                    'address' => $user['address'],
                    'medicalHistory' => $user['medical_history'],
                    'allergies' => $user['allergies'],
                    'lifestyleHabits' => $user['lifestyle_habits'],
                    'memberSince' => $user['created_at']
                ]
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get profile: ' . $e->getMessage()];
        }
    }

    public function updateProfile($userId, $data) {
        try {
            $updates = [];
            $params = [];

            $allowedFields = ['fullname', 'age', 'gender', 'phone', 'address', 'medical_history', 'allergies', 'lifestyle_habits'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }

            if (empty($updates)) {
                return ['success' => false, 'message' => 'No fields to update'];
            }

            $params[] = $userId;
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";

            $this->db->update($sql, $params);

            return ['success' => true, 'message' => 'Profile updated successfully'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to update profile: ' . $e->getMessage()];
        }
    }

    private function createSession($userId, $token) {
        // Remove expired sessions
        $this->db->delete("DELETE FROM user_sessions WHERE expires_at < NOW()");

        // Create new session
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $this->db->insert(
            "INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)",
            [$userId, $token, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', $expiresAt]
        );
    }

    public function validateSession($token) {
        $session = $this->db->selectOne(
            "SELECT u.* FROM user_sessions us JOIN users u ON us.user_id = u.id WHERE us.session_token = ? AND us.expires_at > NOW() AND u.is_active = 1",
            [$token]
        );
        return $session;
    }
}
?>