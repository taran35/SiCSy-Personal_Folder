<?php
session_start();
require_once '../../account/bdd.php';



if (isset($_SESSION['adm_token'])) {
    $token = $_SESSION['adm_token'];
    $sql = "SELECT * FROM adm_token WHERE token = ? LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    if ($result->num_rows == 0) {
        header('Location: ../../admin/login.php');
        exit;
    }
} else {
    header('Location: ../../admin/login.php');
    exit;
}

$configPath = "../../themes-admin/config.json";
$json = file_get_contents($configPath);
$data = json_decode($json, true);
$fenetre = basename(__FILE__);
$folder = $data['theme'];

$configPath2 = "../../themes-admin/" . $folder . "/config.json";
$json2 = file_get_contents($configPath2);
$data2 = json_decode($json2, true);
$file = $data2[$fenetre];
$basePath = $data2['base'];
$base = "/themes-admin/" . $folder . "/" . $basePath;
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Création de compte</title>
    <link rel="stylesheet" href="<?php echo $base ?>">
</head>

<body>
    <header>
        <div style="display:flex; justify-content: space-between; align-items:center;">
            <button onclick="window.location.href='/admin/dash.php'" id="home" aria-label="retour a la page d'accueil"
                style="
            background:none; 
            border:none; 
            color:white; 
            font-size:1.5rem; 
            cursor:pointer;
        ">🏠</button>
            <div>Bienvenue, <?= htmlspecialchars($_SESSION['username']) ?> 👋</div>
            <button id="theme-toggle" aria-label="Basculer le thème" style="
            background:none; 
            border:none; 
            color:white; 
            font-size:1.5rem; 
            cursor:pointer;
        ">🌙</button>
        </div>
    </header>
    <main class="container" style="display: flex;">
        <div class="box">
            <?php echo setup() ?>
        </div>
    </main>
    <footer>
        <p><a class="logout" href="logout.php">Se déconnecter</a></p>
        <p class="credits"><a class="credits2" href="https://github.com/taran35/cloud">Copyright © 2025 Taran35</a>
        </p>
    </footer>
    <script>
        const themeToggleBtn = document.getElementById('theme-toggle');
        const currentTheme = localStorage.getItem('theme');

        if (currentTheme) {
            document.documentElement.setAttribute('data-theme', currentTheme);
            themeToggleBtn.textContent = currentTheme === 'dark' ? '☀️' : '🌙';
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
            themeToggleBtn.textContent = '🌙';
            localStorage.setItem('theme', 'light');
        }

        function switchTheme() {
            const theme = document.documentElement.getAttribute('data-theme');
            if (theme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'light');
                themeToggleBtn.textContent = '🌙';
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                themeToggleBtn.textContent = '☀️';
                localStorage.setItem('theme', 'dark');
            }
        }

        themeToggleBtn.addEventListener('click', switchTheme);
    </script>
</body>

</html>




<?php
function setup()
{
    $configPath3 = 'config.json';
    $data = json_decode(file_get_contents($configPath3), true);
    $setup = $data["setup"];

    if ($setup == true) {
        echo "<h1 style='color: green'> Configuration déjà effectuée</h1>";
        return;
    }




    $filePath = "../../main/php/getfiles.php";

    if (!file_exists($filePath)) {
        echo "<h1 style='color: red;'>Erreur : fichier $filePath introuvable\n </1>";
        return;
    }

    $code = file_get_contents($filePath);


    $patternRequire = '/\$parent\s*=\s*\$_GET\[\s*[\'"]parent[\'"]\s*]\s*\?\?\s*[\'"]\/[\'"]\s*;/';


    $replacementRequire = <<<PHP
\$session_start();
\$parent = \$_GET['parent'] ?? '/';
\$1
require_once '../../modules/Personal_folder/exclude_users.php';
\$config = json_decode(file_get_contents('../../modules/Personal_folder/config.json'), true);
\$exclusion_active = isset(\$config['status']) && \$config['status'] === 'on';

\$pseudoConnecte = \$_SESSION['username'] ?? '';
\$pseudosToExclude = [];

if ((\$parent === '/') && \$exclusion_active) {
    \$pseudosToExclude = getExcludedUsernames(\$mysqli, \$pseudoConnecte);
}

PHP;

    $code = preg_replace($patternRequire, $replacementRequire, $code, 1);

    $patternWhile = '/while\s*\(\s*\$row\s*=\s*\$result->fetch_assoc\(\)\s*\)\s*\{([\s\S]*?)\}/m';

    $replacementWhile = <<<PHP
while (\$row = \$result->fetch_assoc()) {
    if ((\$parent === '/') && in_array(\$row['name'], \$pseudosToExclude)) {
        continue;
    }
    \$rows[] = \$row;
}
PHP;

    $code = preg_replace($patternWhile, $replacementWhile, $code, 1);
    file_put_contents($filePath, $code);

    $data["setup"] = true;
    file_put_contents($configPath3, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "<h1 style='color: green;'>Configuration effectuée</h1>";

}
?>