#!/bin/bash

# Script pour générer des certificats SSL avec mkcert pour poo.localhost

DOMAIN="poo.localhost"

echo "================================================"
echo "Génération des certificats SSL avec mkcert"
echo "================================================"
echo ""

# Vérifier si mkcert est installé
if ! command -v mkcert &> /dev/null; then
    echo "❌ mkcert n'est pas installé sur votre système."
    echo ""
    echo "Installation de mkcert:"
    echo ""
    echo "Linux (Debian/Ubuntu):"
    echo "  sudo apt install libnss3-tools"
    echo "  curl -L https://github.com/FiloSottile/mkcert/releases/download/v1.4.4/mkcert-v1.4.4-linux-amd64 -o mkcert"
    echo "  chmod +x mkcert"
    echo "  sudo mv mkcert /usr/local/bin/"
    echo ""
    echo "macOS:"
    echo "  brew install mkcert"
    echo ""
    echo "Windows (avec Chocolatey):"
    echo "  choco install mkcert"
    echo ""
    exit 1
fi

echo "✓ mkcert est installé"
echo ""

# Installer la CA locale si ce n'est pas déjà fait
echo "Installation de l'autorité de certification locale..."
mkcert -install
echo ""

# Générer les certificats
echo "Génération des certificats pour $DOMAIN..."
mkcert -key-file "$DOMAIN-key.pem" -cert-file "$DOMAIN.pem" "$DOMAIN" "*.$DOMAIN"
echo ""

if [ -f "$DOMAIN.pem" ] && [ -f "$DOMAIN-key.pem" ]; then
    echo "✅ Certificats générés avec succès!"
    echo ""
    echo "Fichiers créés:"
    echo "  - $DOMAIN.pem (certificat)"
    echo "  - $DOMAIN-key.pem (clé privée)"
    echo ""
    echo "Les certificats sont maintenant automatiquement approuvés par votre navigateur."
    echo "Vous pouvez démarrer l'environnement Docker avec: cd infra && docker-compose up -d"
else
    echo "❌ Erreur lors de la génération des certificats"
    exit 1
fi
