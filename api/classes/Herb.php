<?php
require_once __DIR__ . '/Database.php';

class Herb {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAllHerbs($page = 1, $limit = 50) {
        try {
            $offset = ($page - 1) * $limit;

            // Get total count
            $total = $this->db->selectOne("SELECT COUNT(*) as count FROM herbs WHERE is_active = 1")['count'];

            // Get herbs with pagination
            $herbs = $this->db->select(
                "SELECT * FROM herbs WHERE is_active = 1 ORDER BY name ASC LIMIT ? OFFSET ?",
                [$limit, $offset]
            );

            return [
                'success' => true,
                'data' => $herbs,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get herbs: ' . $e->getMessage()];
        }
    }

    public function searchHerbs($query, $dosha = null) {
        try {
            $sql = "SELECT * FROM herbs WHERE is_active = 1 AND (name LIKE ? OR scientific_name LIKE ? OR sanskrit_name LIKE ? OR description LIKE ? OR benefits LIKE ?)";
            $params = ["%$query%", "%$query%", "%$query%", "%$query%", "%$query%"];

            if ($dosha) {
                $sql .= " AND (dosha_effect = ? OR dosha_effect = 'tridosha' OR dosha_effect = 'neutral')";
                $params[] = $dosha;
            }

            $sql .= " ORDER BY name ASC";

            $herbs = $this->db->select($sql, $params);

            return ['success' => true, 'data' => $herbs];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to search herbs: ' . $e->getMessage()];
        }
    }

    public function getHerbById($herbId) {
        try {
            $herb = $this->db->selectOne(
                "SELECT * FROM herbs WHERE id = ? AND is_active = 1",
                [$herbId]
            );

            if (!$herb) {
                return ['success' => false, 'message' => 'Herb not found'];
            }

            // Get related diseases
            $diseases = $this->db->select(
                "SELECT d.*, hdr.effectiveness, hdr.dosage_notes FROM diseases d JOIN herb_disease_relations hdr ON d.id = hdr.disease_id WHERE hdr.herb_id = ? AND d.is_active = 1",
                [$herbId]
            );

            $herb['related_diseases'] = $diseases;

            return ['success' => true, 'data' => $herb];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get herb: ' . $e->getMessage()];
        }
    }

    public function getHerbsByDosha($dosha) {
        try {
            $herbs = $this->db->select(
                "SELECT * FROM herbs WHERE is_active = 1 AND (dosha_effect = ? OR dosha_effect = 'tridosha' OR dosha_effect = 'neutral') ORDER BY name ASC",
                [$dosha]
            );

            return ['success' => true, 'data' => $herbs];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get herbs by dosha: ' . $e->getMessage()];
        }
    }

    public function getHerbsForDisease($diseaseId) {
        try {
            $herbs = $this->db->select(
                "SELECT h.*, hdr.effectiveness, hdr.dosage_notes FROM herbs h JOIN herb_disease_relations hdr ON h.id = hdr.herb_id WHERE hdr.disease_id = ? AND h.is_active = 1 ORDER BY hdr.effectiveness DESC, h.name ASC",
                [$diseaseId]
            );

            return ['success' => true, 'data' => $herbs];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get herbs for disease: ' . $e->getMessage()];
        }
    }

    public function addHerb($data) {
        try {
            $herbId = $this->db->insert(
                "INSERT INTO herbs (name, scientific_name, sanskrit_name, description, benefits, usage_instructions, dosage, contraindications, side_effects, dosha_effect, rasa, virya, vipaka, part_used) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $data['name'],
                    $data['scientific_name'] ?? null,
                    $data['sanskrit_name'] ?? null,
                    $data['description'] ?? null,
                    $data['benefits'] ?? null,
                    $data['usage_instructions'] ?? null,
                    $data['dosage'] ?? null,
                    $data['contraindications'] ?? null,
                    $data['side_effects'] ?? null,
                    $data['dosha_effect'] ?? 'neutral',
                    $data['rasa'] ?? null,
                    $data['virya'] ?? null,
                    $data['vipaka'] ?? null,
                    $data['part_used'] ?? null
                ]
            );

            return ['success' => true, 'message' => 'Herb added successfully', 'herb_id' => $herbId];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to add herb: ' . $e->getMessage()];
        }
    }

    public function updateHerb($herbId, $data) {
        try {
            $updates = [];
            $params = [];

            $allowedFields = ['name', 'scientific_name', 'sanskrit_name', 'description', 'benefits', 'usage_instructions', 'dosage', 'contraindications', 'side_effects', 'dosha_effect', 'rasa', 'virya', 'vipaka', 'part_used'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }

            if (empty($updates)) {
                return ['success' => false, 'message' => 'No fields to update'];
            }

            $params[] = $herbId;
            $sql = "UPDATE herbs SET " . implode(', ', $updates) . " WHERE id = ?";

            $this->db->update($sql, $params);

            return ['success' => true, 'message' => 'Herb updated successfully'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to update herb: ' . $e->getMessage()];
        }
    }

    public function deleteHerb($herbId) {
        try {
            $this->db->update(
                "UPDATE herbs SET is_active = 0 WHERE id = ?",
                [$herbId]
            );

            return ['success' => true, 'message' => 'Herb deleted successfully'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to delete herb: ' . $e->getMessage()];
        }
    }
}
?>