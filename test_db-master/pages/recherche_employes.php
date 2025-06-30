<?php
include 'connexion.php';
$conn = dbconnect();

// Récupérer la liste des départements pour le select
$departements = [];
$res_dept = $conn->query("SELECT dept_no, dept_name FROM departments ORDER BY dept_name");
while ($row = $res_dept->fetch_assoc()) {
    $departements[] = $row;
}

// Récupérer la liste des employés pour le select
$employes = [];
$res_emp = $conn->query("SELECT emp_no, first_name, last_name FROM employees ORDER BY last_name, first_name");
if (!$res_emp) {
    echo '<div class="alert alert-danger">Erreur SQL employés : ' . $conn->error . '</div>';
}
while ($res_emp && $row = $res_emp->fetch_assoc()) {
    $employes[] = $row;
}

// Initialisation des filtres
$dept_no = isset($_GET['dept_no']) ? $_GET['dept_no'] : '';
$emp_no = isset($_GET['emp_no']) ? $_GET['emp_no'] : '';
$age_min = isset($_GET['age_min']) ? intval($_GET['age_min']) : '';
$age_max = isset($_GET['age_max']) ? intval($_GET['age_max']) : '';

// Construction de la requête
$conditions = [];
$params = [];
$types = '';

if ($dept_no !== '') {
    $conditions[] = 'de.dept_no = ?';
    $params[] = $dept_no;
    $types .= 's';
}
if ($emp_no !== '') {
    $conditions[] = 'e.emp_no = ?';
    $params[] = $emp_no;
    $types .= 'i';
}
if ($age_min !== '') {
    $conditions[] = 'TIMESTAMPDIFF(YEAR, e.birth_date, CURDATE()) >= ?';
    $params[] = $age_min;
    $types .= 'i';
}
if ($age_max !== '') {
    $conditions[] = 'TIMESTAMPDIFF(YEAR, e.birth_date, CURDATE()) <= ?';
    $params[] = $age_max;
    $types .= 'i';
}

$sql = "SELECT e.emp_no, e.first_name, e.last_name, e.birth_date, e.hire_date, d.dept_name
        FROM employees e
        JOIN dept_emp de ON e.emp_no = de.emp_no
        JOIN departments d ON de.dept_no = d.dept_no
        WHERE 1";
if ($conditions) {
    $sql .= ' AND ' . implode(' AND ', $conditions);
}
$sql .= ' GROUP BY e.emp_no ORDER BY e.last_name, e.first_name';

// Pagination
$per_page = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Pour compter le total de résultats (pour la pagination)
$count_sql = "SELECT COUNT(DISTINCT e.emp_no) as total FROM employees e JOIN dept_emp de ON e.emp_no = de.emp_no JOIN departments d ON de.dept_no = d.dept_no WHERE 1";
if ($conditions) {
    $count_sql .= ' AND ' . implode(' AND ', $conditions);
}
$count_stmt = $conn->prepare($count_sql);
if ($params) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_rows = $count_result->fetch_assoc()['total'];
$count_stmt->close();
$total_pages = ceil($total_rows / $per_page);

$sql .= " LIMIT $offset, $per_page";

// Préparation de la requête
if ($stmt = $conn->prepare($sql)) {
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = false;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche d'employés</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .search-card { max-width: 900px; margin: 40px auto; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        .search-header { background: linear-gradient(135deg, #007bff 0%, #6610f2 100%); color: white; border-radius: 20px 20px 0 0; padding: 30px; }
        .search-body { padding: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="pt-4 pb-2">
            <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Retour à l'accueil</a>
        </div>
        <div class="search-card bg-white">
            <div class="search-header">
                <h2 class="mb-0"><i class="bi bi-search me-2"></i>Recherche d'employés</h2>
            </div>
            <div class="search-body">
                <form method="get" class="row g-3 mb-4 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Département</label>
                        <select name="dept_no" class="form-select">
                            <option value="">-- Tous --</option>
                            <?php foreach($departements as $d): ?>
                                <option value="<?php echo htmlspecialchars($d['dept_no']); ?>" <?php if($dept_no === $d['dept_no']) echo 'selected'; ?>><?php echo htmlspecialchars($d['dept_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Nom de l'employé</label>
                        <?php if (count($employes) > 0): ?>
                            <select name="emp_no" class="form-select">
                                <option value="">-- Tous --</option>
                                <?php foreach($employes as $e): ?>
                                    <option value="<?php echo htmlspecialchars($e['emp_no']); ?>" <?php if($emp_no == $e['emp_no']) echo 'selected'; ?>><?php echo htmlspecialchars($e['last_name'] . ' ' . $e['first_name'] . ' (#' . $e['emp_no'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0">Aucun employé trouvé.</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Âge min</label>
                        <select name="age_min" class="form-select">
                            <option value="">Non précisé</option>
                            <?php for($i=18; $i<=70; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php if($age_min !== '' && $age_min == $i) echo 'selected'; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Âge max</label>
                        <select name="age_max" class="form-select">
                            <option value="">Non précisé</option>
                            <?php for($i=18; $i<=70; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php if($age_max !== '' && $age_max == $i) echo 'selected'; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2 text-end">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Rechercher</button>
                    </div>
                </form>

                <!-- Affichage des filtres actifs (toujours visible) -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-primary">
                            <div class="card-body py-2 px-3">
                                <span class="fw-bold text-primary"><i class="bi bi-funnel"></i> Filtres actifs :</span>
                                <span class="ms-3"><strong>Département :</strong> <?php
                                    if ($dept_no !== '') {
                                        foreach($departements as $d) {
                                            if ($d['dept_no'] === $dept_no) { echo htmlspecialchars($d['dept_name']); break; }
                                        }
                                    } else { echo '<span class="text-muted">Tous</span>'; }
                                ?></span>
                                <span class="ms-3"><strong>Employé :</strong> <?php
                                    if ($emp_no !== '') {
                                        foreach($employes as $e) {
                                            if ($e['emp_no'] == $emp_no) { echo htmlspecialchars($e['last_name'] . ' ' . $e['first_name'] . ' (#' . $e['emp_no'] . ')'); break; }
                                        }
                                    } else { echo '<span class="text-muted">Tous</span>'; }
                                ?></span>
                                <span class="ms-3"><strong>Âge min :</strong> <?php echo ($age_min !== '') ? htmlspecialchars($age_min) : '<span class="text-muted">Non précisé</span>'; ?></span>
                                <span class="ms-3"><strong>Âge max :</strong> <?php echo ($age_max !== '') ? htmlspecialchars($age_max) : '<span class="text-muted">Non précisé</span>'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Numéro</th>
                                <th>Identité</th>
                                <th>Date de naissance</th>
                                <th>Âge</th>
             
                                <th>Date d'embauche</th>
                                <th>Département</th>
                                <th>Fiche</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($res && $res->num_rows > 0): ?>
                            <?php while($row = $res->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['emp_no']); ?></td>
                                    <td><?php echo htmlspecialchars($row['last_name'] . ' ' . $row['first_name'] . ' (#' . $row['emp_no'] . ')'); ?></td>
                                    <td><?php echo htmlspecialchars($row['birth_date']); ?></td>
                                    <td><?php echo (date('Y') - date('Y', strtotime($row['birth_date']))); ?></td>
                                    <td><?php echo htmlspecialchars($row['hire_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['dept_name']); ?></td>
                                    <td><a href="fiche_employe.php?emp_no=<?php echo urlencode($row['emp_no']); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-person-lines-fill"></i> Fiche</a></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center text-muted">Aucun résultat.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Pagination">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page-1])); ?>">&laquo; Précédent</a></li>
                        <?php endif; ?>
                        <?php for($p=1; $p<=$total_pages; $p++): ?>
                            <li class="page-item <?php if($p==$page) echo 'active'; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $p])); ?>"><?php echo $p; ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page+1])); ?>">Suivant &raquo;</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
                <?php if ($page < $total_pages): ?>
                    <div class="text-center my-3">
                        <a class="btn btn-success btn-lg" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page+1])); ?>">
                            Afficher les 20 prochaines lignes <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
