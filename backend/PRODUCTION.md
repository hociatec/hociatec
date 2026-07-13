Production hardening checklist (Hociatec)

1) Environment variables (.env.prod.local)
- APP_ENV=prod
- APP_SECRET=change-me (long, aléatoire)
- CORS_ALLOW_ORIGIN='^https://(www\.)?votre-domaine\.tld$'
- DEFAULT_URI=https://votre-domaine.tld
- DATABASE_URL=mysql://user:motdepasse@db-host:3306/hociatec?serverVersion=8.0&charset=utf8mb4
- JWT_PASSPHRASE=change-me (servira à générer les clés JWT)
- TRUSTED_PROXIES=127.0.0.1,REMOTE_ADDR (si reverse proxy)
- TRUSTED_HOSTS='^(votre-domaine\.tld|www\.votre-domaine\.tld)$'
- SENTRY_DSN= (optionnel)
- MAILER_DSN=smtp://user:pass@smtp.provider.tld:587?encryption=tls
- MAILER_FROM=contact@votre-domaine.tld
- CONTACT_RECIPIENT=contact@votre-domaine.tld
- APP_FRONTEND_URL=https://votre-domaine.tld

2) JWT
- Générer les clés:
  php bin/console lexik:jwt:generate-keypair --overwrite

3) HTTPS / reverse proxy
- Terminer TLS au reverse proxy (Nginx/Traefik) et transmettre X-Forwarded-*.
- Définir TRUSTED_PROXIES / TRUSTED_HOSTS comme ci‑dessus.
- Activer HSTS côté proxy (ex: Strict-Transport-Security: max-age=31536000; includeSubDomains; preload).

4) Base de données
- Créer un utilisateur dédié avec mot de passe fort (pas de root en prod).
- Sauvegardes: voir tools/backup_db.sh et planifier un cron quotidien + rétention.

5) CORS
- Définir CORS_ALLOW_ORIGIN sur votre domaine exact (regex), pas de joker global.

6) Emails
- Configurer MAILER_DSN avec un SMTP professionnel (SPF/DKIM/DMARC configurés dans DNS).

7) Logs / Observabilité
- Configurer SENTRY_DSN.
- Conserver les logs applicatifs et du reverse proxy.

8) Sécurité applicative
- Mettre à jour régulièrement dépendances Composer/NPM.
- Forcer les secrets hors dépôt (.env.local / secrets Symfony).

