#!/usr/bin/env php
<?php

const DOMAINS = ["poo.localhost"];
const SSL_DIR = __DIR__ . '/nginx/ssl';

$emojis = [
    'red_cross' => "❌",
    'tick' => "✅",
];

echo "==================================================\n";
echo "Génération des certificats SSL avec l'outil MKCert\n";
echo "==================================================\n";

// Vérification que mkcert est installé
echo "\nVérification de l'installation de MKCert...\n";
exec('command -v mkcert 2>/dev/null', $output, $returnCode);

if ($returnCode !== 0) {
    echo "{$emojis['red_cross']} Erreur: MKCert n'est pas installé sur ce système.\n";
    echo "   Veuillez installer MKCert avant de continuer.\n";
    echo "   Plus d'informations: https://github.com/FiloSottile/mkcert\n";
    exit(1);
}

echo "{$emojis['tick']} MKCert est installé\n";

// Installation de l'autorité de certification locale
echo "\nInstallation de l'autorité de certification locale...\n";
exec('mkcert -install 2>&1', $output, $returnCode);

if ($returnCode !== 0) {
    echo "{$emojis['red_cross']} Erreur lors de l'installation de la CA locale.\n";
    exit(1);
}

echo "{$emojis['tick']} CA locale installée\n";

// Créer le répertoire SSL s'il n'existe pas
if (!is_dir(SSL_DIR)) {
    echo "\nCréation du répertoire " . SSL_DIR . "...\n";
    if (!mkdir($concurrentDirectory = SSL_DIR, 0755, true) && !is_dir($concurrentDirectory)) {
        echo "{$emojis['red_cross']} Erreur lors de la création du répertoire SSL.\n";
        exit(1);
    }
    echo "{$emojis['tick']} Répertoire créé\n";
}

// Génération des certificats pour chaque domaine
foreach (DOMAINS as $domain) {
    echo "\nGénération des certificats pour $domain...\n";

    $keyFile = SSL_DIR . "/$domain-key.pem";
    $certFile = SSL_DIR . "/$domain.pem";

    $command = sprintf(
        'mkcert -key-file %s -cert-file %s %s 2>&1',
        escapeshellarg($keyFile),
        escapeshellarg($certFile),
        escapeshellarg($domain),
    );

    exec($command, $output, $returnCode);

    if ($returnCode !== 0) {
        echo "{$emojis['red_cross']} Erreur lors de la génération des certificats pour $domain.\n";
        exit(1);
    }

    if (file_exists($certFile) && file_exists($keyFile)) {
        echo "{$emojis['tick']} Certificats générés avec succès!\n";
        echo "\nFichiers créés:\n";
        echo " - $certFile (certificat)\n";
        echo " - $keyFile (clé privée)\n";
    } else {
        echo "{$emojis['red_cross']} Les fichiers de certificat n'ont pas été créés.\n";
        exit(1);
    }
}

echo "\n{$emojis['tick']} Tous les certificats ont été générés avec succès!\n";
echo "\nVous pouvez maintenant utiliser ces certificats dans votre configuration Docker/Nginx.\n";
