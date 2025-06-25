<?php
include 'connexion.php';

$conn = dbconnect();
$emp_no = isset($_GET['emp_no']) ? intval($_GET['emp_no']) : 0;

if (!$emp_no) {
    header('Location: index.php');
    exit;
}

// Infos principales
$sql = "SELECT e.*, t.title, d.dept_name, de.from_date as dept_from, de.to_date as dept_to
        FROM employees e
        LEFT JOIN dept_emp de ON e.emp_no = de.emp_no
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiche de l'employé <?php echo htmlspecialchars($employe['first_name'] . ' ' . $employe['last_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .fiche-card { max-width: 700px; margin: 40px auto; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        .fiche-header { background: linear-gradient(135deg, #007bff 0%, #6610f2 100%); color: white; border-radius: 20px 20px 0 0; padding: 30px; }
        .fiche-body { padding: 30px; }
        .fiche-label { font-weight: bold; color: #6610f2; }
    </style>
</head>
<body>
    <div class="container">
        <div class="fiche-card bg-white">
            <div class="fiche-header">
                <h2 class="mb-0"><i class="bi bi-person-badge me-2"></i>Fiche de l'employé</h2>
                <h4 class="mt-2"><?php echo htmlspecialchars($employe['first_name'] . ' ' . $employe['last_name']); ?></h4>
            </div>
            <div class="fiche-body">
                <div class="row mb-2">
                    <div class="col-5 fiche-label">Numéro :</div>
                    <div class="col-7"><?php echo htmlspecialchars($employe['emp_no']); ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 fiche-label">Sexe :</div>
                    <div class="col-7"><?php echo htmlspecialchars($employe['gender'] == 'M' ? 'Homme' : 'Femme'); ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 fiche-label">Date de naissance :</div>
                    <div class="col-7"><?php echo htmlspecialchars($employe['birth_date']); ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 fiche-label">Date d'embauche :</div>
                    <div class="col-7"><?php echo htmlspecialchars($employe['hire_date']); ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 fiche-label">Poste actuel :</div>
                    <div class="col-7"><?php echo htmlspecialchars($employe['title']); ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 fiche-label">Département actuel :</div>
                    <div class="col-7"><?php echo htmlspecialchars($employe['dept_name']); ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 fiche-label">Dans le département depuis :</div>
                    <div class="col-7"><?php echo htmlspecialchars($employe['dept_from']); ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 fiche-label">Jusqu'à :</div>
                    <div class="col-7"><?php echo htmlspecialchars($employe['dept_to']); ?></div>
                </div>
                <hr>
                <h5 class="mt-4 mb-3 text-primary"><i class="bi bi-cash-coin me-2"></i>Historique des salaires</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Montant</th>
                                <th>Début</th>
                                <th>Fin</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while($sal = $res_sal->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo number_format($sal['salary'], 0, ',', ' '); ?> €</td>
                                <td><?php echo htmlspecialchars($sal['from_date']); ?></td>
                                <td><?php echo htmlspecialchars($sal['to_date']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <h5 class="mt-4 mb-3 text-primary"><i class="bi bi-briefcase-fill me-2"></i>Historique des emplois occupés</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Poste</th>
                                <th>Début</th>
                                <th>Fin</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while($tit = $res_titles->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($tit['title']); ?></td>
                                <td><?php echo htmlspecialchars($tit['from_date']); ?></td>
                                <td><?php echo htmlspecialchars($tit['to_date']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="text-center pb-4"></div>
                <a href="javascript:history.back()" class="btn btn-primary"><i class="bi bi-arrow-left"></i> Retour</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
