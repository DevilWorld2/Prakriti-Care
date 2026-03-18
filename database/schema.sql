-- PrakritiCare Database Schema
-- Run this script to create the complete database structure

CREATE DATABASE IF NOT EXISTS prakriti_care;
USE prakriti_care;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    age INT,
    gender ENUM('male', 'female', 'other'),
    dosha_type ENUM('vata', 'pitta', 'kapha', 'vata-pitta', 'pitta-kapha', 'kapha-vata', 'tridosha'),
    phone VARCHAR(20),
    address TEXT,
    medical_history TEXT,
    allergies TEXT,
    lifestyle_habits TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    user_type ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Admins table (separate from users for better security)
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Herbs table
CREATE TABLE herbs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    scientific_name VARCHAR(100),
    sanskrit_name VARCHAR(100),
    description TEXT,
    benefits TEXT,
    usage_instructions TEXT,
    dosage VARCHAR(255),
    contraindications TEXT,
    side_effects TEXT,
    dosha_effect ENUM('vata', 'pitta', 'kapha', 'vata-pitta', 'pitta-kapha', 'kapha-vata', 'tridosha', 'neutral'),
    rasa ENUM('sweet', 'sour', 'salty', 'pungent', 'bitter', 'astringent'),
    virya ENUM('hot', 'cold'),
    vipaka ENUM('sweet', 'sour', 'pungent'),
    part_used VARCHAR(100),
    image_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Diseases/Symptoms mapping
CREATE TABLE diseases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    ayurvedic_name VARCHAR(100),
    dosha_imbalance ENUM('vata', 'pitta', 'kapha', 'vata-pitta', 'pitta-kapha', 'kapha-vata'),
    common_symptoms TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Herb-Disease relationships
CREATE TABLE herb_disease_relations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    herb_id INT NOT NULL,
    disease_id INT NOT NULL,
    effectiveness ENUM('primary', 'secondary', 'supportive'),
    dosage_notes TEXT,
    FOREIGN KEY (herb_id) REFERENCES herbs(id) ON DELETE CASCADE,
    FOREIGN KEY (disease_id) REFERENCES diseases(id) ON DELETE CASCADE,
    UNIQUE KEY unique_herb_disease (herb_id, disease_id)
);

-- Assessments table
CREATE TABLE assessments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    assessment_type ENUM('prakriti', 'disease', 'general') DEFAULT 'general',
    symptoms TEXT,
    medical_history TEXT,
    lifestyle_factors TEXT,
    dosha_scores JSON,
    primary_dosha ENUM('vata', 'pitta', 'kapha'),
    secondary_dosha ENUM('vata', 'pitta', 'kapha'),
    recommendations TEXT,
    status ENUM('pending', 'completed', 'reviewed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Recommendations table
CREATE TABLE recommendations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    assessment_id INT,
    herb_id INT NOT NULL,
    condition VARCHAR(255),
    dosage VARCHAR(255),
    frequency VARCHAR(255),
    duration VARCHAR(255),
    instructions TEXT,
    expected_benefits TEXT,
    precautions TEXT,
    priority ENUM('high', 'medium', 'low') DEFAULT 'medium',
    status ENUM('active', 'completed', 'discontinued') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE SET NULL,
    FOREIGN KEY (herb_id) REFERENCES herbs(id) ON DELETE CASCADE
);

-- Contact messages table
CREATE TABLE contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200),
    message TEXT NOT NULL,
    status ENUM('unread', 'read', 'replied') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    replied_at TIMESTAMP NULL
);

-- Sessions table for tracking user sessions
CREATE TABLE user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default admin user
INSERT INTO admins (username, email, password, full_name) VALUES
('admin', 'admin@prakriti.care', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator');
-- Password is 'password' - change this in production!

-- Insert sample herbs
INSERT INTO herbs (name, scientific_name, sanskrit_name, description, benefits, usage_instructions, dosage, dosha_effect, rasa, virya, vipaka, part_used) VALUES
('Tulsi', 'Ocimum sanctum', 'Tulasi', 'Holy basil, sacred plant of India', 'Boosts immunity, reduces stress, respiratory health', 'Can be taken as tea, powder, or fresh leaves', '300-500mg twice daily', 'kapha', 'pungent', 'hot', 'pungent', 'leaves, seeds'),
('Ashwagandha', 'Withania somnifera', 'Ashwagandha', 'Indian ginseng, adaptogenic herb', 'Reduces stress, improves vitality, enhances immunity', 'Take with milk or water', '300-600mg twice daily', 'vata', 'bitter', 'hot', 'sweet', 'root'),
('Triphala', 'Three fruits formula', 'Triphala', 'Combination of three fruits', 'Supports digestion, detoxification, eye health', 'Take before bed with warm water', '500mg-1g before bed', 'tridosha', 'mixed', 'cold', 'sweet', 'fruits'),
('Neem', 'Azadirachta indica', 'Nimba', 'Indian lilac, versatile medicinal tree', 'Purifies blood, enhances skin health, antimicrobial', 'Various forms: leaves, oil, powder', '300-500mg twice daily', 'pitta', 'bitter', 'cold', 'pungent', 'leaves, bark, seeds'),
('Brahmi', 'Bacopa monnieri', 'Brahmi', 'Memory enhancer, nerve tonic', 'Improves memory, reduces anxiety, enhances cognition', 'Take with milk or water', '300-450mg twice daily', 'pitta', 'bitter', 'cold', 'sweet', 'whole plant'),
('Shankhpushpi', 'Convolvulus pluricaulis', 'Shankhpushpi', 'Brain tonic, intellect promoter', 'Enhances memory, reduces mental fatigue, calms mind', 'Take with milk or water', '300-500mg twice daily', 'pitta', 'bitter', 'cold', 'sweet', 'whole plant'),
('Shatavari', 'Asparagus racemosus', 'Shatavari', 'Queen of herbs, female tonic', 'Hormonal balance, reproductive health, immunity', 'Take with milk or water', '300-500mg twice daily', 'pitta', 'sweet', 'cold', 'sweet', 'root'),
('Ginger', 'Zingiber officinale', 'Ardraka', 'Common spice with medicinal properties', 'Digestive aid, anti-inflammatory, nausea relief', 'Fresh or dried, with meals', '500mg-1g with meals', 'kapha', 'pungent', 'hot', 'pungent', 'rhizome'),
('Turmeric', 'Curcuma longa', 'Haridra', 'Golden spice, anti-inflammatory', 'Reduces inflammation, antioxidant, joint health', 'With black pepper for absorption', '500mg-1g twice daily', 'kapha', 'bitter', 'hot', 'pungent', 'rhizome'),
('Amla', 'Emblica officinalis', 'Amalaki', 'Indian gooseberry, vitamin C rich', 'Immunity booster, anti-aging, hair health', 'Fresh, powder, or juice', '300-500mg twice daily', 'pitta', 'sour', 'cold', 'sweet', 'fruit');

-- Insert sample diseases
INSERT INTO diseases (name, ayurvedic_name, dosha_imbalance, common_symptoms) VALUES
('Common Cold', 'Pratishyaya', 'kapha', 'Runny nose, sore throat, cough, congestion'),
('Digestive Issues', 'Agni Mandya', 'mixed', 'Indigestion, bloating, gas, irregular bowel movements'),
('Stress & Anxiety', 'Manasika Vikara', 'vata', 'Restlessness, worry, sleep disturbances, mental fatigue'),
('Joint Pain', 'Sandhi Shula', 'vata', 'Joint stiffness, pain, reduced mobility'),
('Skin Problems', 'Kushtha', 'pitta', 'Rashes, acne, inflammation, itching'),
('Insomnia', 'Anidra', 'vata', 'Difficulty falling asleep, waking frequently, poor sleep quality'),
('Headache', 'Shirah Shula', 'mixed', 'Head pain, migraine, tension headaches'),
('Respiratory Issues', 'Shwasa', 'kapha', 'Shortness of breath, wheezing, chest congestion'),
('Fatigue', 'Klama', 'mixed', 'Low energy, tiredness, lack of motivation'),
('Allergies', 'Atopy', 'kapha', 'Sneezing, itchy eyes, skin reactions, congestion');

-- Create relationships between herbs and diseases
INSERT INTO herb_disease_relations (herb_id, disease_id, effectiveness, dosage_notes) VALUES
(1, 1, 'primary', 'Take as tea 2-3 times daily'),
(1, 3, 'secondary', '300mg twice daily for stress relief'),
(2, 3, 'primary', '300-600mg twice daily'),
(2, 6, 'secondary', '500mg before bed'),
(3, 2, 'primary', '500mg-1g before bed'),
(4, 5, 'primary', '300-500mg twice daily'),
(5, 3, 'primary', '300-450mg twice daily'),
(5, 6, 'secondary', '300mg before bed'),
(6, 3, 'primary', '300-500mg twice daily'),
(6, 7, 'secondary', '400mg twice daily'),
(7, 2, 'secondary', '300-500mg twice daily'),
(8, 2, 'primary', '500mg with meals'),
(9, 4, 'primary', '500mg-1g twice daily with black pepper'),
(10, 1, 'secondary', '300-500mg twice daily');

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_dosha ON users(dosha_type);
CREATE INDEX idx_herbs_name ON herbs(name);
CREATE INDEX idx_herbs_dosha ON herbs(dosha_effect);
CREATE INDEX idx_diseases_name ON diseases(name);
CREATE INDEX idx_assessments_user ON assessments(user_id);
CREATE INDEX idx_recommendations_user ON recommendations(user_id);
CREATE INDEX idx_user_sessions_token ON user_sessions(session_token);
CREATE INDEX idx_user_sessions_expires ON user_sessions(expires_at);