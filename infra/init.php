<?php /** @noinspection PhpComposerExtensionStubsInspection */

const DOMAIN = 'poo.test';

$projectRoot = dirname(__DIR__);

function createAppEnv(string $projectRoot): void
{
    $envPath = $projectRoot . '/app/.env';
    $envExamplePath = $projectRoot . '/app/.env.example';

    if (file_exists($envPath)) {
        echo "ℹ️  app/.env existe déjà, ignoré.\n";
        return;
    }

    if (!file_exists($envExamplePath)) {
        echo "❌ app/.env.example introuvable.\n";
        return;
    }

    copy($envExamplePath, $envPath);
    echo "✅ app/.env créé à partir de .env.example\n";
}

function createRootEnv(string $projectRoot): void
{
    $envPath = $projectRoot . '/.env';

    if (file_exists($envPath)) {
        echo "ℹ️  .env existe déjà, ignoré.\n";
        return;
    }

    if (PHP_OS_FAMILY !== 'Linux') {
        echo "ℹ️  Création de .env ignorée (non-Linux).\n";
        return;
    }

    $uid = posix_getuid();
    $gid = posix_getgid();

    $content = <<<ENV
    UID={$uid}
    GID={$gid}
    ENV;

    file_put_contents($envPath, $content . "\n");
    echo "✅ .env créé avec UID={$uid} et GID={$gid}\n";
}

function isMkcertInstalled(): bool
{
    exec('which mkcert 2>/dev/null', $output, $returnCode);
    return $returnCode === 0;
}

function installMkcert(): bool
{
    echo "📦 Installation de mkcert...\n";

    // Détection du gestionnaire de paquets
    $packageManagers = [
        'pacman' => 'sudo pacman -S --noconfirm mkcert',
        'apt' => 'sudo apt install -y mkcert',
        'dnf' => 'sudo dnf install -y mkcert',
        'brew' => 'brew install mkcert',
    ];

    foreach ($packageManagers as $pm => $command) {
        exec("which $pm 2>/dev/null", $output, $returnCode);
        if ($returnCode === 0) {
            echo "   Utilisation de $pm...\n";
            passthru($command, $returnCode);
            return $returnCode === 0;
        }
    }

    echo "❌ Aucun gestionnaire de paquets supporté trouvé.\n";
    echo "   Installe mkcert manuellement : https://github.com/FiloSottile/mkcert\n";
    return false;
}

function setupMkcert(string $projectRoot): void
{
    $certsDir = $projectRoot . '/infra/nginx/certs';
    $certFile = $certsDir . '/server.crt';
    $keyFile = $certsDir . '/server.key';

    // Vérifier si les certificats existent déjà
    if (file_exists($certFile) && file_exists($keyFile)) {
        echo "ℹ️  Certificats SSL existent déjà, ignoré.\n";
        return;
    }

    // Vérifier/installer mkcert
    if (!isMkcertInstalled()) {
        if (!installMkcert()) {
            return;
        }
    }

    // Installer le CA root de mkcert
    echo "🔐 Installation du CA root mkcert...\n";
    passthru('mkcert -install', $returnCode);
    if ($returnCode !== 0) {
        echo "❌ Échec de l'installation du CA root.\n";
        return;
    }

    // Créer le dossier des certificats
    if (!is_dir($certsDir) && !mkdir($certsDir, 0755, true) && !is_dir($certsDir)) {
        throw new RuntimeException(sprintf('Directory "%s" was not created', $certsDir));
    }

    // Générer les certificats
    echo "🔐 Génération des certificats pour " . DOMAIN . "...\n";
    $command = sprintf(
        'mkcert -cert-file %s -key-file %s %s localhost 127.0.0.1 ::1',
        escapeshellarg($certFile),
        escapeshellarg($keyFile),
        escapeshellarg(DOMAIN)
    );
    passthru($command, $returnCode);

    if ($returnCode === 0) {
        echo "✅ Certificats créés dans infra/nginx/certs/\n";
    } else {
        echo "❌ Échec de la génération des certificats.\n";
    }
}

// Exécution
createAppEnv($projectRoot);
createRootEnv($projectRoot);
setupMkcert($projectRoot);

echo "\n📌 N'oublie pas d'ajouter dans /etc/hosts :\n";
echo "   127.0.0.1 " . DOMAIN . "\n";
