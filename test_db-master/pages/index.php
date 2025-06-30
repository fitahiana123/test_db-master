<?php
//de aona de aoona 

include 'connexion.php';

$conn = dbconnect();


$departements = [];
$res_dept = $conn->query("SELECT dept_no, dept_name FROM departments ORDER BY dept_name");
while ($row = $res_dept->fetch_assoc()) {
    $departements[] = $row;
}

$managers = [];
$res_mgr = $conn->query("SELECT e.emp_no, e.first_name, e.last_name FROM employees e JOIN dept_manager dm ON e.emp_no = dm.emp_no GROUP BY e.emp_no ORDER BY e.last_name, e.first_name");
while ($res_mgr && $row = $res_mgr->fetch_assoc()) {
    $managers[] = $row;
}

$filter_dept = isset($_GET['dept_no']) ? $_GET['dept_no'] : '';
$filter_mgr = isset($_GET['mgr_no']) ? $_GET['mgr_no'] : '';

$conditions = [];
if ($filter_dept !== '') {
    $conditions[] = "d.dept_no = '" . $conn->real_escape_string($filter_dept) . "'";
}
if ($filter_mgr !== '') {
    $conditions[] = "e.emp_no = '" . $conn->real_escape_string($filter_mgr) . "'";
}
$sql = "
SELECT d.dept_no, d.dept_name, 
       e.emp_no, e.first_name, e.last_name
FROM departments d
LEFT JOIN dept_manager dm 
    ON d.dept_no = dm.dept_no 
    AND YEAR(dm.from_date) <= 2005 
    AND YEAR(dm.to_date) >= 2005
LEFT JOIN employees e 
    ON dm.emp_no = e.emp_no
";
if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= " ORDER BY d.dept_no";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Liste des Départements</title>
    <style>
        table { border-collapse: collapse; width: 60%; margin: 30px auto; }
        th, td { border: 1px solid #ccc; padding: 8px 12px; text-align: left; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
    <div style="width:60%;margin:20px auto 0 auto;text-align:right;">
        <a href="recherche_employes.php" class="btn btn-success" style="margin-bottom:10px;"><i class="bi bi-search"></i> Recherche avancée</a>
    </div>
    <h2 style="text-align:center;">Liste des Départements</h2>
    <form method="get" style="width:60%;margin:20px auto 10px auto;display:flex;gap:20px;align-items:end;">
        <div style="flex:1;">
            <label>Département</label>
            <select name="dept_no" class="form-select">
                <option value="">-- Tous --</option>
                <?php foreach($departements as $d): ?>
                    <option value="<?php echo htmlspecialchars($d['dept_no']); ?>" <?php if($filter_dept === $d['dept_no']) echo 'selected'; ?>><?php echo htmlspecialchars($d['dept_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="flex:1;">
            <label>Manager</label>
            <select name="mgr_no" class="form-select">
                <option value="">-- Tous --</option>
                <?php foreach($managers as $m): ?>
                    <option value="<?php echo htmlspecialchars($m['emp_no']); ?>" <?php if($filter_mgr == $m['emp_no']) echo 'selected'; ?>><?php echo htmlspecialchars($m['last_name'] . ' ' . $m['first_name'] . ' (#' . $m['emp_no'] . ')'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Rechercher</button>
    </form>
    <table>
        <tr>
            <th>Numéro</th>
            <th>Nom du département</th>
            <th>Manager en cours</th>
        </tr>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['dept_no']); ?></td>
                    <td>
                        <a href="employes_departement.php?dept_no=<?php echo urlencode($row['dept_no']); ?>" style="text-decoration: none; color: #007bff;">
                            <?php echo htmlspecialchars($row['dept_name']); ?>
                        </a>
                    </td>
                    <td>
                        <?php 
                        if ($row['first_name'] && $row['last_name'] && $row['emp_no']) {
                            echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name'] . ' (#' . $row['emp_no'] . ')');
                        } else {
                            echo "<i>Aucun</i>";
                        }
                        ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="3">Aucun département trouvé.</td></tr>
        <?php endif; ?>
    </table>
</body>
</html>