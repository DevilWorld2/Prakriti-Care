<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/JWT.php';

class Admin {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function login($email, $password) {
        try {
            // Get admin by email
            $admin = $this->db->selectOne("SELECT * FROM admins WHERE email = ? AND is_active = 1", [$email]);

            if (!$admin || !password_verify($password, $admin['password'])) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }

            // Update last login
            $this->db->update("UPDATE admins SET last_login = NOW() WHERE id = ?", [$admin['id']]);

            // Generate JWT token
            $token = JWT::encode([
                'admin_id' => $admin['id'],
                'email' => $admin['email'],
                'type' => 'admin'
            ]);

            return [
                'success' => true,
                'message' => 'Admin login successful',
                'token' => $token,
                'admin' => [
                    'id' => $admin['id'],
                    'username' => $admin['username'],
                    'email' => $admin['email'],
                    'full_name' => $admin['full_name']
                ]
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Admin login failed: ' . $e->getMessage()];
        }
    }

    public function getUsers($page = 1, $limit = 25) {
        try {
            $offset = ($page - 1) * $limit;

            // Get total count
            $total = $this->db->selectOne("SELECT COUNT(*) as count FROM users")['count'];

            // Get users with pagination
            $users = $this->db->select(
                "SELECT id, fullname, email, dosha_type, is_active, created_at FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?",
                [$limit, $offset]
            );

            return [
                'success' => true,
                'data' => $users,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get users: ' . $e->getMessage()];
        }
    }

    public function getUserDetails($userId) {
        try {
            $user = $this->db->selectOne(
                "SELECT * FROM users WHERE id = ?",
                [$userId]
            );

            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }

            // Get user's assessments
            $assessments = $this->db->select(
                "SELECT * FROM assessments WHERE user_id = ? ORDER BY created_at DESC",
                [$userId]
            );

            // Get user's recommendations
            $recommendations = $this->db->select(
                "SELECT r.*, h.name as herb_name FROM recommendations r JOIN herbs h ON r.herb_id = h.id WHERE r.user_id = ? ORDER BY r.created_at DESC",
                [$userId]
            );

            $user['assessments'] = $assessments;
            $user['recommendations'] = $recommendations;

            return ['success' => true, 'data' => $user];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get user details: ' . $e->getMessage()];
        }
    }

    public function updateUserStatus($userId, $isActive) {
        try {
            $this->db->update(
                "UPDATE users SET is_active = ? WHERE id = ?",
                [$isActive, $userId]
            );

            return ['success' => true, 'message' => 'User status updated successfully'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to update user status: ' . $e->getMessage()];
        }
    }

    public function deleteUser($userId) {
        try {
            // Soft delete by deactivating
            $this->db->update(
                "UPDATE users SET is_active = 0 WHERE id = ?",
                [$userId]
            );

            return ['success' => true, 'message' => 'User deactivated successfully'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to delete user: ' . $e->getMessage()];
        }
    }

    public function getDashboardStats() {
        try {
            $stats = [];

            // Total users
            $stats['total_users'] = $this->db->selectOne("SELECT COUNT(*) as count FROM users")['count'];

            // Active users
            $stats['active_users'] = $this->db->selectOne("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'];

            // Total assessments
            $stats['total_assessments'] = $this->db->selectOne("SELECT COUNT(*) as count FROM assessments")['count'];

            // Total recommendations
            $stats['total_recommendations'] = $this->db->selectOne("SELECT COUNT(*) as count FROM recommendations")['count'];

            // Recommendation status breakdown
            $totalRecs = $stats['total_recommendations'];
            $completed = $this->db->selectOne("SELECT COUNT(*) as count FROM recommendations WHERE status = 'completed'")['count'];
            $pending = $this->db->selectOne("SELECT COUNT(*) as count FROM recommendations WHERE status = 'active'")['count'];
            $failed = $this->db->selectOne("SELECT COUNT(*) as count FROM recommendations WHERE status = 'discontinued'")['count'];

            $stats['recommendation_success_rate'] = $totalRecs > 0 ? round(($completed / $totalRecs) * 100, 1) : 0;
            $stats['recommendation_completed'] = $completed;
            $stats['recommendation_pending'] = $pending;
            $stats['recommendation_failed'] = $failed;

            // Recent failed recommendations
            $stats['recent_failed'] = $this->db->select(
                "SELECT r.id, r.user_id, u.email as user_email, h.name as herb_name, r.status, COALESCE(r.completed_at, r.created_at) AS updated_at FROM recommendations r JOIN users u ON r.user_id = u.id JOIN herbs h ON r.herb_id = h.id WHERE r.status = 'discontinued' ORDER BY COALESCE(r.completed_at, r.created_at) DESC LIMIT 5"
            );

            // Recommendations last month (for trend)
            $stats['total_recommendations_last_month'] = $this->db->selectOne(
                "SELECT COUNT(*) as count FROM recommendations WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            )['count'];

            // Recent assessments (last 30 days)
            $stats['recent_assessments'] = $this->db->selectOne(
                "SELECT COUNT(*) as count FROM assessments WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            )['count'];

            // Contact messages
            $stats['unread_messages'] = $this->db->selectOne("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'unread'")['count'];

            // Herb count
            $stats['total_herbs'] = $this->db->selectOne("SELECT COUNT(*) as count FROM herbs")['count'];

            return ['success' => true, 'data' => $stats];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get dashboard stats: ' . $e->getMessage()];
        }
    }

    public function getRecentActivity($limit = 10) {
        try {
            // Load recent activity from recommendations, assessments, user registrations and contacts.
            $activity = [];

            $recActivity = $this->db->select(
                "SELECT r.id, u.email AS user_email, 'Recommendation' AS action, CONCAT(r.status, ' recommendation for ', h.name) AS detail, COALESCE(r.completed_at, r.created_at) AS time, r.status AS status_tag " .
                "FROM recommendations r " .
                "JOIN users u ON r.user_id = u.id " .
                "JOIN herbs h ON r.herb_id = h.id " .
                "ORDER BY COALESCE(r.completed_at, r.created_at) DESC LIMIT ?",
                [$limit]
            );

            $assessments = $this->db->select(
                "SELECT a.id, u.email AS user_email, 'Assessment' AS action, CONCAT('Dosha assessment (', a.dosha_type, ')') AS detail, a.created_at AS time, 'success' AS status_tag " .
                "FROM assessments a " .
                "JOIN users u ON a.user_id = u.id " .
                "ORDER BY a.created_at DESC LIMIT ?",
                [$limit]
            );

            $userReg = $this->db->select(
                "SELECT id, email AS user_email, 'Registration' AS action, 'New user registered' AS detail, created_at AS time, 'success' AS status_tag " .
                "FROM users " .
                "ORDER BY created_at DESC LIMIT ?",
                [$limit]
            );

            $contacts = $this->db->select(
                "SELECT id, '' AS user_email, 'Contact' AS action, CONCAT(subject, ' (', status, ')') AS detail, created_at AS time, status AS status_tag " .
                "FROM contact_messages " .
                "ORDER BY created_at DESC LIMIT ?",
                [$limit]
            );

            // Merge and sort by time desc
            $activity = array_merge($recActivity, $assessments, $userReg, $contacts);
            usort($activity, function($a, $b) {
                return strtotime($b['time']) - strtotime($a['time']);
            });

            $activity = array_slice($activity, 0, $limit);

            return ['success' => true, 'data' => $activity];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get recent activity: ' . $e->getMessage()];
        }
    }
}

?>