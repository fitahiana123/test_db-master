<?php
include 'connexion.php';

$conn = dbconnect();
$dept_no = isset($_GET['dept_no']) ? $_GET['dept_no'] : '';

if (!$dept_no) {
    echo '<div class="container mt-5"><div class="alert alert-danger">Département non spécifié.</div></div>';
    exit;
}

// Récupérer le nom du département
$stmt = $conn->prepare("SELECT dept_name FROM departments WHERE dept_no = ?");
$stmt->bind_param("s", $dept_no);
$stmt->execute();
$res = $stmt->get_result();
$dept = $res->fetch_assoc();
if (!$dept) {
    echo '<div class="container mt-5"><div class="alert alert-danger">Département introuvable.</div></div>';
    exit;
}

// Statistiques par emploi pour ce département
$sql = "SELECT t.title,
    SUM(CASE WHEN e.gender = 'M' THEN 1 ELSE 0 END) AS nb_hommes,
    SUM(CASE WHEN e.gender = 'F' THEN 1 ELSE 0 END) AS nb_femmes,
    ROUND(AVG(s.salary), 2) AS salaire_moyen
FROM dept_emp de
JOIN employees e ON de.emp_no = e.emp_no
JOIN (
    SELECT emp_no, MAX(from_date) as max_from
    FROM titles
    GROUP BY emp_no
) t2 ON e.emp_no = t2.emp_no
JOIN titles t ON e.emp_no = t.emp_no AND t.from_date = t2.max_from
JOIN (
    SELECT emp_no, MAX(from_date) as max_from
    FROM salaries
    GROUP BY emp_no
) s2 ON e.emp_no = s2.emp_no
JOIN salaries s ON e.emp_no = s.emp_no AND s.from_date = s2.max_from
WHERE de.dept_no = ?
GROUP BY t.title
ORDER BY t.title;";
$stmt2 = $conn->prepare($sql);
$stmt2->bind_param("s", $dept_no);
$stmt2->execute();
$result = $stmt2->get_result();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Statistiques des emplois du département <?php echo htmlspecialchars($dept['dept_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4"><i class="bi bi-bar-chart"></i> Statistiques par emploi - <?php echo htmlspecialchars($dept['dept_name']); ?></h2>
    <div class="mb-3">
        <a href="index.php" class="btn btn-outline-primary"><i class="bi bi-house-door"></i> Accueil</a>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Emploi</th>
                            <th>Nombre d'hommes</th>
                            <th>Nombre de femmes</th>
                            <th>Salaire moyen (€)</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo (int)$row['nb_hommes']; ?></td>
                                <td><?php echo (int)$row['nb_femmes']; ?></td>
                                <td><?php echo number_format($row['salaire_moyen'], 2, ',', ' '); ?> €</td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center text-muted">Aucune donnée disponible</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
