<?php
require_once __DIR__ . '/Database.php';

class Recommendation {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->ensureConditionColumn();
    }

    private function ensureConditionColumn() {
        try {
            $columnCheck = $this->db->selectOne(
                "SELECT COUNT(*) as cnt FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'recommendations' AND column_name = 'condition'"
            );

            if (!$columnCheck || intval($columnCheck['cnt'] ?? 0) === 0) {
                $this->db->query("ALTER TABLE recommendations ADD COLUMN `condition` VARCHAR(255) NULL AFTER herb_id");
            }
        } catch (Exception $e) {
            // If database permissions/preconditions prevent schema change, do not break application.
            // Fallback is to skip condition persistence in createRecommendation.
        }
    }

    public function getUserRecommendations($userId, $status = null) {
        try {
            $sql = "SELECT r.*, h.name as herb_name, h.scientific_name, h.dosha_effect, h.benefits, h.dosage as herb_dosage FROM recommendations r JOIN herbs h ON r.herb_id = h.id WHERE r.user_id = ?";
            $params = [$userId];

            if ($status) {
                $sql .= " AND r.status = ?";
                $params[] = $status;
            }

            $sql .= " ORDER BY r.priority DESC, r.created_at DESC";

            $recommendations = $this->db->select($sql, $params);

            return ['success' => true, 'data' => $recommendations];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get recommendations: ' . $e->getMessage()];
        }
    }

    public function createRecommendation($userId, $herbId, $data = []) {
        try {
            $useCondition = false;
            try {
                $columnCheck = $this->db->selectOne(
                    "SELECT COUNT(*) as cnt FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'recommendations' AND column_name = 'condition'"
                );
                if ($columnCheck && intval($columnCheck['cnt'] ?? 0) > 0) {
                    $useCondition = true;
                }
            } catch (Exception $inner) {
                $useCondition = false;
            }

            if ($useCondition) {
                $recId = $this->db->insert(
                    "INSERT INTO recommendations (user_id, assessment_id, herb_id, `condition`, dosage, frequency, duration, instructions, expected_benefits, precautions, priority) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $userId,
                        $data['assessment_id'] ?? null,
                        $herbId,
                        $data['condition'] ?? null,
                        $data['dosage'] ?? null,
                        $data['frequency'] ?? null,
                        $data['duration'] ?? null,
                        $data['instructions'] ?? null,
                        $data['expected_benefits'] ?? null,
                        $data['precautions'] ?? null,
                        $data['priority'] ?? 'medium'
                    ]
                );
            } else {
                $recId = $this->db->insert(
                    "INSERT INTO recommendations (user_id, assessment_id, herb_id, dosage, frequency, duration, instructions, expected_benefits, precautions, priority) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $userId,
                        $data['assessment_id'] ?? null,
                        $herbId,
                        $data['dosage'] ?? null,
                        $data['frequency'] ?? null,
                        $data['duration'] ?? null,
                        $data['instructions'] ?? null,
                        $data['expected_benefits'] ?? null,
                        $data['precautions'] ?? null,
                        $data['priority'] ?? 'medium'
                    ]
                );
            }


            return ['success' => true, 'message' => 'Recommendation created successfully', 'recommendation_id' => $recId];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to create recommendation: ' . $e->getMessage()];
        }
    }

    public function updateRecommendationStatus($recId, $userId, $status) {
        try {
            $updateData = ['status' => $status];

            if ($status === 'completed') {
                $updateData['completed_at'] = date('Y-m-d H:i:s');
            }

            $updates = [];
            $params = [];

            foreach ($updateData as $field => $value) {
                $updates[] = "$field = ?";
                $params[] = $value;
            }

            $params[] = $recId;
            $params[] = $userId;

            $sql = "UPDATE recommendations SET " . implode(', ', $updates) . " WHERE id = ? AND user_id = ?";

            $this->db->update($sql, $params);

            return ['success' => true, 'message' => 'Recommendation status updated successfully'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to update recommendation: ' . $e->getMessage()];
        }
    }

    public function generateRecommendations($userId, $symptoms = null, $dosha = null) {
        try {
            $recommendations = [];

            // If dosha is provided, get herbs for that dosha
            if ($dosha) {
                $herbs = $this->db->select(
                    "SELECT * FROM herbs WHERE is_active = 1 AND (dosha_effect = ? OR dosha_effect = 'tridosha' OR dosha_effect = 'neutral') ORDER BY RAND() LIMIT 5",
                    [$dosha]
                );
            } else {
                // Get general herbs
                $herbs = $this->db->select(
                    "SELECT * FROM herbs WHERE is_active = 1 ORDER BY RAND() LIMIT 5"
                );
            }

            // Create recommendations for each herb
            foreach ($herbs as $herb) {
                $recData = [
                    'assessment_id' => null,
                    'dosage' => $herb['dosage'],
                    'frequency' => 'twice daily',
                    'duration' => '4-6 weeks',
                    'instructions' => $herb['usage_instructions'],
                    'expected_benefits' => $herb['benefits'],
                    'precautions' => $herb['contraindications'],
                    'priority' => 'medium'
                ];

                $result = $this->createRecommendation($userId, $herb['id'], $recData);
                if ($result['success']) {
                    $recommendations[] = [
                        'id' => $result['recommendation_id'],
                        'herb_name' => $herb['name'],
                        'dosage' => $recData['dosage'],
                        'frequency' => $recData['frequency'],
                        'duration' => $recData['duration']
                    ];
                }
            }

            return ['success' => true, 'data' => $recommendations];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to generate recommendations: ' . $e->getMessage()];
        }
    }

    public function getRecommendationHistory($userId) {
        try {
            $history = $this->db->select(
                "SELECT r.*, h.name as herb_name, h.benefits FROM recommendations r JOIN herbs h ON r.herb_id = h.id WHERE r.user_id = ? ORDER BY r.created_at DESC",
                [$userId]
            );

            return ['success' => true, 'data' => $history];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get recommendation history: ' . $e->getMessage()];
        }
    }

    public function searchRecommendations($userId, $query) {
        try {
            $recommendations = $this->db->select(
                "SELECT r.*, h.name as herb_name, h.scientific_name, h.benefits FROM recommendations r JOIN herbs h ON r.herb_id = h.id WHERE r.user_id = ? AND (h.name LIKE ? OR h.benefits LIKE ? OR r.instructions LIKE ?) ORDER BY r.created_at DESC",
                [$userId, "%$query%", "%$query%", "%$query%"]
            );

            return ['success' => true, 'data' => $recommendations];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to search recommendations: ' . $e->getMessage()];
        }
    }

    public function searchHerbsBySymptoms($query) {
        try {
            $cleanQuery = trim(strtolower($query));
            $keywords = array_filter(array_unique(array_map('trim', preg_split('/[\s,;]+/', $cleanQuery))));

            if (empty($keywords)) {
                return ['success' => true, 'keywords' => [], 'count' => 0, 'matched_diseases' => [], 'herbs' => []];
            }

            $diseaseConditions = [];
            $params = [];
            foreach ($keywords as $word) {
                $diseaseConditions[] = "(LOWER(name) LIKE ? OR LOWER(common_symptoms) LIKE ? OR LOWER(ayurvedic_name) LIKE ? OR LOWER(description) LIKE ?)";
                $like = "%$word%";
                for ($i = 0; $i < 4; $i++) {
                    $params[] = $like;
                }
            }

            $matchedDiseases = [];
            if (!empty($diseaseConditions)) {
                $diseaseSql = "SELECT * FROM diseases WHERE is_active = 1 AND (" . implode(' OR ', $diseaseConditions) . ") ORDER BY name ASC";
                $matchedDiseases = $this->db->select($diseaseSql, $params);
            }

            $herbScores = [];
            $herbDetails = [];

            foreach ($matchedDiseases as $disease) {
                $relatedHerbs = $this->db->select(
                    "SELECT h.*, hdr.effectiveness, hdr.dosage_notes, d.name as disease_name FROM herbs h JOIN herb_disease_relations hdr ON h.id = hdr.herb_id JOIN diseases d ON hdr.disease_id = d.id WHERE hdr.disease_id = ? AND h.is_active = 1",
                    [$disease['id']]
                );

                foreach ($relatedHerbs as $herb) {
                    $score = $herbScores[$herb['id']] ?? 0;
                    $score += 50;
                    if (isset($herb['effectiveness']) && strtolower($herb['effectiveness']) === 'primary') {
                        $score += 30;
                    }
                    $herbScores[$herb['id']] = $score;
                    $herbDetails[$herb['id']] = $herb;
                }
            }

            $herbConditions = [];
            $herbParams = [];
            foreach ($keywords as $word) {
                $herbConditions[] = "(LOWER(name) LIKE ? OR LOWER(scientific_name) LIKE ? OR LOWER(sanskrit_name) LIKE ? OR LOWER(description) LIKE ? OR LOWER(benefits) LIKE ? OR LOWER(usage_instructions) LIKE ? OR LOWER(contraindications) LIKE ? OR LOWER(side_effects) LIKE ?)";
                $like = "%$word%";
                for ($i = 0; $i < 8; $i++) {
                    $herbParams[] = $like;
                }
            }

            if (!empty($herbConditions)) {
                $herbSql = "SELECT * FROM herbs WHERE is_active = 1 AND (" . implode(' OR ', $herbConditions) . ") ORDER BY name ASC";
                $herbResults = $this->db->select($herbSql, $herbParams);

                foreach ($herbResults as $herb) {
                    $score = $herbScores[$herb['id']] ?? 0;

                    foreach ($keywords as $word) {
                        if (stripos($herb['name'] ?? '', $word) !== false) $score += 15;
                        if (stripos($herb['scientific_name'] ?? '', $word) !== false) $score += 10;
                        if (stripos($herb['description'] ?? '', $word) !== false) $score += 8;
                        if (stripos($herb['benefits'] ?? '', $word) !== false) $score += 7;
                    }

                    $herbScores[$herb['id']] = max($score, 10);
                    $herbDetails[$herb['id']] = $herb;
                }
            }

            $sortedHerbIds = array_keys($herbScores);
            usort($sortedHerbIds, function($a, $b) use ($herbScores) {
                return $herbScores[$b] <=> $herbScores[$a];
            });

            $herbs = [];
            foreach ($sortedHerbIds as $herbId) {
                $h = $herbDetails[$herbId];
                $herbs[] = [
                    'id' => $h['id'],
                    'name' => $h['name'],
                    'scientific_name' => $h['scientific_name'],
                    'dosha' => [
                        'dosha_effect' => $h['dosha_effect'],
                        'rasa' => $h['rasa'],
                        'virya' => $h['virya'],
                        'vipaka' => $h['vipaka']
                    ],
                    'benefits' => $h['benefits'] ? explode(', ', $h['benefits']) : [],
                    'description' => $h['description'],
                    'usage_instructions' => $h['usage_instructions'],
                    'dosage' => $h['dosage'],
                    'contraindications' => $h['contraindications'],
                    'side_effects' => $h['side_effects'],
                    'effectiveness' => $h['effectiveness'] ?? null,
                    'disease' => $h['disease_name'] ?? null,
                    'score' => $herbScores[$herbId] ?? 0
                ];
            }

            return [
                'success' => true,
                'keywords' => $keywords,
                'count' => count($herbs),
                'matched_diseases' => $matchedDiseases,
                'herbs' => $herbs
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to search herbs by symptoms: ' . $e->getMessage()];
        }
    }
}
?>