<?php


include 'connexion.php';

$conn = dbconnect();


//dxdxtrdxrdxrx
//betay
//rianalaaaah
//Fita.c
$sql = "
SELECT d.dept_no, d.dept_name, 
       e.first_name, e.last_name
FROM departments d
LEFT JOIN dept_manager dm 
    ON d.dept_no = dm.dept_no 
    AND YEAR(dm.from_date) <= 2005 
    AND YEAR(dm.to_date) >= 2005
LEFT JOIN employees e 
    ON dm.emp_no = e.emp_no
ORDER BY d.dept_no
";
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
    <h2 style="text-align:center;">Liste des Départements</h2>
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
                        if ($row['first_name'] && $row['last_name']) {
                            echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
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