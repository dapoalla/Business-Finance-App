<?php
require_once 'db.php';
include 'header.php';

$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Aggregate inflow/outflow per quarter for selected year
function quarterOf($dateStr) {
    $m = (int)date('n', strtotime($dateStr));
    return (int)ceil($m / 3);
}

$quarters = [1=>['inflow'=>0,'outflow'=>0],2=>['inflow'=>0,'outflow'=>0],3=>['inflow'=>0,'outflow'=>0],4=>['inflow'=>0,'outflow'=>0]];
$res = $conn->query("SELECT type, amount, created_at FROM transactions WHERE YEAR(created_at) = " . (int)$year);
if ($res && $res->num_rows > 0) {
    while ($t = $res->fetch_assoc()) {
        $q = quarterOf($t['created_at']);
        $quarters[$q][$t['type']] += (float)$t['amount'];
    }
}

$config = include __DIR__ . '/config.php';
$currency = $config['app']['currency'] ?? 'NGN';
?>
<div class="container">
    <h2>Trial Balance (<?= (int)$year ?>)</h2>
    <form method="get" style="margin-bottom:12px;">
        <label>Year <input type="number" name="year" value="<?= (int)$year ?>" min="2000" max="2100"></label>
        <button type="submit">View</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Quarter</th>
                <th>Inflow (<?= htmlspecialchars($currency) ?>)</th>
                <th>Outflow (<?= htmlspecialchars($currency) ?>)</th>
                <th>Net (<?= htmlspecialchars($currency) ?>)</th>
            </tr>
        </thead>
        <tbody>
        <?php for ($q=1; $q<=4; $q++): $in=$quarters[$q]['inflow']; $out=$quarters[$q]['outflow']; ?>
            <tr>
                <td>Q<?= $q ?></td>
                <td><?= number_format($in, 2) ?></td>
                <td><?= number_format($out, 2) ?></td>
                <td><?= number_format($in-$out, 2) ?></td>
            </tr>
        <?php endfor; ?>
        </tbody>
    </table>
</div>
<?php require_once 'footer.php'; ?>