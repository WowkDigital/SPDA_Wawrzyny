<?php
session_start();

$password = "admin123"; // Simple hardcoded password for demonstration

if (isset($_POST['password']) && $_POST['password'] === $password) {
    $_SESSION['logged_in'] = true;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// Connect to SQLite
$dbFile = 'visits.db';
$stats = [
    'total_visits' => 0,
    'unique_ips' => 0,
    'recent_visits' => [],
    'browser_stats' => []
];

if (file_exists($dbFile)) {
    try {
        $pdo = new PDO("sqlite:" . $dbFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Total visits
        $stats['total_visits'] = $pdo->query("SELECT COUNT(*) FROM visits")->fetchColumn();

        // Unique IPs
        $stats['unique_ips'] = $pdo->query("SELECT COUNT(DISTINCT ip) FROM visits")->fetchColumn();

        // Recent 10 visits
        $stats['recent_visits'] = $pdo->query("SELECT * FROM visits ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

        // Browser groups (simplified UA extraction)
        $browserQuery = "SELECT 
                            CASE 
                                WHEN user_agent LIKE '%Chrome%' AND user_agent NOT LIKE '%Edg%' THEN 'Chrome'
                                WHEN user_agent LIKE '%Firefox%' THEN 'Firefox'
                                WHEN user_agent LIKE '%Safari%' AND user_agent NOT LIKE '%Chrome%' THEN 'Safari'
                                WHEN user_agent LIKE '%Edg%' THEN 'Edge'
                                ELSE 'Inny'
                            END as browser,
                            COUNT(*) as count
                         FROM visits
                         GROUP BY browser
                         ORDER BY count DESC";
        $stats['browser_stats'] = $pdo->query($browserQuery)->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $dbError = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admina - Zaawansowane Statystyki</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .glass-card { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(8px); border: 1px solid rgba(255, 255, 255, 0.3); }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 flex flex-col items-center p-6 pb-20">

    <?php if (!$is_logged_in): ?>
    <div class="w-full max-w-sm bg-white rounded-3xl shadow-2xl p-8 border border-slate-100 mt-20">
        <div class="w-12 h-12 bg-indigo-600 rounded-xl flex items-center justify-center mb-6 shadow-indigo-200 shadow-xl mx-auto">
            <i data-lucide="lock" class="text-white w-6 h-6"></i>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 mb-2 text-center">Admin Access</h1>
        <p class="text-slate-500 text-sm mb-8 text-center px-4 italic">Zaloguj się aby zarządzać statystykami</p>
        
        <form method="POST" class="space-y-4">
            <div>
                <label for="password" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Hasło dostępu</label>
                <input type="password" name="password" id="password" required 
                       placeholder="••••••••"
                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all placeholder:text-slate-300">
            </div>
            <button type="submit" 
                    class="w-full bg-slate-900 text-white font-bold py-3 rounded-xl hover:bg-slate-800 transition-all shadow-lg active:scale-95">
                WEJDŹ DO PANELU
            </button>
            <?php if (isset($_POST['password'])): ?>
                <div class="flex items-center space-x-2 text-rose-500 bg-rose-50 p-3 rounded-lg text-sm justify-center">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                    <span>Nieprawidłowe hasło</span>
                </div>
            <?php endif; ?>
        </form>
    </div>
    <?php else: ?>
    
    <div class="w-full max-w-5xl">
        <!-- Header -->
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
            <div>
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Statystyki Odwiedzin</h1>
                <p class="text-slate-500 font-medium">Monitoring ruchu na stronie w czasie rzeczywistym (SQLite)</p>
            </div>
            <div class="flex items-center gap-4">
                <a href="../index.html" class="flex items-center gap-2 text-slate-600 hover:text-indigo-600 transition-colors bg-white px-4 py-2 rounded-xl border border-slate-200 shadow-sm">
                    <i data-lucide="external-link" class="w-4 h-4"></i>
                    <span>Podgląd strony</span>
                </a>
                <a href="?logout=1" class="flex items-center gap-2 text-rose-600 hover:text-white hover:bg-rose-500 transition-all bg-white px-4 py-2 rounded-xl border border-rose-100 shadow-sm">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                    <span>Wyloguj</span>
                </a>
            </div>
        </header>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-indigo-600 p-8 rounded-[2rem] text-white shadow-2xl relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 bg-indigo-500 w-32 h-32 rounded-full blur-3xl opacity-50 transition-all group-hover:scale-110"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <i data-lucide="eye" class="w-8 h-8 opacity-60"></i>
                        <span class="text-xs bg-white/20 px-2 py-1 rounded-full backdrop-blur-sm">Calkowite</span>
                    </div>
                    <p class="text-6xl font-black mb-1"><?= number_format($stats['total_visits'], 0, ',', ' ') ?></p>
                    <p class="text-indigo-100 font-medium uppercase text-xs tracking-widest">Wszystkie Wejścia</p>
                </div>
            </div>

            <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-xl group">
                <div class="flex items-center justify-between mb-4">
                    <i data-lucide="users" class="w-8 h-8 text-emerald-500 opacity-60"></i>
                    <span class="text-xs bg-emerald-50 text-emerald-600 px-2 py-1 rounded-full">Unikalne</span>
                </div>
                <p class="text-6xl font-black text-slate-900 mb-1"><?= number_format($stats['unique_ips'], 0, ',', ' ') ?></p>
                <p class="text-slate-400 font-medium uppercase text-xs tracking-widest">Unikalne Adresy IP</p>
            </div>

            <!-- Browser Breakdown -->
            <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-xl">
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-4">Najpopularniejsze Przeglądarki</h3>
                <div class="space-y-4">
                    <?php if (empty($stats['browser_stats'])): ?>
                        <p class="text-slate-300 italic text-sm">Brak danych...</p>
                    <?php else: foreach($stats['browser_stats'] as $b): ?>
                        <div class="space-y-1">
                            <div class="flex justify-between text-xs font-bold text-slate-700">
                                <span><?= $b['browser'] ?></span>
                                <span><?= round(($b['count'] / $stats['total_visits']) * 100, 1) ?>%</span>
                            </div>
                            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                <div class="bg-indigo-500 h-full rounded-full transition-all duration-1000" style="width: <?= ($b['count'] / $stats['total_visits']) * 100 ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>

        <!-- Latest Visits Table -->
        <div class="bg-white rounded-[2rem] border border-slate-100 shadow-2xl overflow-hidden">
            <div class="p-8 border-b border-slate-50 flex items-center justify-between">
                <h2 class="text-xl font-bold text-slate-900">Ostatnie odwiedziny</h2>
                <div class="h-8 w-8 bg-slate-50 flex items-center justify-center rounded-lg text-slate-400">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-8 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Data i Godzina</th>
                            <th class="px-8 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Adres IP</th>
                            <th class="px-8 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Przeglądarka</th>
                            <th class="px-8 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">User Agent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if (empty($stats['recent_visits'])): ?>
                            <tr>
                                <td colspan="4" class="px-8 py-10 text-center text-slate-300 italic">Nie znaleziono jeszcze żadnych wizyt.</td>
                            </tr>
                        <?php else: foreach($stats['recent_visits'] as $v): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-8 py-4 text-sm font-medium text-slate-600"><?= $v['created_at'] ?></td>
                                <td class="px-8 py-4">
                                    <span class="bg-slate-100 text-slate-500 text-xs font-mono px-2 py-1 rounded-md"><?= $v['ip'] ?></span>
                                </td>
                                <td class="px-8 py-4">
                                    <span class="text-xs font-bold text-indigo-500 bg-indigo-50 px-3 py-1 rounded-full uppercase">
                                        <?php
                                            if (strpos($v['user_agent'], 'Edg/')) echo 'Edge';
                                            elseif (strpos($v['user_agent'], 'Chrome')) echo 'Chrome';
                                            elseif (strpos($v['user_agent'], 'Firefox')) echo 'Firefox';
                                            elseif (strpos($v['user_agent'], 'Safari')) echo 'Safari';
                                            else echo 'Inny';
                                        ?>
                                    </span>
                                </td>
                                <td class="px-8 py-4 text-xs text-slate-400 max-w-xs truncate" title="<?= $v['user_agent'] ?>">
                                    <?= $v['user_agent'] ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <footer class="mt-20 text-center">
            <p class="text-sm text-slate-400">Panel Statystyk v2.0 &bull; SQLite Engine</p>
            <div class="flex justify-center gap-2 mt-4 opacity-20 filter grayscale hover:opacity-100 hover:grayscale-0 transition-all">
                <i data-lucide="database" class="w-5 h-5"></i>
                <i data-lucide="shield-check" class="w-5 h-5"></i>
                <i data-lucide="activity" class="w-5 h-5"></i>
            </div>
        </footer>
    </div>
    <?php endif; ?>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
