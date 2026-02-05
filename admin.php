<?php
session_start();
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// Récupérer les statistiques
function getStats() {
    global $pdo;
    
    $stats = [];
    
    // Total des réponses
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reponses");
    $stats['total'] = $stmt->fetch()['total'];
    
    // Réponses OUI
    $stmt = $pdo->query("SELECT COUNT(*) as oui FROM reponses WHERE reponse = 'oui'");
    $stats['oui'] = $stmt->fetch()['oui'];
    
    // Réponses NON
    $stmt = $pdo->query("SELECT COUNT(*) as non FROM reponses WHERE reponse = 'non'");
    $stats['non'] = $stmt->fetch()['non'];
    
    // Dernières réponses
    $stmt = $pdo->query("SELECT * FROM reponses ORDER BY date_reponse DESC LIMIT 10");
    $stats['recentes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Réponses par jour
    $stmt = $pdo->query("SELECT DATE(date_reponse) as jour, COUNT(*) as nombre FROM reponses GROUP BY DATE(date_reponse) ORDER BY jour DESC");
    $stats['par_jour'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $stats;
}

$stats = getStats();

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=valentine_stats_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Prénom', 'Réponse', 'Tentatives', 'Date', 'IP', 'Page']);
    
    $stmt = $pdo->query("SELECT * FROM reponses ORDER BY date_reponse DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['prenom'],
            $row['reponse'],
            $row['tentatives'],
            $row['date_reponse'],
            $row['ip_address'],
            $row['page_source']
        ]);
    }
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Statistiques Valentine</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .admin-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }
        
        .admin-header h1 {
            color: #333;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 2px solid #e9ecef;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card h3 {
            color: #333;
            font-size: 1.5rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stat-number {
            font-size: 3.5rem;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
        
        .oui-stat { color: #10b981; }
        .non-stat { color: #ef4444; }
        .total-stat { color: #667eea; }
        
        .stat-details {
            list-style: none;
            margin-top: 20px;
        }
        
        .stat-details li {
            padding: 12px 15px;
            background: #f8f9fa;
            margin-bottom: 8px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .badge-oui {
            background: #10b981;
            color: white;
        }
        
        .badge-non {
            background: #ef4444;
            color: white;
        }
        
        .table-container {
            overflow-x: auto;
            margin: 30px 0;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        th {
            background: #667eea;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-export {
            background: #10b981;
            color: white;
        }
        
        .btn-export:hover {
            background: #0da271;
            transform: translateY(-2px);
        }
        
        .btn-logout {
            background: #ef4444;
            color: white;
        }
        
        .btn-logout:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }
        
        .btn-refresh {
            background: #667eea;
            color: white;
        }
        
        .btn-refresh:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .chart-bar {
            height: 30px;
            background: #e5e7eb;
            border-radius: 15px;
            overflow: hidden;
            margin: 10px 0;
            position: relative;
        }
        
        .chart-fill {
            height: 100%;
            border-radius: 15px;
            transition: width 1s ease-in-out;
        }
        
        .chart-fill-oui {
            background: #10b981;
        }
        
        .chart-fill-non {
            background: #ef4444;
        }
        
        .chart-label {
            position: absolute;
            top: 0;
            left: 15px;
            line-height: 30px;
            color: white;
            font-weight: 600;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        
        @media (max-width: 768px) {
            .admin-container {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-number {
                font-size: 2.5rem;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>📊 Tableau de Bord Valentine</h1>
            <p>Statistiques des réponses en temps réel</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>📈 Total des Réponses</h3>
                <div class="stat-number total-stat"><?php echo $stats['total']; ?></div>
                <p>Personnes ont répondu</p>
            </div>
            
            <div class="stat-card">
                <h3>💖 Réponses "OUI"</h3>
                <div class="stat-number oui-stat"><?php echo $stats['oui']; ?></div>
                <p>Cœurs conquis</p>
            </div>
            
            <div class="stat-card">
                <h3>😢 Réponses "NON"</h3>
                <div class="stat-number non-stat"><?php echo $stats['non']; ?></div>
                <p>Cœurs brisés</p>
            </div>
        </div>
        
        <div class="chart-container">
            <h3>📊 Répartition des Réponses</h3>
            <?php if ($stats['total'] > 0): ?>
                <div class="chart-bar">
                    <div class="chart-fill chart-fill-oui" 
                         style="width: <?php echo ($stats['oui'] / $stats['total']) * 100; ?>%">
                        <span class="chart-label">OUI: <?php echo round(($stats['oui'] / $stats['total']) * 100, 1); ?>%</span>
                    </div>
                </div>
                <div class="chart-bar">
                    <div class="chart-fill chart-fill-non" 
                         style="width: <?php echo ($stats['non'] / $stats['total']) * 100; ?>%">
                        <span class="chart-label">NON: <?php echo round(($stats['non'] / $stats['total']) * 100, 1); ?>%</span>
                    </div>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 20px;">Aucune donnée pour le moment</p>
            <?php endif; ?>
        </div>
        
        <div class="table-container">
            <h3 style="padding: 20px 0 10px 20px; color: #333;">📋 Dernières Réponses</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Prénom</th>
                        <th>Réponse</th>
                        <th>Tentatives</th>
                        <th>Date/Heure</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['recentes'] as $reponse): ?>
                    <tr>
                        <td><?php echo $reponse['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($reponse['prenom']); ?></strong></td>
                        <td>
                            <span class="badge <?php echo $reponse['reponse'] === 'oui' ? 'badge-oui' : 'badge-non'; ?>">
                                <?php echo strtoupper($reponse['reponse']); ?>
                            </span>
                        </td>
                        <td><?php echo $reponse['tentatives']; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($reponse['date_reponse'])); ?></td>
                        <td><?php echo $reponse['ip_address']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (!empty($stats['par_jour'])): ?>
        <div class="chart-container">
            <h3>📅 Activité par Jour</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Nombre de réponses</th>
                        <th>Graphique</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['par_jour'] as $jour): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($jour['jour'])); ?></td>
                        <td><strong><?php echo $jour['nombre']; ?></strong> réponses</td>
                        <td>
                            <div style="background: #e5e7eb; height: 20px; border-radius: 10px; width: 200px; overflow: hidden;">
                                <div style="background: #667eea; height: 100%; width: <?php echo min($jour['nombre'] * 10, 100); ?>%;"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="actions">
            <a href="admin.php?export=csv" class="btn btn-export">
                📥 Exporter en CSV
            </a>
            <a href="admin.php" class="btn btn-refresh">
                🔄 Actualiser
            </a>
            <a href="admin_logout.php" class="btn btn-logout">
                🚪 Déconnexion
            </a>
        </div>
    </div>
</body>
</html>