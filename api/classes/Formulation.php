<?php
require_once __DIR__ . '/Database.php';

class Formulation {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAllFormulations($page = 1, $limit = 50) {
        try {
            $offset = ($page - 1) * $limit;
            $total = $this->db->selectOne("SELECT COUNT(*) as count FROM herb_disease_relations")['count'];
            $formulations = $this->db->select(
                "SELECT hdr.id, hdr.herb_id, hdr.disease_id, hdr.effectiveness, hdr.dosage_notes, h.name AS herb_name, d.name AS disease_name " .
                "FROM herb_disease_relations hdr " .
                "JOIN herbs h ON hdr.herb_id = h.id " .
                "JOIN diseases d ON hdr.disease_id = d.id " .
                "ORDER BY hdr.id DESC LIMIT ? OFFSET ?",
                [$limit, $offset]
            );

            return [
                'success' => true,
                'data' => $formulations,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to fetch formulations: ' . $e->getMessage()];
        }
    }

    public function getFormulationById($id) {
        try {
            $formulation = $this->db->selectOne(
                "SELECT hdr.id, hdr.herb_id, hdr.disease_id, hdr.effectiveness, hdr.dosage_notes, h.name AS herb_name, d.name AS disease_name " .
                "FROM herb_disease_relations hdr " .
                "JOIN herbs h ON hdr.herb_id = h.id " .
                "JOIN diseases d ON hdr.disease_id = d.id " .
                "WHERE hdr.id = ?",
                [$id]
            );
            if (!$formulation) {
                return ['success' => false, 'message' => 'Formulation not found'];
            }
            return ['success' => true, 'data' => $formulation];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to fetch formulation: ' . $e->getMessage()];
        }
    }

    public function addFormulation($data) {
        try {
            if (empty($data['herb_id']) || empty($data['disease_id'])) {
                return ['success' => false, 'message' => 'Herb and disease IDs are required'];
            }

            $existing = $this->db->selectOne(
                "SELECT * FROM herb_disease_relations WHERE herb_id = ? AND disease_id = ?",
                [$data['herb_id'], $data['disease_id']]
            );

            if ($existing) {
                return ['success' => false, 'message' => 'Formulation already exists for this herb and disease'];
            }

            $relationId = $this->db->insert(
                "INSERT INTO herb_disease_relations (herb_id, disease_id, effectiveness, dosage_notes) VALUES (?, ?, ?, ?)",
                [
                    $data['herb_id'],
                    $data['disease_id'],
                    $data['effectiveness'] ?? 'supportive',
                    $data['dosage_notes'] ?? ''
                ]
            );

            return ['success' => true, 'message' => 'Formulation added successfully', 'formulation_id' => $relationId];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to add formulation: ' . $e->getMessage()];
        }
    }

    public function updateFormulation($id, $data) {
        try {
            $updates = [];
            $params = [];
            $allowedFields = ['herb_id', 'disease_id', 'effectiveness', 'dosage_notes'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "{$field} = ?";
                    $params[] = $data[$field];
                }
            }

            if (empty($updates)) {
                return ['success' => false, 'message' => 'No fields to update'];
            }

            $params[] = $id;
            $this->db->update("UPDATE herb_disease_relations SET " . implode(', ', $updates) . " WHERE id = ?", $params);
            return ['success' => true, 'message' => 'Formulation updated successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to update formulation: ' . $e->getMessage()];
        }
    }

    public function deleteFormulation($id) {
        try {
            $deleted = $this->db->delete("DELETE FROM herb_disease_relations WHERE id = ?", [$id]);
            if (!$deleted) {
                return ['success' => false, 'message' => 'Formulation not found or already deleted'];
            }
            return ['success' => true, 'message' => 'Formulation deleted successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to delete formulation: ' . $e->getMessage()];
        }
    }
}
?>