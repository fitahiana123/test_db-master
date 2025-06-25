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
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .main-container { background: white; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); margin: 20px auto; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 20px 20px 0 0; }
        .content { padding: 30px; }
    </style>
</head>
<body>
    <div class="back-link">
        <a href="index.php">← Retour à la liste des départements</a>
    </div>
    
    <h2>Employés du département : <?php echo htmlspecialchars($dept_name . ' (' . $dept_no . ')'); ?></h2>
    
    <table>
        <tr>
            <th>Numéro employé</th>
            <th>Prénom</th>
            <th>Nom</th>
            <th>Date d'embauche</th>
            <th>Début dans le département</th>
            <th>Fin dans le département</th>
        </tr>
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
            <tr><td colspan="6">Aucun employé trouvé dans ce département.</td></tr>
        <?php endif; ?>
    </table>
    
    <div class="back-link">
        <a href="index.php">← Retour à la liste des départements</a>
    </div>
</body>
</html>
