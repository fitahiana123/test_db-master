<?php
include 'connexion.php';

$conn = dbconnect();

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $task = trim($_POST['task']);
                $priority = $_POST['priority'];
                if (!empty($task)) {
                    $stmt = $conn->prepare("INSERT INTO todos (task, priority) VALUES (?, ?)");
                    $stmt->bind_param("ss", $task, $priority);
                    $stmt->execute();
                }
                break;
                
            case 'toggle':
                $id = $_POST['id'];
                $stmt = $conn->prepare("UPDATE todos SET completed = NOT completed WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                break;
                
            case 'delete':
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM todos WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $task = trim($_POST['task']);
                $priority = $_POST['priority'];
                if (!empty($task)) {
                    $stmt = $conn->prepare("UPDATE todos SET task = ?, priority = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $task, $priority, $id);
                    $stmt->execute();
                }
                break;
        }
    }
    header('Location: todo.php');
    exit;
}

// Récupération des tâches
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$sql = "SELECT * FROM todos";
switch ($filter) {
    case 'pending':
        $sql .= " WHERE completed = FALSE";
        break;
    case 'completed':
        $sql .= " WHERE completed = TRUE";
        break;
}
$sql .= " ORDER BY completed ASC, priority DESC, created_at DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma To-Do Liste</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .add-form {
            padding: 30px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        .todo-item {
            display: flex;
            align-items: center;
            padding: 20px;
            margin-bottom: 15px;
            background: #f8f9fa;
            border-radius: 15px;
            border-left: 5px solid #dee2e6;
            transition: all 0.3s ease;
        }
        
        .todo-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .todo-item.completed {
            opacity: 0.7;
            border-left-color: #28a745;
        }
        
        .todo-item.completed .task-text {
            text-decoration: line-through;
            color: #6c757d;
        }
        
        .stats {
            padding: 20px 30px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            text-align: center;
            color: #6c757d;
        }
        
        .checkbox {
            width: 20px;
            height: 20px;
            margin-right: 15px;
            cursor: pointer;
        }
        
        .task-text {
            flex: 1;
            font-size: 16px;
            margin-right: 15px;
        }
        
        .edit-form {
            display: none;
            flex: 1;
            margin-right: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📝 Ma To-Do Liste</h1>
            <p>Organisez vos tâches efficacement</p>
        </div>
        
        <div class="add-form">
            <form method="POST" class="mb-0">
                <input type="hidden" name="action" value="add">
                <div class="d-flex gap-3 flex-wrap align-items-center">
                    <input type="text" name="task" class="form-control" placeholder="Ajouter une nouvelle tâche..." required>
                    <select name="priority" class="form-select" style="width: auto;">
                        <option value="low">Priorité basse</option>
                        <option value="medium" selected>Priorité moyenne</option>
                        <option value="high">Priorité haute</option>
                    </select>
                    <button type="submit" class="btn btn-primary">➕ Ajouter</button>
                </div>
            </form>
        </div>
        
        .todo-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .todo-item.completed {
            opacity: 0.7;
            border-left-color: #28a745;
        }
        
        .todo-item.completed .task-text {
            text-decoration: line-through;
            color: #6c757d;
        }
        
        .priority-high { border-left-color: #dc3545; }
        .priority-medium { border-left-color: #ffc107; }
        .priority-low { border-left-color: #28a745; }
        
        .checkbox {
            width: 20px;
            height: 20px;
            margin-right: 15px;
            cursor: pointer;
        }
        
        .task-text {
            flex: 1;
            font-size: 16px;
            margin-right: 15px;
        }
        
        .priority-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            margin-right: 15px;
        }
        
        .priority-high-badge { background: #dc3545; color: white; }
        .priority-medium-badge { background: #ffc107; color: black; }
        .priority-low-badge { background: #28a745; color: white; }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
        }
        
        .btn-warning {
            background: #ffc107;
            color: black;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .edit-form {
            display: none;
            flex: 1;
            margin-right: 15px;
        }
        
        .edit-form input,
        .edit-form select {
            padding: 8px 12px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            margin-right: 10px;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #6c757d;
        }
        
        .stats {
            padding: 20px 30px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            text-align: center;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📝 Ma To-Do Liste</h1>
            <p>Organisez vos tâches efficacement</p>
        </div>
        
        <div class="add-form">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <input type="text" name="task" placeholder="Ajouter une nouvelle tâche..." required>
                    <select name="priority">
                        <option value="low">Priorité basse</option>
                        <option value="medium" selected>Priorité moyenne</option>
                        <option value="high">Priorité haute</option>
                    </select>
                    <button type="submit" class="btn btn-primary">➕ Ajouter</button>
                </div>
            </form>
        </div>
        
        <div class="filters">
            <div class="d-flex gap-2 flex-wrap">
                <a href="todo.php?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                    Toutes les tâches
                </a>
                <a href="todo.php?filter=pending" class="btn <?php echo $filter === 'pending' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                    En cours
                </a>
                <a href="todo.php?filter=completed" class="btn <?php echo $filter === 'completed' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                    Terminées
                </a>
            </div>
        </div>
        
        <div class="todo-list">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($todo = $result->fetch_assoc()): ?>
                    <div class="todo-item <?php echo $todo['completed'] ? 'completed' : ''; ?> priority-<?php echo $todo['priority']; ?>" id="todo-<?php echo $todo['id']; ?>">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?php echo $todo['id']; ?>">
                            <input type="checkbox" class="form-check-input checkbox" <?php echo $todo['completed'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                        </form>
                        
                        <div class="task-text" id="text-<?php echo $todo['id']; ?>">
                            <?php echo htmlspecialchars($todo['task']); ?>
                        </div>
                        
                        <div class="edit-form" id="edit-<?php echo $todo['id']; ?>">
                            <form method="POST" class="d-flex align-items-center">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id" value="<?php echo $todo['id']; ?>">
                                <input type="text" name="task" class="form-control me-2" value="<?php echo htmlspecialchars($todo['task']); ?>" required>
                                <select name="priority" class="form-select me-2" style="width: auto;">
                                    <option value="low" <?php echo $todo['priority'] === 'low' ? 'selected' : ''; ?>>Basse</option>
                                    <option value="medium" <?php echo $todo['priority'] === 'medium' ? 'selected' : ''; ?>>Moyenne</option>
                                    <option value="high" <?php echo $todo['priority'] === 'high' ? 'selected' : ''; ?>>Haute</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary me-2">💾</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cancelEdit(<?php echo $todo['id']; ?>)">❌</button>
                            </form>
                        </div>
                        
                        <span class="badge <?php 
                            switch($todo['priority']) {
                                case 'high': echo 'bg-danger'; break;
                                case 'medium': echo 'bg-warning text-dark'; break;
                                case 'low': echo 'bg-success'; break;
                            }
                            ?>">
                            <?php 
                            switch($todo['priority']) {
                                case 'high': echo 'HAUTE'; break;
                                case 'medium': echo 'MOYENNE'; break;
                                case 'low': echo 'BASSE'; break;
                            }
                            ?>
                        </span>
                        
                        <div class="ms-auto d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-warning" onclick="editTodo(<?php echo $todo['id']; ?>)">
                                ✏️ Modifier
                            </button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $todo['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">🗑️ Supprimer</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center p-5 text-muted">
                    <h3>🎉 Aucune tâche à afficher</h3>
                    <p>
                        <?php if ($filter === 'completed'): ?>
                            Vous n'avez pas encore terminé de tâches.
                        <?php elseif ($filter === 'pending'): ?>
                            Toutes vos tâches sont terminées ! Félicitations ! 🎊
                        <?php else: ?>
                            Commencez par ajouter une nouvelle tâche ci-dessus.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php
        // Statistiques
        $stats_sql = "SELECT 
            COUNT(*) as total,
            SUM(completed) as completed,
            COUNT(*) - SUM(completed) as pending
            FROM todos";
        $stats_result = $conn->query($stats_sql);
        $stats = $stats_result->fetch_assoc();
        ?>
        
        <div class="card-footer bg-light text-center py-3">
            <strong>Statistiques :</strong> 
            <span class="badge bg-primary rounded-pill"><?php echo $stats['total']; ?> tâche(s) au total</span>
            <span class="badge bg-success rounded-pill"><?php echo $stats['completed']; ?> terminée(s)</span>
            <span class="badge bg-warning text-dark rounded-pill"><?php echo $stats['pending']; ?> en cours</span>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editTodo(id) {
            document.getElementById('text-' + id).style.display = 'none';
            document.getElementById('edit-' + id).style.display = 'flex';
        }
        
        function cancelEdit(id) {
            document.getElementById('text-' + id).style.display = 'block';
            document.getElementById('edit-' + id).style.display = 'none';
        }
    </script>
</body>
</html>
