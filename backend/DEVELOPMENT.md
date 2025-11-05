# Backend Dev Setup (Hociatec)

## Prérequis
- PHP 8.2+
- Composer
- Docker + Docker Compose

## Démarrage services (BDD + Mailpit)

Dans `backend/`:

```bash
docker compose -f compose.yaml -f compose.override.yaml up -d
```

- MySQL 8.0 exposé sur `3306` (volume `database_data`)
- Mailpit (SMTP) exposé sur `1025` et UI sur `8025`

## Configuration locale

- Les secrets doivent aller dans `backend/.env.local` (non versionné)
- Un fichier d’exemple est déjà préparé avec une passphrase JWT et Mailpit

Vérifiez/ajustez `backend/.env.local`:

```
JWT_PASSPHRASE=... # votre passphrase
MAILER_DSN="smtp://mailer:1025"
MAILER_FROM="contact@hociatec.fr"
CONTACT_RECIPIENT="contact@hociatec.fr"
APP_SECRET=... # optionnel
```

## Dépendances

```bash
composer install
```

La génération des PDF nécessite `dompdf/dompdf` (ajouté dans composer.json). Si vous aviez déjà installé, relancez `composer install`.

## Clés JWT (rotation et génération)

Générez une nouvelle paire de clés en utilisant la passphrase de `.env.local`:

```bash
php bin/console lexik:jwt:generate-keypair --overwrite
```

- Clés générées dans `config/jwt/` (ignoré par Git)
- La passphrase doit correspondre à `JWT_PASSPHRASE`

## Base de données

```bash
php bin/console doctrine:migrations:migrate -n
```

## Lancer l’API en dev

Au choix:

- Serveur Symfony local
  ```bash
  symfony server:start -d
  ```
- Serveur PHP intégré
  ```bash
  php -S 127.0.0.1:8000 -t public
  ```

## Vérifications clés

- Auth: `POST /api/auth/login`
- Audits (client):
  - `POST /api/audits` (création)
  - `GET /api/audits` / `GET /api/audits/{id}`
  - `POST /api/audits/{id}/pdf` et `/pdf-summary` (PDF)
- Audits (admin):
  - `GET /api/admin/audits` / `GET /api/admin/audits/{id}`
  - `PUT /api/admin/audits/{id}/status`
  - `PUT /api/admin/audits/{auditId}/items/{itemId}` (conformité/commentaire)
  - `POST /api/admin/audits/{id}/pdf` et `/pdf-summary`

La génération PDF doit retourner un binaire PDF (statut HTTP 200). Sans `dompdf/dompdf`, l’API renvoie HTTP 501 avec un message explicite.

