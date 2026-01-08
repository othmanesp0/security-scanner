<?php
/**
 * View Scan Results - Fixed Final Version
 * Includes AI Dashboard, Error Handling, and French UI
 */
require_once 'includes/auth.php';
requireLogin();

$scan_id = isset($_GET['id']) ? sanitizeInput($_GET['id']) : null;
if (!$scan_id) { header('Location: user_dashboard.php'); exit(); }

require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// 1. Fetch Scan Details
// Uses 'output_file', 'scan_type', 'target' from your fixed schema
$query = "SELECT s.*, u.username FROM scans s JOIN users u ON s.user_id = u.id WHERE s.id = :scan_id";
if ($_SESSION['role'] !== 'admin') { $query .= " AND s.user_id = :user_id"; }

$stmt = $db->prepare($query);
$stmt->bindParam(':scan_id', $scan_id);
if ($_SESSION['role'] !== 'admin') { $stmt->bindParam(':user_id', $_SESSION['user_id']); }
$stmt->execute();
$scan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$scan) { header('Location: user_dashboard.php'); exit(); }

// 2. Read Raw Results & AI Data
$results = '';
$ai_data = null;
$ai_file_path = '';

if ($scan['output_file'] && file_exists($scan['output_file'])) {
    $results = file_get_contents($scan['output_file']);
    
    // Look for AI Analysis File (filename_ai.json)
    $ai_file_path = str_replace('.txt', '_ai.json', $scan['output_file']);
    
    if (file_exists($ai_file_path)) {
        $json_content = file_get_contents($ai_file_path);
        $ai_data = json_decode($json_content, true);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport - <?php echo htmlspecialchars($scan['scan_type']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6">
    <div class="max-w-7xl mx-auto">
        
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900">Rapport de Sécurité</h1>
                <p class="text-gray-600 mt-1">
                    Cible : <span class="font-mono font-bold text-gray-800"><?php echo htmlspecialchars($scan['target'] ?? $scan['target_url']); ?></span> 
                    &bull; Outil : <?php echo htmlspecialchars($scan['scan_type'] ?? $scan['tool']); ?>
                </p>
            </div>
            <a href="user_dashboard.php" class="px-5 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 shadow-sm transition">
                &larr; Retour
            </a>
        </div>

        <?php 
            // Logic to determine if we show the dashboard or an error
            $has_valid_ai = $ai_data && (isset($ai_data['summary']) || isset($ai_data['risk_score']));
            $ai_error = $ai_data['error'] ?? null;
        ?>

        <?php if ($ai_error): ?>
        <div class="mb-8 bg-red-50 border-l-4 border-red-500 p-6 rounded shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-bold text-red-800">Échec de l'analyse IA</h3>
                    <p class="mt-2 text-sm text-red-700">
                        L'intelligence artificielle n'a pas pu traiter ce rapport correctement.
                        <br><br>
                        <strong>Raison technique :</strong> <?php echo htmlspecialchars($ai_error); ?>
                    </p>
                    <?php if(isset($ai_data['details'])): ?>
                    <div class="mt-3 p-3 bg-red-100 rounded text-xs font-mono text-red-800 overflow-x-auto">
                        <?php echo htmlspecialchars(substr($ai_data['details'], 0, 300)) . '...'; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php elseif (!$has_valid_ai): ?>
        <div class="mb-8 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-bold text-yellow-800">Analyse IA non disponible</h3>
                    <p class="mt-1 text-sm text-yellow-700">
                        Le rapport d'analyse n'a pas encore été généré ou le fichier est manquant.
                        <br>
                        <span class="text-xs font-mono">Fichier attendu : <?php echo htmlspecialchars(basename($ai_file_path)); ?></span>
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($has_valid_ai): ?>
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
            
            <div class="bg-white rounded-xl shadow-md p-6 flex flex-col items-center justify-center col-span-1">
                <h3 class="text-gray-500 font-medium mb-4 uppercase text-xs tracking-wider">Score de Risque</h3>
                <div class="relative w-40 h-40">
                    <canvas id="riskChart"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-4xl font-bold text-gray-800"><?php echo $ai_data['risk_score'] ?? 0; ?></span>
                        <span class="text-sm text-gray-400">/ 100</span>
                    </div>
                </div>
                <div class="mt-4 px-4 py-1 rounded-full text-sm font-bold border
                    <?php 
                        $sl = $ai_data['risk_level'] ?? 'Inconnu';
                        if (in_array($sl, ['Critique', 'Critical', 'Élevé', 'High'])) echo 'bg-red-50 text-red-700 border-red-200';
                        elseif (in_array($sl, ['Moyen', 'Medium'])) echo 'bg-yellow-50 text-yellow-700 border-yellow-200';
                        else echo 'bg-green-50 text-green-700 border-green-200';
                    ?>">
                    <?php echo htmlspecialchars($sl); ?>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 col-span-1 lg:col-span-3">
                <div class="flex items-center mb-4">
                    <div class="p-2 bg-blue-100 rounded-lg mr-3">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800">Synthèse de l'Expert</h3>
                </div>
                <p class="text-gray-600 leading-relaxed mb-6 text-lg">
                    <?php echo htmlspecialchars($ai_data['summary'] ?? "Analyse terminée."); ?>
                </p>
                
                <?php if (!empty($ai_data['key_findings'])): ?>
                <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
                    <h4 class="font-bold text-blue-800 text-xs uppercase tracking-wider mb-3">Observations Clés</h4>
                    <ul class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <?php foreach ($ai_data['key_findings'] as $finding): ?>
                            <li class="flex items-start text-blue-900 text-sm">
                                <span class="text-blue-500 mr-2 font-bold">•</span>
                                <?php echo htmlspecialchars($finding); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($ai_data['threats_detected'])): ?>
        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
            <span class="bg-red-100 text-red-600 p-2 rounded-lg mr-3 shadow-sm">⚠️</span>
            Menaces Détectées
        </h2>
        <div class="space-y-6 mb-10">
            <?php foreach ($ai_data['threats_detected'] as $threat): ?>
                <div class="bg-white border-l-8 <?php echo (stripos($threat['severity'], 'High') !== false || stripos($threat['severity'], 'Crit') !== false) ? 'border-red-500' : 'border-yellow-400'; ?> rounded-lg shadow-sm p-6 hover:shadow-md transition duration-200">
                    <div class="flex justify-between items-start mb-3">
                        <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($threat['title']); ?></h3>
                        <span class="px-3 py-1 text-xs font-bold rounded uppercase tracking-wider 
                            <?php echo (stripos($threat['severity'], 'High') !== false || stripos($threat['severity'], 'Crit') !== false) ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                            <?php echo htmlspecialchars($threat['severity']); ?>
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-4">
                        <div>
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Description Technique</h4>
                            <p class="text-gray-700 leading-relaxed"><?php echo htmlspecialchars($threat['description']); ?></p>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Impact Potentiel</h4>
                            <p class="text-red-700 font-medium leading-relaxed bg-red-50 p-3 rounded border border-red-100">
                                <?php echo htmlspecialchars($threat['potential_impact'] ?? 'Non spécifié'); ?>
                            </p>
                        </div>
                    </div>
                    
                    <?php if (!empty($threat['remediation'])): ?>
                    <div class="mt-6 pt-4 border-t border-gray-100">
                        <h4 class="text-xs font-bold text-green-600 uppercase tracking-wider mb-2">Action Recommandée</h4>
                        <div class="flex items-start text-green-800 bg-green-50 p-3 rounded-lg border border-green-100">
                            <svg class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span><?php echo htmlspecialchars($threat['remediation']); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <script>
            const score = <?php echo $ai_data['risk_score'] ?? 0; ?>;
            const ctx = document.getElementById('riskChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [score, 100 - score],
                        backgroundColor: [
                            score > 60 ? '#EF4444' : (score > 30 ? '#F59E0B' : '#10B981'), 
                            '#E5E7EB'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    cutout: '80%',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { enabled: false } },
                    animation: { animateScale: true, animateRotate: true }
                }
            });
        </script>
        <?php endif; ?>

        <div class="bg-gray-900 rounded-xl shadow-lg overflow-hidden mt-8">
            <div class="px-6 py-4 bg-gray-800 border-b border-gray-700 flex justify-between items-center">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <h3 class="text-gray-300 font-mono text-sm font-bold">Terminal Output (Raw)</h3>
                </div>
                <div class="flex items-center space-x-4">
                     <span class="text-xs text-gray-500 font-mono"><?php echo htmlspecialchars(basename($scan['output_file'])); ?></span>
                     <button onclick="copyOutput()" class="text-xs text-blue-400 hover:text-blue-300 uppercase tracking-wider font-bold">Copier</button>
                </div>
            </div>
            <div class="p-6 overflow-x-auto relative">
                <pre id="rawOutput" class="font-mono text-xs leading-relaxed text-green-400 whitespace-pre-wrap selection:bg-green-900 selection:text-white"><?php echo htmlspecialchars($results ?: "En attente des données... \n(Si ce message persiste, vérifiez les permissions du dossier /var/www/html/results/)"); ?></pre>
            </div>
        </div>

        <script>
            function copyOutput() {
                const text = document.getElementById('rawOutput').innerText;
                navigator.clipboard.writeText(text).then(() => {
                    alert('Sortie copiée dans le presse-papier !');
                });
            }
        </script>
    </div>
</body>
</html>
