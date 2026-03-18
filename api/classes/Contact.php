<?php
require_once __DIR__ . '/Database.php';

class Contact {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function submitMessage($data) {
        try {
            // Validate required fields
            if (empty($data['name']) || empty($data['email']) || empty($data['message'])) {
                return ['success' => false, 'message' => 'Name, email, and message are required'];
            }

            // Insert contact message
            $messageId = $this->db->insert(
                "INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)",
                [
                    $data['name'],
                    $data['email'],
                    $data['subject'] ?? null,
                    $data['message']
                ]
            );

            return [
                'success' => true,
                'message' => 'Message sent successfully. We will get back to you soon!',
                'message_id' => $messageId
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to send message: ' . $e->getMessage()];
        }
    }

    public function getMessages($status = null, $page = 1, $limit = 25) {
        try {
            $offset = ($page - 1) * $limit;

            $sql = "SELECT * FROM contact_messages";
            $params = [];

            if ($status) {
                $sql .= " WHERE status = ?";
                $params[] = $status;
            }

            $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $messages = $this->db->select($sql, $params);

            // Get total count
            $countSql = "SELECT COUNT(*) as count FROM contact_messages";
            $countParams = $status ? [$status] : [];
            if ($status) {
                $countSql .= " WHERE status = ?";
            }
            $total = $this->db->selectOne($countSql, $countParams)['count'];

            return [
                'success' => true,
                'data' => $messages,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get messages: ' . $e->getMessage()];
        }
    }

    public function updateMessageStatus($messageId, $status) {
        try {
            $updateData = ['status' => $status];

            if ($status === 'replied') {
                $updateData['replied_at'] = date('Y-m-d H:i:s');
            }

            $updates = [];
            $params = [];

            foreach ($updateData as $field => $value) {
                $updates[] = "$field = ?";
                $params[] = $value;
            }

            $params[] = $messageId;

            $sql = "UPDATE contact_messages SET " . implode(', ', $updates) . " WHERE id = ?";

            $this->db->update($sql, $params);

            return ['success' => true, 'message' => 'Message status updated successfully'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to update message status: ' . $e->getMessage()];
        }
    }

    public function deleteMessage($messageId) {
        try {
            $this->db->delete("DELETE FROM contact_messages WHERE id = ?", [$messageId]);

            return ['success' => true, 'message' => 'Message deleted successfully'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to delete message: ' . $e->getMessage()];
        }
    }
}
?>