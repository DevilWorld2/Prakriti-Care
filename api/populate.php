<?php
// Populate database with additional sample data
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Database.php';

try {
    $db = Database::getInstance();

    // Add more herbs
    $additionalHerbs = [
        [
            'name' => 'Brahmi',
            'scientific_name' => 'Bacopa monnieri',
            'sanskrit_name' => 'Brahmi',
            'description' => 'Traditional brain tonic and memory enhancer',
            'benefits' => 'Improves memory, reduces anxiety, enhances cognitive function, supports nervous system',
            'usage_instructions' => 'Take 300-450mg twice daily with meals',
            'dosage' => '300-450mg twice daily',
            'dosha_effect' => 'pitta',
            'rasa' => 'bitter',
            'virya' => 'cold',
            'vipaka' => 'sweet',
            'part_used' => 'whole plant'
        ],
        [
            'name' => 'Shatavari',
            'scientific_name' => 'Asparagus racemosus',
            'sanskrit_name' => 'Shatavari',
            'description' => 'Queen of herbs, female reproductive tonic',
            'benefits' => 'Hormonal balance, female reproductive health, immunity boost, digestive support',
            'usage_instructions' => 'Take 300-500mg twice daily with milk',
            'dosage' => '300-500mg twice daily',
            'dosha_effect' => 'pitta',
            'rasa' => 'sweet',
            'virya' => 'cold',
            'vipaka' => 'sweet',
            'part_used' => 'root'
        ],
        [
            'name' => 'Ginger',
            'scientific_name' => 'Zingiber officinale',
            'sanskrit_name' => 'Ardraka',
            'description' => 'Common spice with powerful medicinal properties',
            'benefits' => 'Digestive aid, anti-inflammatory, nausea relief, circulation support',
            'usage_instructions' => '500mg-1g with meals, or as tea',
            'dosage' => '500mg-1g with meals',
            'dosha_effect' => 'kapha',
            'rasa' => 'pungent',
            'virya' => 'hot',
            'vipaka' => 'pungent',
            'part_used' => 'rhizome'
        ],
        [
            'name' => 'Turmeric',
            'scientific_name' => 'Curcuma longa',
            'sanskrit_name' => 'Haridra',
            'description' => 'Golden spice with anti-inflammatory properties',
            'benefits' => 'Reduces inflammation, antioxidant, joint health, immune support',
            'usage_instructions' => '500mg-1g twice daily with black pepper for absorption',
            'dosage' => '500mg-1g twice daily',
            'dosha_effect' => 'kapha',
            'rasa' => 'bitter',
            'virya' => 'hot',
            'vipaka' => 'pungent',
            'part_used' => 'rhizome'
        ],
        [
            'name' => 'Amla',
            'scientific_name' => 'Emblica officinalis',
            'sanskrit_name' => 'Amalaki',
            'description' => 'Indian gooseberry, rich in vitamin C',
            'benefits' => 'Immunity booster, anti-aging, hair health, digestive support',
            'usage_instructions' => '300-500mg twice daily, fresh or as powder',
            'dosage' => '300-500mg twice daily',
            'dosha_effect' => 'pitta',
            'rasa' => 'sour',
            'virya' => 'cold',
            'vipaka' => 'sweet',
            'part_used' => 'fruit'
        ]
    ];

    foreach ($additionalHerbs as $herb) {
        $db->insert(
            "INSERT INTO herbs (name, scientific_name, sanskrit_name, description, benefits, usage_instructions, dosage, dosha_effect, rasa, virya, vipaka, part_used) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $herb['name'], $herb['scientific_name'], $herb['sanskrit_name'],
                $herb['description'], $herb['benefits'], $herb['usage_instructions'],
                $herb['dosage'], $herb['dosha_effect'], $herb['rasa'],
                $herb['virya'], $herb['vipaka'], $herb['part_used']
            ]
        );
    }

    // Add more diseases
    $additionalDiseases = [
        ['name' => 'Arthritis', 'ayurvedic_name' => 'Sandhivata', 'dosha_imbalance' => 'vata', 'common_symptoms' => 'Joint pain, stiffness, swelling, reduced mobility'],
        ['name' => 'Diabetes', 'ayurvedic_name' => 'Madhumeha', 'dosha_imbalance' => 'kapha', 'common_symptoms' => 'Frequent urination, excessive thirst, fatigue, slow healing'],
        ['name' => 'Hypertension', 'ayurvedic_name' => 'Raktagata Vata', 'dosha_imbalance' => 'vata', 'common_symptoms' => 'High blood pressure, headaches, dizziness, chest pain'],
        ['name' => 'Thyroid Disorders', 'ayurvedic_name' => 'Galganda', 'dosha_imbalance' => 'kapha', 'common_symptoms' => 'Weight changes, fatigue, mood changes, temperature sensitivity'],
        ['name' => 'PCOS', 'ayurvedic_name' => 'Artavakshaya', 'dosha_imbalance' => 'vata', 'common_symptoms' => 'Irregular periods, weight gain, acne, hair loss']
    ];

    foreach ($additionalDiseases as $disease) {
        $db->insert(
            "INSERT INTO diseases (name, ayurvedic_name, dosha_imbalance, common_symptoms) VALUES (?, ?, ?, ?)",
            [$disease['name'], $disease['ayurvedic_name'], $disease['dosha_imbalance'], $disease['common_symptoms']]
        );
    }

    echo "Additional sample data added successfully!\n";

} catch (Exception $e) {
    echo "Error adding sample data: " . $e->getMessage() . "\n";
}
?>