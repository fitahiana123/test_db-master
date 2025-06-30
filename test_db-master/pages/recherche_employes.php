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
    <title>Recherche Avancée d'Employés</title>
    <?php include 'includes/bootstrap_head.php'; ?>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="index.php" class="text-decoration-none">
                        <i class="bi bi-house-door me-1"></i>Accueil
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <i class="bi bi-search me-1"></i>Recherche Avancée
                </li>
            </ol>
        </nav>

        <!-- Formulaire de recherche -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h2 class="mb-0">
                            <i class="bi bi-search me-2"></i>Recherche Avancée d'Employés
                        </h2>
                    </div>
                    <div class="card-body">
                        <form method="get" class="row g-3">
                            <div class="col-md-3">
                                <label for="dept_no" class="form-label">
                                    <i class="bi bi-building me-1"></i>Département
                                </label>
                                <select name="dept_no" id="dept_no" class="form-select">
                                    <option value="">-- Tous les départements --</option>
                                    <?php foreach($departements as $d): ?>
                                        <option value="<?php echo htmlspecialchars($d['dept_no']); ?>" 
                                                <?php if($dept_no === $d['dept_no']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($d['dept_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="emp_no" class="form-label">
                                    <i class="bi bi-person me-1"></i>Employé spécifique
                                </label>
                                <?php if (count($employes) > 0): ?>
                                    <select name="emp_no" id="emp_no" class="form-select">
                                        <option value="">-- Tous les employés --</option>
                                        <?php foreach($employes as $e): ?>
                                            <option value="<?php echo htmlspecialchars($e['emp_no']); ?>" 
                                                    <?php if($emp_no == $e['emp_no']) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($e['last_name'] . ' ' . $e['first_name'] . ' (#' . $e['emp_no'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <div class="alert alert-warning mb-0">
                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                        Aucun employé trouvé dans la base de données.
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-2">
                                <label for="age_min" class="form-label">
                                    <i class="bi bi-calendar-plus me-1"></i>Âge minimum
                                </label>
                                <select name="age_min" id="age_min" class="form-select">
                                    <option value="">Non précisé</option>
                                    <?php for($i=18; $i<=70; $i++): ?>
                                        <option value="<?php echo $i; ?>" 
                                                <?php if($age_min !== '' && $age_min == $i) echo 'selected'; ?>>
                                            <?php echo $i; ?> ans
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label for="age_max" class="form-label">
                                    <i class="bi bi-calendar-x me-1"></i>Âge maximum
                                </label>
                                <select name="age_max" id="age_max" class="form-select">
                                    <option value="">Non précisé</option>
                                    <?php for($i=18; $i<=70; $i++): ?>
                                        <option value="<?php echo $i; ?>" 
                                                <?php if($age_max !== '' && $age_max == $i) echo 'selected'; ?>>
                                            <?php echo $i; ?> ans
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres actifs -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-info">
                    <div class="card-body py-3">
                        <h6 class="card-title mb-2">
                            <i class="bi bi-funnel text-info me-2"></i>Filtres actifs
                        </h6>
                        <div class="row g-2">
                            <div class="col-auto">
                                <span class="badge bg-light text-dark">
                                    <strong>Département :</strong> 
                                    <?php
                                        if ($dept_no !== '') {
                                            foreach($departements as $d) {
                                                if ($d['dept_no'] === $dept_no) { 
                                                    echo htmlspecialchars($d['dept_name']); 
                                                    break; 
                                                }
                                            }
                                        } else { 
                                            echo 'Tous'; 
                                        }
                                    ?>
                                </span>
                            </div>
                            <div class="col-auto">
                                <span class="badge bg-light text-dark">
                                    <strong>Employé :</strong> 
                                    <?php
                                        if ($emp_no !== '') {
                                            foreach($employes as $e) {
                                                if ($e['emp_no'] == $emp_no) { 
                                                    echo htmlspecialchars($e['last_name'] . ' ' . $e['first_name']); 
                                                    break; 
                                                }
                                            }
                                        } else { 
                                            echo 'Tous'; 
                                        }
                                    ?>
                                </span>
                            </div>
                            <div class="col-auto">
                                <span class="badge bg-light text-dark">
                                    <strong>Âge :</strong> 
                                    <?php 
                                        if ($age_min !== '' || $age_max !== '') {
                                            echo ($age_min !== '' ? $age_min : '?') . ' - ' . ($age_max !== '' ? $age_max : '?') . ' ans';
                                        } else {
                                            echo 'Tous âges';
                                        }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Résultats -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="mb-0">
                                    <i class="bi bi-table me-2"></i>Résultats de la recherche
                                    <?php if ($res && $res->num_rows > 0): ?>
                                        <span class="badge bg-primary"><?php echo $res->num_rows; ?> résultat(s) sur <?php echo $total_rows; ?></span>
                                    <?php endif; ?>
                                </h5>
                            </div>
                            <div class="col-auto">
                                <?php if ($total_rows > 0): ?>
                                    <small class="text-muted">
                                        Page <?php echo $page; ?> sur <?php echo $total_pages; ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th><i class="bi bi-hash me-1"></i>N°</th>
                                        <th><i class="bi bi-person me-1"></i>Identité</th>
                                        <th><i class="bi bi-calendar-heart me-1"></i>Naissance</th>
                                        <th><i class="bi bi-person-badge me-1"></i>Âge</th>
                                        <th><i class="bi bi-calendar-plus me-1"></i>Embauche</th>
                                        <th><i class="bi bi-building me-1"></i>Département</th>
                                        <th width="120"><i class="bi bi-gear me-1"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($res && $res->num_rows > 0): ?>
                                        <?php while($row = $res->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo htmlspecialchars($row['emp_no']); ?></span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-person-circle me-2 text-primary"></i>
                                                        <div>
                                                            <div class="fw-medium">
                                                                <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                                                            </div>
                                                            <small class="text-muted">#<?php echo htmlspecialchars($row['emp_no']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <i class="bi bi-calendar me-1 text-muted"></i>
                                                    <?php echo htmlspecialchars(date('d/m/Y', strtotime($row['birth_date']))); ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?php echo (date('Y') - date('Y', strtotime($row['birth_date']))); ?> ans
                                                    </span>
                                                </td>
                                                <td>
                                                    <i class="bi bi-calendar-check me-1 text-success"></i>
                                                    <?php echo htmlspecialchars(date('d/m/Y', strtotime($row['hire_date']))); ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-outline-secondary">
                                                        <?php echo htmlspecialchars($row['dept_name']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="fiche_employe.php?emp_no=<?php echo urlencode($row['emp_no']); ?>" 
                                                       class="btn btn-primary btn-sm" 
                                                       data-bs-toggle="tooltip" 
                                                       title="Voir la fiche détaillée">
                                                        <i class="bi bi-person-lines-fill"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5">
                                                <div class="text-muted">
                                                    <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                                    <h5>Aucun résultat trouvé</h5>
                                                    <p class="mb-0">Aucun employé ne correspond à vos critères de recherche.</p>
                                                    <a href="recherche_employes.php" class="btn btn-outline-primary mt-3">
                                                        <i class="bi bi-arrow-clockwise me-1"></i>Réinitialiser la recherche
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="row mt-4">
            <div class="col-12">
                <nav aria-label="Navigation par pages">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">
                                    <i class="bi bi-chevron-double-left"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page-1])); ?>">
                                    <i class="bi bi-chevron-left"></i> Précédent
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php 
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);
                        for($p = $start; $p <= $end; $p++): 
                        ?>
                            <li class="page-item <?php if($p == $page) echo 'active'; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $p])); ?>">
                                    <?php echo $p; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page+1])); ?>">
                                    Suivant <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>">
                                    <i class="bi bi-chevron-double-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
        <?php endif; ?>

        <!-- Statistiques -->
        <?php if ($total_rows > 0): ?>
        <div class="row mt-4 mb-5">
            <div class="col-12">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <div class="row">
                            <div class="col-md-4">
                                <h4 class="text-success">
                                    <i class="bi bi-people display-6 d-block mb-2"></i>
                                    <?php echo $total_rows; ?>
                                </h4>
                                <p class="text-muted">Employé(s) trouvé(s)</p>
                            </div>
                            <div class="col-md-4">
                                <h4 class="text-info">
                                    <i class="bi bi-file-earmark-text display-6 d-block mb-2"></i>
                                    <?php echo $total_pages; ?>
                                </h4>
                                <p class="text-muted">Page(s) de résultats</p>
                            </div>
                            <div class="col-md-4">
                                <h4 class="text-warning">
                                    <i class="bi bi-eye display-6 d-block mb-2"></i>
                                    <?php echo min($per_page, $total_rows); ?>
                                </h4>
                                <p class="text-muted">Résultats par page</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/bootstrap_scripts.php'; ?>
</body>
</html>
