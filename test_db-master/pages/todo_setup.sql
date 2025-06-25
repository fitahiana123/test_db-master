-- Script pour créer la table des tâches
CREATE TABLE IF NOT EXISTS todos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task TEXT NOT NULL,
    completed BOOLEAN DEFAULT FALSE,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Quelques tâches d'exemple
INSERT INTO todos (task, completed, priority) VALUES
('Terminer le projet PHP', FALSE, 'high'),
('Faire les courses', FALSE, 'medium'),
('Lire un livre', FALSE, 'low'),
('Appeler le dentiste', TRUE, 'medium');
