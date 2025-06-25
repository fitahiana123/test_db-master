<?php
include 'connexion.php';

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Installation de la To-Do Liste</title>
    <!-- Bootstrap CSS -->
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <!-- Bootstrap Icons -->
    <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css' rel='stylesheet'>
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            max-width: 600px;
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-top-left-radius: 20px !important;
            border-top-right-radius: 20px !important;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='card'>
            <div class='card-header text-center'>
                <h2 class='mb-0'><i class='bi bi-gear-fill me-2'></i> Installation de la To-Do Liste</h2>
            </div>
            <div class='card-body p-4'>";

try {
    $conn = dbconnect();
    
    // Vérifier si la table existe déjà
    $check_table = $conn->query("SHOW TABLES LIKE 'todos'");
    
    if ($check_table->num_rows > 0) {
        echo "<div class='alert alert-info'><i class='bi bi-info-circle-fill me-2'></i> La table 'todos' existe déjà.</div>";
    } else {
        // Créer la table
        $create_table = "
        CREATE TABLE todos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task TEXT NOT NULL,
            completed BOOLEAN DEFAULT FALSE,
            priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($create_table)) {
            echo "<div class='alert alert-success'><i class='bi bi-check-circle-fill me-2'></i> Table 'todos' créée avec succès !</div>";
            
            // Ajouter quelques tâches d'exemple
            $sample_tasks = [
                ["Terminer le projet PHP", "high", false],
                ["Faire les courses", "medium", false],
                ["Lire un livre", "low", false],
                ["Appeler le dentiste", "medium", true]
            ];
            
            foreach ($sample_tasks as $task) {
                $stmt = $conn->prepare("INSERT INTO todos (task, priority, completed) VALUES (?, ?, ?)");
                $stmt->bind_param("ssi", $task[0], $task[1], $task[2]);
                $stmt->execute();
            }
            
            echo "<div class='alert alert-success'><i class='bi bi-check-circle-fill me-2'></i> Tâches d'exemple ajoutées !</div>";
        } else {
            echo "<div class='alert alert-danger'><i class='bi bi-exclamation-triangle-fill me-2'></i> Erreur lors de la création de la table : " . $conn->error . "</div>";
        }
    }
    
    echo "<div class='alert alert-success'><i class='bi bi-emoji-smile-fill me-2'></i> Installation terminée avec succès !</div>";
    echo "<p class='mb-4'>Votre to-do liste est maintenant prête à être utilisée.</p>";
    echo "<div class='text-center mt-4'><a href='todo.php' class='btn btn-primary btn-lg'><i class='bi bi-rocket-takeoff-fill me-2'></i> Ouvrir la To-Do Liste</a></div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'><i class='bi bi-exclamation-triangle-fill me-2'></i> Erreur : " . $e->getMessage() . "</div>";
}

echo "</div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
?>
