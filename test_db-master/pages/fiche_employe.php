<?php
include 'connexion.php';

$conn = dbconnect();
$emp_no = isset($_GET['emp_no']) ? intval($_GET['emp_no']) : 0;

if (!$emp_no) {
    header('Location: index.php');
    exit;
}

// Infos principales (après changement de département, on prend le plus récent)
$sql = "SELECT e.*, t.title, d.dept_name, de.from_date as dept_from, de.to_date as dept_to
        FROM employees e
        LEFT JOIN dept_emp de ON e.emp_no = de.emp_no AND de.to_date = '9999-01-01'
        LEFT JOIN departments d ON de.dept_no = d.dept_no
        LEFT JOIN titles t ON e.emp_no = t.emp_no AND t.to_date = (SELECT MAX(to_date) FROM titles WHERE emp_no = e.emp_no)
        WHERE e.emp_no = ?
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $emp_no);
$stmt->execute();
$result = $stmt->get_result();
$employe = $result->fetch_assoc();

if (!$employe) {
    echo '<div class="container mt-5"><div class="alert alert-danger">Employé introuvable.</div></div>';
    exit;
}

// Historique des salaires
$sql_sal = "SELECT salary, from_date, to_date FROM salaries WHERE emp_no = ? ORDER BY from_date DESC";
$stmt_sal = $conn->prepare($sql_sal);
$stmt_sal->bind_param("i", $emp_no);
$stmt_sal->execute();
$res_sal = $stmt_sal->get_result();

// Historique des titres
$sql_titles = "SELECT title, from_date, to_date FROM titles WHERE emp_no = ? ORDER BY from_date DESC";
$stmt_titles = $conn->prepare($sql_titles);
$stmt_titles->bind_param("i", $emp_no);
$stmt_titles->execute();
$res_titles = $stmt_titles->get_result();

// Trouver l'emploi le plus long
$sql_longest_title = "SELECT title, from_date, to_date, DATEDIFF(to_date, from_date) AS duree FROM titles WHERE emp_no = ? ORDER BY duree DESC LIMIT 1";
$stmt_longest = $conn->prepare($sql_longest_title);
$stmt_longest->bind_param("i", $emp_no);
$stmt_longest->execute();
$res_longest = $stmt_longest->get_result();
$longest_title = $res_longest->fetch_assoc();

// Récupérer la liste des départements (pour le formulaire)
$departements = [];
$res_dept = $conn->query("SELECT dept_no, dept_name FROM departments ORDER BY dept_name");
while ($row = $res_dept->fetch_assoc()) {
    $departements[] = $row;
}

// Gestion du formulaire de changement de département
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['changer_dept'])) {
    $new_dept = $_POST['new_dept'] ?? '';
    $new_from = $_POST['new_from'] ?? '';
    $current_dept = $employe['dept_name'];
    $current_from = $employe['dept_from'];
    if (!$new_dept || !$new_from) {
        $message = '<div class="alert alert-danger">Veuillez choisir un département et une date de début.</div>';
    } elseif ($new_from < $current_from) {
        $message = '<div class="alert alert-danger">La date de début du nouveau département ne peut pas être antérieure à la date de début du département actuel.</div>';
    } else {
        // Mettre fin à l'affectation actuelle
        $stmt_end = $conn->prepare("UPDATE dept_emp SET to_date = ? WHERE emp_no = ? AND to_date = '9999-01-01'");
        $stmt_end->bind_param("si", $new_from, $emp_no);
        $stmt_end->execute();
        // Ajouter la nouvelle affectation
        $to_date = '9999-01-01';
        $stmt_new = $conn->prepare("INSERT INTO dept_emp (emp_no, dept_no, from_date, to_date) VALUES (?, ?, ?, ?)");
        $stmt_new->bind_param("isss", $emp_no, $new_dept, $new_from, $to_date);
        $stmt_new->execute();
        // Rafraîchir les infos employé
        header("Location: fiche_employe.php?emp_no=" . $emp_no . "&success=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Fiche de l'employé <?php echo htmlspecialchars($employe['first_name'] . ' ' . $employe['last_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .employee-card {
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.1);
        }
        
        .employee-header {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem;
            position: relative;
        }
        
        .employee-header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            right: 0;
            height: 20px;
            background: var(--primary-gradient);
            clip-path: polygon(0 0, 100% 0, 95% 100%, 5% 100%);
        }
        
        .avatar {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .info-label {
            font-weight: 600;
            color: #6610f2;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            font-size: 1.1rem;
            color: #495057;
        }
        
        .timeline-item {
            position: relative;
            padding-left: 2rem;
            margin-bottom: 1.5rem;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0.5rem;
            width: 12px;
            height: 12px;
            background: var(--primary-gradient);
            border-radius: 50%;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
        }
        
        .timeline-item::after {
            content: '';
            position: absolute;
            left: 5px;
            top: 1.2rem;
            width: 2px;
            height: calc(100% - 0.5rem);
            background: linear-gradient(to bottom, #007bff, transparent);
        }
        
        .timeline-item:last-child::after {
            display: none;
        }
        
        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease-in-out;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
   
    
    <div class="container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="index.php" class="text-decoration-none">
                        <i class="bi bi-house-door me-1"></i>Accueil
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="javascript:history.back()" class="text-decoration-none">
                        <i class="bi bi-people me-1"></i>Employés
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <i class="bi bi-person-lines-fill me-1"></i>Fiche employé
                </li>
            </ol>
        </nav>

        <div class="row">
            <!-- Carte principale de l'employé -->
            <div class="col-lg-8">
                <div class="card employee-card">
                    <div class="employee-header">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="avatar">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                            </div>
                            <div class="col">
                                <h2 class="mb-1">
                                    <?php echo htmlspecialchars($employe['first_name'] . ' ' . $employe['last_name']); ?>
                                </h2>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-light text-dark me-2">
                                        #<?php echo htmlspecialchars($employe['emp_no']); ?>
                                    </span>
                                    <span class="text-light">
                                        <i class="bi bi-briefcase me-1"></i>
                                        <?php echo htmlspecialchars($employe['title'] ?: 'Poste non défini'); ?>
                                    </span>
                                </div>
                                <?php if ($longest_title): ?>
                                <div class="mt-2">
                                    <span class="badge bg-info text-dark">
                                        <i class="bi bi-trophy me-1"></i>
                                        Emploi le plus long :
                                        <strong><?php echo htmlspecialchars($longest_title['title']); ?></strong>
                                        (<?php echo htmlspecialchars(date('d/m/Y', strtotime($longest_title['from_date']))); ?> - <?php echo htmlspecialchars(date('d/m/Y', strtotime($longest_title['to_date']))); ?>)
                                        [<?php echo round($longest_title['duree']/365.25, 1); ?> an(s)]
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body p-4">  
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="info-label">Sexe</div>
                                <div class="info-value">
                                    <i class="bi bi-person me-2 text-primary"></i>
                                    <?php echo htmlspecialchars($employe['gender'] == 'M' ? 'Homme' : 'Femme'); ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-label">Date de naissance</div>
                                <div class="info-value">
                                    <i class="bi bi-calendar-heart me-2 text-primary"></i>
                                    <?php echo htmlspecialchars(date('d/m/Y', strtotime($employe['birth_date']))); ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-label">Date d'embauche</div>
                                <div class="info-value">
                                    <i class="bi bi-calendar-plus me-2 text-primary"></i>
                                    <?php echo htmlspecialchars(date('d/m/Y', strtotime($employe['hire_date']))); ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-label">Département actuel</div>
                                <div class="info-value">
                                    <i class="bi bi-building me-2 text-primary"></i>
                                    <?php echo htmlspecialchars($employe['dept_name'] ?: 'Non assigné'); ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-label">Dans le département depuis</div>
                                <div class="info-value">
                                    <i class="bi bi-calendar-check me-2 text-primary"></i>
                                    <?php echo htmlspecialchars($employe['dept_from'] ? date('d/m/Y', strtotime($employe['dept_from'])) : 'N/A'); ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-label">Jusqu'à</div>
                                <div class="info-value">
                                    <i class="bi bi-calendar-x me-2 text-primary"></i>
                                    <?php echo htmlspecialchars($employe['dept_to'] ? date('d/m/Y', strtotime($employe['dept_to'])) : 'N/A'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques -->
            <div class="col-lg-4">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="stat-card">
                            <div class="stat-icon text-success">
                                <i class="bi bi-calendar-range"></i>
                            </div>
                            <h4 class="fw-bold">
                                <?php 
                                $anciennete = floor((time() - strtotime($employe['hire_date'])) / (365.25 * 24 * 3600));
                                echo $anciennete; 
                                ?>
                            </h4>
                            <p class="text-muted mb-0">Année(s) d'ancienneté</p>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="stat-card">
                            <div class="stat-icon text-info">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <h4 class="fw-bold">
                                <?php 
                                $age = floor((time() - strtotime($employe['birth_date'])) / (365.25 * 24 * 3600));
                                echo $age; 
                                ?>
                            </h4>
                            <p class="text-muted mb-0">Âge</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Historique des salaires -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-cash-coin me-2"></i>Historique des salaires
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th><i class="bi bi-currency-euro me-1"></i>Montant</th>
                                        <th><i class="bi bi-calendar-check me-1"></i>Début</th>
                                        <th><i class="bi bi-calendar-x me-1"></i>Fin</th>
                                        
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($res_sal && $res_sal->num_rows > 0): ?>
                                        <?php while($sal = $res_sal->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <span class="fw-bold text-success">
                                                        <?php echo number_format($sal['salary'], 0, ',', ' '); ?> €
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($sal['from_date']))); ?></td>
                                                <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($sal['to_date']))); ?></td>
                                               
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                                Aucun historique de salaire disponible
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

        <!-- Historique des emplois -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-briefcase-fill me-2"></i>Historique des emplois occupés
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($res_titles && $res_titles->num_rows > 0): ?>
                            <div class="timeline">
                                <?php while($tit = $res_titles->fetch_assoc()): ?>
                                    <div class="timeline-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="fw-bold text-primary mb-1">
                                                    <?php echo htmlspecialchars($tit['title']); ?>
                                                </h6>
                                                <p class="text-muted mb-0">
                                                    <i class="bi bi-calendar-range me-1"></i>
                                                    Du <?php echo htmlspecialchars(date('d/m/Y', strtotime($tit['from_date']))); ?>
                                                    au <?php echo htmlspecialchars(date('d/m/Y', strtotime($tit['to_date']))); ?>
                                                </p>
                                            </div>
                                            <span class="badge bg-primary">
                                                <?php 
                                                $duree = floor((strtotime($tit['to_date']) - strtotime($tit['from_date'])) / (365.25 * 24 * 3600));
                                                echo $duree . ' an' . ($duree > 1 ? 's' : '');
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                <p class="mb-0">Aucun historique d'emploi disponible</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Changer de département -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-arrow-right-circle me-2"></i>Changer de département
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)) echo $message; ?>
                        <div class="mb-4">
                            <button class="btn btn-warning" type="button" data-bs-toggle="collapse" data-bs-target="#changerDeptForm" aria-expanded="false" aria-controls="changerDeptForm">
                                <i class="bi bi-arrow-left-right"></i> Changer de département
                            </button>
                            <div class="collapse mt-3" id="changerDeptForm">
                                <div class="card card-body">
                                    <div class="mb-3">
                                        <strong>Département actuel :</strong> <?php echo htmlspecialchars($employe['dept_name'] ?: 'Non assigné'); ?>
                                        <br><strong>Date de début :</strong> <?php echo htmlspecialchars($employe['dept_from'] ? date('d/m/Y', strtotime($employe['dept_from'])) : 'N/A'); ?>
                                    </div>
                                    <form method="post">
                                        <div class="mb-3">
                                            <label for="new_dept" class="form-label">Nouveau département</label>
                                            <select name="new_dept" id="new_dept" class="form-select" required>
                                                <option value="">-- Choisir --</option>
                                                <?php foreach($departements as $d): ?>
                                                    <?php if ($d['dept_name'] !== $employe['dept_name']): ?>
                                                        <option value="<?php echo htmlspecialchars($d['dept_no']); ?>"><?php echo htmlspecialchars($d['dept_name']); ?></option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="new_from" class="form-label">Date de début</label>
                                            <input type="date" name="new_from" id="new_from" class="form-control" min="<?php echo htmlspecialchars($employe['dept_from']); ?>" required>
                                        </div>
                                        <button type="submit" name="changer_dept" class="btn btn-primary">Valider le changement</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="row mt-4 mb-5">
            <div class="col-12 text-center">
                <div class="btn-group" role="group">
                    <a href="javascript:history.back()" class="btn btn-primary">
                        <i class="bi bi-arrow-left me-1"></i>Retour
                    </a>
                    <a href="index.php" class="btn btn-outline-primary">
                        <i class="bi bi-house-door me-1"></i>Accueil
                    </a>
                    <button class="btn btn-outline-primary" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i>Imprimer
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS pour le collapse -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


