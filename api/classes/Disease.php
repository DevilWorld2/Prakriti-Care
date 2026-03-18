<?php
require_once __DIR__ . '/Database.php';

class Disease {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAllDiseases($page = 1, $limit = 50) {
        try {
            $offset = ($page - 1) * $limit;
            $total = $this->db->selectOne("SELECT COUNT(*) as count FROM diseases WHERE is_active = 1")['count'];
            $diseases = $this->db->select("SELECT * FROM diseases WHERE is_active = 1 ORDER BY name ASC LIMIT ? OFFSET ?", [$limit, $offset]);

            return [
                'success' => true,
                'data' => $diseases,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to fetch diseases: ' . $e->getMessage()];
        }
    }

    public function getDiseaseById($diseaseId) {
        try {
            $disease = $this->db->selectOne("SELECT * FROM diseases WHERE id = ? AND is_active = 1", [$diseaseId]);
            if (!$disease) {
                return ['success' => false, 'message' => 'Disease not found'];
            }
            return ['success' => true, 'data' => $disease];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to fetch disease: ' . $e->getMessage()];
        }
    }

    public function searchDiseases($query) {
        try {
            $q = trim(strtolower($query));
            if ($q === '') {
                return ['success' => true, 'data' => []];
            }

            $qLike = "%$q%";
            $diseases = $this->db->select(
                "SELECT * FROM diseases WHERE is_active = 1 AND (LOWER(name) LIKE ? OR LOWER(ayurvedic_name) LIKE ? OR LOWER(common_symptoms) LIKE ? OR LOWER(description) LIKE ?) ORDER BY FIELD(LOWER(name), ?) DESC, name ASC",
                [$qLike, $qLike, $qLike, $qLike, $q]
            );

            return ['success' => true, 'data' => $diseases];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to search diseases: ' . $e->getMessage()];
        }
    }

    public function addDisease($data) {
        try {
            $diseaseId = $this->db->insert(
                "INSERT INTO diseases (name, description, ayurvedic_name, dosha_imbalance, common_symptoms) VALUES (?, ?, ?, ?, ?)",
                [
                    $data['name'] ?? '',
                    $data['description'] ?? '',
                    $data['ayurvedic_name'] ?? '',
                    $data['dosha_imbalance'] ?? null,
                    $data['common_symptoms'] ?? ''
                ]
            );
            return ['success' => true, 'message' => 'Disease added successfully', 'disease_id' => $diseaseId];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to add disease: ' . $e->getMessage()];
        }
    }

    public function updateDisease($diseaseId, $data) {
        try {
            $updates = [];
            $params = [];
            $allowedFields = ['name', 'description', 'ayurvedic_name', 'dosha_imbalance', 'common_symptoms', 'is_active'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "{$field} = ?";
                    $params[] = $data[$field];
                }
            }

            if (empty($updates)) {
                return ['success' => false, 'message' => 'No fields to update'];
            }

            $params[] = $diseaseId;
            $this->db->update("UPDATE diseases SET " . implode(', ', $updates) . " WHERE id = ?", $params);
            return ['success' => true, 'message' => 'Disease updated successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to update disease: ' . $e->getMessage()];
        }
    }

    public function deleteDisease($diseaseId) {
        try {
            $this->db->update("UPDATE diseases SET is_active = 0 WHERE id = ?", [$diseaseId]);
            return ['success' => true, 'message' => 'Disease deleted successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to delete disease: ' . $e->getMessage()];
        }
    }
}
?>