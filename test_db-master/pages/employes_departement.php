<?php
include 'connexion.php';

$conn = dbconnect();
$dept_no = isset($_GET['dept_no']) ? $_GET['dept_no'] : '';

if (empty($dept_no)) {
    header('Location: index.php');
    exit;
}

// Récupérer le nom du département
$sql_dept = "SELECT dept_name FROM departments WHERE dept_no = ?";
$stmt_dept = $conn->prepare($sql_dept);
$stmt_dept->bind_param("s", $dept_no);
$stmt_dept->execute();
$result_dept = $stmt_dept->get_result();
$dept_name = '';
if ($result_dept && $result_dept->num_rows > 0) {
    $row_dept = $result_dept->fetch_assoc();
    $dept_name = $row_dept['dept_name'];
}

// Récupérer la liste des employés du département
$sql = "
SELECT e.emp_no, e.first_name, e.last_name, e.hire_date,
       de.from_date, de.to_date
FROM employees e
JOIN dept_emp de ON e.emp_no = de.emp_no
WHERE de.dept_no = ?
ORDER BY e.last_name, e.first_name
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $dept_no);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employés du département <?php echo htmlspecialchars($dept_name); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { 
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
            color: white;
            border: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #0069d9 0%, #5209cf 100%);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <a href="index.php" class="btn btn-primary">
                    <i class="bi bi-arrow-left"></i> Retour à la liste des départements
                </a>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h2 class="mb-0">Employés du département : <?php echo htmlspecialchars($dept_name . ' (' . $dept_no . ')'); ?></h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Numéro employé</th>
                                        <th>Prénom</th>
                                        <th>Nom</th>
                                        <th>Date d'embauche</th>
                                        <th>Début dans le département</th>
                                        <th>Fin dans le département</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result && $result->num_rows > 0): ?>
                                        <?php while($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['emp_no']); ?></td>
                                                <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['hire_date']); ?></td>
                                                <td><?php echo htmlspecialchars($row['from_date']); ?></td>
                                                <td><?php echo htmlspecialchars($row['to_date']); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">Aucun employé trouvé dans ce département.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4 mb-5">
            <div class="col-12 text-center">
                <a href="index.php" class="btn btn-primary">
                    <i class="bi bi-arrow-left"></i> Retour à la liste des départements
                </a>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
