<?php
require_once __DIR__ . '/Database.php';

class Assessment {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function createAssessment($userId, $data) {
        try {
            // Calculate dosha scores (simplified version)
            $doshaScores = $this->calculateDoshaScores($data);

            // Determine primary and secondary dosha
            $primaryDosha = $this->getPrimaryDosha($doshaScores);
            $secondaryDosha = $this->getSecondaryDosha($doshaScores);

            // Generate recommendations based on assessment
            $recommendations = $this->generateRecommendationsFromAssessment($userId, $data, $primaryDosha);

            $assessmentId = $this->db->insert(
                "INSERT INTO assessments (user_id, assessment_type, symptoms, medical_history, lifestyle_factors, dosha_scores, primary_dosha, secondary_dosha, recommendations, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $userId,
                    $data['assessment_type'] ?? 'general',
                    $data['symptoms'] ?? null,
                    $data['medical_history'] ?? null,
                    $data['lifestyle_factors'] ?? null,
                    json_encode($doshaScores),
                    $primaryDosha,
                    $secondaryDosha,
                    $recommendations,
                    'completed'
                ]
            );

            // Update user's dosha type
            $this->db->update(
                "UPDATE users SET dosha_type = ? WHERE id = ?",
                [$primaryDosha, $userId]
            );

            return [
                'success' => true,
                'message' => 'Assessment completed successfully',
                'assessment_id' => $assessmentId,
                'dosha_result' => [
                    'primary' => $primaryDosha,
                    'secondary' => $secondaryDosha,
                    'scores' => $doshaScores
                ]
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to create assessment: ' . $e->getMessage()];
        }
    }

    public function getUserAssessments($userId) {
        try {
            $assessments = $this->db->select(
                "SELECT * FROM assessments WHERE user_id = ? ORDER BY created_at DESC",
                [$userId]
            );

            // Decode JSON dosha scores
            foreach ($assessments as &$assessment) {
                if ($assessment['dosha_scores']) {
                    $assessment['dosha_scores'] = json_decode($assessment['dosha_scores'], true);
                }
            }

            return ['success' => true, 'data' => $assessments];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get assessments: ' . $e->getMessage()];
        }
    }

    public function getAssessmentById($assessmentId, $userId) {
        try {
            $assessment = $this->db->selectOne(
                "SELECT * FROM assessments WHERE id = ? AND user_id = ?",
                [$assessmentId, $userId]
            );

            if (!$assessment) {
                return ['success' => false, 'message' => 'Assessment not found'];
            }

            // Decode JSON dosha scores
            if ($assessment['dosha_scores']) {
                $assessment['dosha_scores'] = json_decode($assessment['dosha_scores'], true);
            }

            return ['success' => true, 'data' => $assessment];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get assessment: ' . $e->getMessage()];
        }
    }

    private function calculateDoshaScores($data) {
        // Simplified dosha calculation based on common Ayurvedic principles
        $scores = ['vata' => 0, 'pitta' => 0, 'kapha' => 0];

        // Body type questions
        if (isset($data['body_type'])) {
            switch ($data['body_type']) {
                case 'thin':
                    $scores['vata'] += 3;
                    break;
                case 'medium':
                    $scores['pitta'] += 2;
                    break;
                case 'heavy':
                    $scores['kapha'] += 3;
                    break;
            }
        }

        // Skin type
        if (isset($data['skin_type'])) {
            switch ($data['skin_type']) {
                case 'dry':
                    $scores['vata'] += 2;
                    break;
                case 'oily':
                    $scores['kapha'] += 2;
                    break;
                case 'sensitive':
                    $scores['pitta'] += 2;
                    break;
            }
        }

        // Temperature preference
        if (isset($data['temperature_preference'])) {
            switch ($data['temperature_preference']) {
                case 'cold':
                    $scores['pitta'] += 2;
                    break;
                case 'hot':
                    $scores['vata'] += 2;
                    break;
                case 'moderate':
                    $scores['kapha'] += 1;
                    break;
            }
        }

        // Digestion
        if (isset($data['digestion'])) {
            switch ($data['digestion']) {
                case 'irregular':
                    $scores['vata'] += 3;
                    break;
                case 'strong':
                    $scores['pitta'] += 2;
                    break;
                case 'slow':
                    $scores['kapha'] += 2;
                    break;
            }
        }

        // Energy levels
        if (isset($data['energy_levels'])) {
            switch ($data['energy_levels']) {
                case 'variable':
                    $scores['vata'] += 2;
                    break;
                case 'high':
                    $scores['pitta'] += 2;
                    break;
                case 'steady':
                    $scores['kapha'] += 2;
                    break;
            }
        }

        // Sleep pattern
        if (isset($data['sleep_pattern'])) {
            switch ($data['sleep_pattern']) {
                case 'light':
                    $scores['vata'] += 2;
                    break;
                case 'moderate':
                    $scores['pitta'] += 1;
                    break;
                case 'heavy':
                    $scores['kapha'] += 2;
                    break;
            }
        }

        // Stress response
        if (isset($data['stress_response'])) {
            switch ($data['stress_response']) {
                case 'anxious':
                    $scores['vata'] += 3;
                    break;
                case 'irritable':
                    $scores['pitta'] += 3;
                    break;
                case 'withdrawn':
                    $scores['kapha'] += 2;
                    break;
            }
        }

        return $scores;
    }

    private function getPrimaryDosha($scores) {
        $maxScore = max($scores);
        foreach ($scores as $dosha => $score) {
            if ($score == $maxScore) {
                return $dosha;
            }
        }
        return 'vata'; // default
    }

    private function getSecondaryDosha($scores) {
        $sorted = $scores;
        arsort($sorted);
        $doshsa = array_keys($sorted);
        return $doshsa[1] ?? null;
    }

    private function generateRecommendationsFromAssessment($userId, $data, $primaryDosha) {
        // Generate basic recommendations based on dosha
        $recommendations = [];

        switch ($primaryDosha) {
            case 'vata':
                $recommendations[] = "Focus on grounding and warming herbs like Ashwagandha and Ginger";
                $recommendations[] = "Maintain regular routine and avoid cold, dry foods";
                $recommendations[] = "Practice gentle yoga and meditation for mental stability";
                break;
            case 'pitta':
                $recommendations[] = "Cooling herbs like Brahmi and Shatavari may be beneficial";
                $recommendations[] = "Avoid spicy and sour foods, prefer cooling foods";
                $recommendations[] = "Practice cooling pranayama and maintain moderate exercise";
                break;
            case 'kapha':
                $recommendations[] = "Stimulating herbs like Tulsi and Triphala for digestion";
                $recommendations[] = "Focus on light, warm foods and regular exercise";
                $recommendations[] = "Practice invigorating activities and maintain social connections";
                break;
        }

        return implode("\n", $recommendations);
    }
}
?>