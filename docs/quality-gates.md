# Portes de qualité Hociatec

Ces contrôles doivent passer avant toute mise en production.

## Backend

Depuis `backend/` :

```bash
composer validate --strict --no-check-publish
composer audit --locked
php tools/check_architecture.php
vendor/bin/php-cs-fixer fix --dry-run --diff --using-cache=no
vendor/bin/deptrac analyse --config-file=deptrac.php --no-progress --no-cache
vendor/bin/phpstan analyse --no-progress --memory-limit=1G
APP_ENV=prod php bin/console lint:container
```

## Frontend

Depuis `frontend/` :

```bash
npm ci
npm run typecheck
npm run lint
npm test -- --run
npm audit --omit=dev --audit-level=critical
npm run build
```

L’audit signale actuellement une vulnérabilité haute dans `react-router` liée aux
fonctionnalités RSC/Server Actions. Hociatec utilise une SPA client sans RSC ni
Server Actions ; aucune version corrigée de `react-router-dom` n’est publiée dans
la branche stable utilisée. Le résultat complet de `npm audit` doit néanmoins être
revu à chaque mise à jour de cette dépendance. La CI bloque les vulnérabilités
critiques et conserve cette alerte haute comme exception documentée.

## Vérifications de déploiement

Après une migration ou une modification de service :

```bash
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear --env=prod --no-warmup
php bin/console cache:warmup --env=prod
sudo systemctl reload php8.3-fpm
```

Vérifier ensuite les routes critiques, l’authentification, les téléchargements privés, les e-mails transactionnels et les sauvegardes.

## Couverture utile minimale

Le projet ne suit pas seulement un pourcentage global. Une mise en production est bloquée si les scénarios critiques suivants ne restent pas couverts par des tests automatisés :

- authentification, rotation et révocation des refresh tokens ;
- permissions HTTP, ownership client, CSRF et cookies de session ;
- workflows de paiement Stripe, webhooks, idempotence et retries ;
- stock, rendez-vous, vouchers, outbox et autres zones sensibles à la concurrence ;
- sauvegardes, restauration, téléchargements privés et exports admin ;
- pagination bornée, cache versionné et transitions de statuts métier.

Les suites d’architecture et d’intégration servent de garde-fous sur ces zones. Toute nouvelle feature critique doit rejoindre cette liste avant livraison.
