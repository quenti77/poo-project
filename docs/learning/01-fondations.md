# Phase 1 : Fondations

Cette phase pose les bases essentielles avant toute technique DevOps.
Un projet bien documenté et testé est la condition préalable à tout déploiement réussi.

---

## 1.1 Documentation

### Pourquoi documenter en premier

La documentation n'est pas une corvée de fin de projet. C'est un outil de réflexion :

- **Clarifier sa pensée** : écrire force à structurer ses idées
- **Onboarding** : un nouveau développeur doit pouvoir comprendre le projet en 15 minutes
- **Mémoire** : toi dans 6 mois, tu auras oublié pourquoi tu as fait ce choix

### Markdown - Les bases

```markdown
# Titre principal (h1)
## Section (h2)
### Sous-section (h3)

**Gras** et *italique*

- Liste à puces
- Autre élément

1. Liste numérotée
2. Deuxième élément

\`code inline\`

\`\`\`bash
bloc de code
\`\`\`

[Lien](https://example.com)

> Citation ou note importante

| Colonne 1 | Colonne 2 |
|-----------|-----------|
| Valeur    | Valeur    |
```

### Fichiers à produire

#### README.md

Le README est la vitrine du projet. Structure recommandée :

```markdown
# Nom du projet

Description courte (1-2 phrases) de ce que fait le projet.

## Stack technique

- Backend : PHP 8.x, Laravel 11
- Frontend : Vue.js 3, Inertia.js
- Base de données : PostgreSQL 16
- Cache : Redis

## Prérequis

- Docker Engine 24+
- Task (taskfile.dev)

## Installation

\`\`\`bash
git clone <repo>
cd project
task init
task up
\`\`\`

## Commandes utiles

\`\`\`bash
task -l        # Liste toutes les commandes
task logs      # Voir les logs
task shell     # Accéder au container
\`\`\`

## Structure du projet

\`\`\`
project/
├── app/           # Code Laravel
├── infra/         # Infrastructure
└── docs/          # Documentation
\`\`\`

## Contribuer

[Lien vers CONTRIBUTING.md si existant]

## Licence

MIT
```

#### docs/architecture.md

Décrit comment les composants interagissent :

```markdown
# Architecture

## Vue d'ensemble

\`\`\`
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Nginx     │────▶│   PHP-FPM   │────▶│  PostgreSQL │
│   (proxy)   │     │   (Laravel) │     │    (DB)     │
└─────────────┘     └─────────────┘     └─────────────┘
                           │
                           ▼
                    ┌─────────────┐
                    │    Redis    │
                    │   (cache)   │
                    └─────────────┘
\`\`\`
```

## Composants

### Nginx
- Reverse proxy
- Sert les assets statiques
- Termine le SSL

### PHP-FPM
- Exécute le code Laravel
- Pool de workers configuré pour X requêtes simultanées

### PostgreSQL
- Base de données principale
- Réplication en lecture (si besoin de scaling)

### Redis
- Cache applicatif
- Sessions
- Queue jobs

## Flux de données

1. Requête HTTPS arrive sur Nginx
2. Nginx transmet à PHP-FPM
3. Laravel traite la requête
4. Données récupérées depuis PostgreSQL (ou cache Redis)
5. Réponse renvoyée au client
```

#### docs/decisions/ (ADR)

Les Architecture Decision Records documentent les choix techniques importants.

Format d'un ADR (`docs/decisions/001-choix-base-donnees.md`) :

```markdown
# ADR 001 : Choix de PostgreSQL comme base de données

## Statut

Accepté

## Date

2024-01-15

## Contexte

Le projet nécessite une base de données relationnelle pour stocker
les données utilisateurs et les transactions.

Options considérées :
- MySQL/MariaDB
- PostgreSQL
- SQLite

## Décision

PostgreSQL a été choisi.

## Justification

- Support natif du JSON (JSONB) pour les données semi-structurées
- Meilleures performances sur les requêtes complexes
- Extensions disponibles (PostGIS si besoin géo)
- Laravel supporte parfaitement PostgreSQL

## Conséquences

- Besoin de connaître les spécificités PostgreSQL
- Image Docker plus lourde que SQLite
- Configuration de réplication différente de MySQL
```

### Outils recommandés

- **Mermaid** : diagrammes en Markdown (supporté par GitHub/GitLab)
- **Draw.io** : diagrammes plus complexes (export en SVG)
- **mdBook** ou **Docusaurus** : si besoin d'un site de documentation

---

## 1.2 Base applicative saine

### Configuration Laravel

#### Fichiers d'environnement

```bash
# .env.example - template versionné
APP_NAME=MonApp
APP_ENV=local
APP_DEBUG=true
APP_URL=https://app.test

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=app
DB_USERNAME=app
DB_PASSWORD=

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=redis
REDIS_PORT=6379
```

**Règles importantes** :
- `.env` n'est JAMAIS versionné (dans `.gitignore`)
- `.env.example` contient toutes les clés sans valeurs sensibles
- Les valeurs par défaut doivent fonctionner pour le dev local

#### Configuration par environnement

```php
// config/database.php
'pgsql' => [
    'driver' => 'pgsql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    // Valeurs par défaut sensées pour le dev
],
```

#### Cache de configuration

```bash
# En production uniquement
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Pour tout nettoyer
php artisan optimize:clear
```

### Inertia.js - Build frontend

#### Configuration Vite

```javascript
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    // Pour le build de production
    build: {
        manifest: true,
        outDir: 'public/build',
    },
});
```

#### Build des assets

```bash
# Développement (avec hot reload)
npm run dev

# Production
npm run build
```

### Git - Stratégie de branches

#### Trunk-based development (recommandé)

```
main ─────●─────●─────●─────●─────●─────▶
           \   /       \   /
            ●─●         ●─●
         feature-1   feature-2
```

- `main` est toujours déployable
- Branches de feature courtes (< 2 jours)
- Merge fréquents via Pull Request
- CI obligatoire avant merge

#### GitFlow simplifié (si releases planifiées)

```
main ────────────●───────────────●────────▶
                 ▲               ▲
develop ───●───●─┴───●───●───●───┴───●────▶
            \       /
             ●─────●
            feature
```

#### Conventions de commits

```bash
# Format
type(scope): description courte

# Types courants
feat: nouvelle fonctionnalité
fix: correction de bug
docs: documentation
refactor: refactoring sans changement fonctionnel
test: ajout/modification de tests
chore: maintenance (deps, config)

# Exemples
feat(auth): add password reset functionality
fix(api): handle null response from payment provider
docs(readme): update installation instructions
```

---

## 1.3 Tests

### Pourquoi tester

- **Confiance** : modifier du code sans casser l'existant
- **Documentation** : les tests montrent comment utiliser le code
- **CI/CD** : impossible d'automatiser le déploiement sans tests

### Pyramide des tests

```
        ╱╲
       ╱  ╲        E2E (peu, lents, coûteux)
      ╱────╲
     ╱      ╲      Intégration (modérés)
    ╱────────╲
   ╱          ╲    Unitaires (beaucoup, rapides)
  ╱────────────╲
```

### Tests unitaires (PHPUnit)

Testent une unité isolée (classe, méthode) :

```php
// tests/Unit/Services/PriceCalculatorTest.php
namespace Tests\Unit\Services;

use App\Services\PriceCalculator;
use PHPUnit\Framework\TestCase;

class PriceCalculatorTest extends TestCase
{
    private PriceCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new PriceCalculator();
    }

    public function test_calculates_price_with_tax(): void
    {
        $result = $this->calculator->withTax(100, 0.20);

        $this->assertEquals(120, $result);
    }

    public function test_applies_discount(): void
    {
        $result = $this->calculator->withDiscount(100, 10);

        $this->assertEquals(90, $result);
    }

    public function test_throws_exception_for_negative_price(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculator->withTax(-50, 0.20);
    }
}
```

### Tests d'intégration (Feature tests Laravel)

Testent plusieurs composants ensemble :

```php
// tests/Feature/Api/UserControllerTest.php
namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_users(): void
    {
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/users');

        $response
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_user(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertCreated();
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/protected-resource');

        $response->assertUnauthorized();
    }
}
```

### Tests E2E (Playwright)

Testent l'application complète via le navigateur :

```javascript
// tests/e2e/login.spec.js
import { test, expect } from '@playwright/test';

test.describe('Authentication', () => {
    test('user can login with valid credentials', async ({ page }) => {
        await page.goto('/login');

        await page.fill('input[name="email"]', 'user@example.com');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');

        await expect(page).toHaveURL('/dashboard');
        await expect(page.locator('h1')).toContainText('Welcome');
    });

    test('shows error with invalid credentials', async ({ page }) => {
        await page.goto('/login');

        await page.fill('input[name="email"]', 'wrong@example.com');
        await page.fill('input[name="password"]', 'wrongpassword');
        await page.click('button[type="submit"]');

        await expect(page.locator('.error-message')).toBeVisible();
        await expect(page).toHaveURL('/login');
    });
});
```

### Analyse statique (PHPStan)

Détecte les erreurs sans exécuter le code :

```yaml
# phpstan.neon
parameters:
    level: 6  # 0-9, augmenter progressivement
    paths:
        - app
        - tests
    excludePaths:
        - vendor
```

```bash
# Exécution
./vendor/bin/phpstan analyse

# Résultat exemple
------ --------------------------------------------------------
 Line   app/Services/PaymentService.php
------ --------------------------------------------------------
 45     Parameter #1 $amount of method process() expects int,
        string given.
------ --------------------------------------------------------
```

### Linting (PHP-CS-Fixer)

Uniformise le style de code :

```php
// .php-cs-fixer.php
<?php

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'ordered_imports' => true,
        'single_quote' => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__ . '/app')
            ->in(__DIR__ . '/tests')
    );
```

```bash
# Vérifier
./vendor/bin/php-cs-fixer fix --dry-run --diff

# Corriger automatiquement
./vendor/bin/php-cs-fixer fix
```

### Configuration complète

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!-- phpunit.xml -->
<phpunit
    colors="true"
    stopOnFailure="false"
    cacheDirectory=".phpunit.cache"
>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>app</directory>
        </include>
    </source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
    </php>
</phpunit>
```

### Commandes Task file

```yaml
# Taskfile.yml
version: '3'

tasks:
  test:
    desc: "Run all tests"
    cmds:
      - ./vendor/bin/phpunit

  test:unit:
    desc: "Run unit tests only"
    cmds:
      - ./vendor/bin/phpunit --testsuite=Unit

  test:feature:
    desc: "Run feature tests only"
    cmds:
      - ./vendor/bin/phpunit --testsuite=Feature

  test:coverage:
    desc: "Run tests with coverage report"
    cmds:
      - ./vendor/bin/phpunit --coverage-html=coverage

  lint:
    desc: "Check code style"
    cmds:
      - ./vendor/bin/php-cs-fixer fix --dry-run --diff

  lint:fix:
    desc: "Fix code style"
    cmds:
      - ./vendor/bin/php-cs-fixer fix

  analyse:
    desc: "Run static analysis"
    cmds:
      - ./vendor/bin/phpstan analyse

  quality:
    desc: "Run all quality checks"
    cmds:
      - task: lint
      - task: analyse
      - task: test
```

---

## Checklist de fin de phase

- [ ] README.md complet et à jour
- [ ] Architecture documentée avec schéma
- [ ] Au moins 1 ADR rédigé
- [ ] `.env.example` complet
- [ ] Tests unitaires sur les services critiques
- [ ] Tests feature sur les routes principales
- [ ] PHPStan niveau 5+ sans erreur
- [ ] PHP-CS-Fixer configuré
- [ ] Task file avec commandes de qualité
