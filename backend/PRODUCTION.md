Production hardening checklist (Hociatec)

1) Environment variables (.env.prod.local)
- APP_ENV=prod
- APP_DEBUG=0
- APP_SECRET=change-me (long, aléatoire)
- CORS_ALLOW_ORIGIN='^https://(www\.)?votre-domaine\.tld$'
- DEFAULT_URI=https://votre-domaine.tld
- DATABASE_URL=mysql://user:motdepasse@db-host:3306/hociatec?serverVersion=8.0&charset=utf8mb4
- JWT_PASSPHRASE=change-me (servira à générer les clés JWT)
- TRUSTED_PROXIES=127.0.0.1,REMOTE_ADDR (si reverse proxy)
- TRUSTED_HOSTS='^(votre-domaine\.tld|www\.votre-domaine\.tld)$'
- MAILER_DSN=smtp://user:pass@smtp.provider.tld:587?encryption=tls
- MAILER_FROM=contact@votre-domaine.tld
- CONTACT_RECIPIENT=contact@votre-domaine.tld
- APP_FRONTEND_URL=https://votre-domaine.tld
- STRIPE_SECRET_KEY=stripe-live-secret-key
- STRIPE_WEBHOOK_SECRET=stripe-webhook-secret
- STRIPE_REFUND_WEBHOOK_SECRET=stripe-refund-webhook-secret
- MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
- MESSENGER_FAILURE_TRANSPORT_DSN=doctrine://default?queue_name=failed&auto_setup=0
2) JWT
- Générer les clés:
  php bin/console lexik:jwt:generate-keypair --overwrite

3) Validation avant mise en production
- Depuis la racine du projet:
  tools/production_check.sh
- Le script vérifie les variables critiques, les clés JWT, Composer, les audits de sécurité, le container Symfony, Doctrine, Messenger et le build frontend.
- Pour une recette technique sans exécuter la suite de tests:
  tools/production_check.sh --skip-tests
- Le contrôle échoue volontairement si une variable de production contient encore une valeur de test ou placeholder (`root`, `sk_test`, `change-me`, etc.). Une base locale au serveur est acceptée si elle utilise un utilisateur applicatif dédié.
- Le endpoint `GET /api/health` doit répondre `200` avec `{"status":"ok"}` après déploiement.

4) HTTPS / reverse proxy
- Terminer TLS au reverse proxy (Nginx/Traefik) et transmettre X-Forwarded-*.
- Définir TRUSTED_PROXIES / TRUSTED_HOSTS comme ci‑dessus.
- Activer HSTS côté proxy (ex: Strict-Transport-Security: max-age=31536000; includeSubDomains; preload).
- Vérifier que `/api/*` renvoie les en-têtes de sécurité: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `Content-Security-Policy`.
- Servir aussi le frontend avec des en-têtes de sécurité. Le fichier `frontend/public/_headers` fournit une base compatible avec les hébergeurs statiques qui le supportent. Si Nginx/Apache sert le frontend, reporter ces mêmes valeurs dans la configuration du virtual host.
- Pour Nginx, inclure `deploy/nginx/frontend-security-headers.conf` dans le server block HTTPS qui sert `frontend/dist`, puis vérifier avec `curl -I https://hociatec.fr/` que `Content-Security-Policy` et `Strict-Transport-Security` sont présents sur la réponse HTML.
- Vérifier que la CSP frontend autorise seulement les origines nécessaires: site, API `https://api.hociatec.fr`, images, et Stripe Checkout si le paiement redirigé est actif.

5) Base de données
- Créer un utilisateur dédié avec mot de passe fort (pas de root en prod).
- Sauvegardes: voir tools/backup_db.sh et planifier un cron quotidien + rétention.

6) CORS
- Définir CORS_ALLOW_ORIGIN sur votre domaine exact (regex), pas de joker global.
- Pour Hociatec, la valeur attendue est `^https://(www\.)?hociatec\.fr$`.
- L'authentification utilise des cookies `HttpOnly`; conserver `allow_credentials: true` et vérifier que le reverse proxy renvoie un `Access-Control-Allow-Origin` explicite, jamais `*`.
- Après déploiement, vérifier que `Access-Control-Allow-Headers` ne contient plus `authorization` lorsque l'authentification par cookies HttpOnly est active.
- Vérifier dans le navigateur que les cookies `hociatec_access` et `hociatec_refresh` sont `HttpOnly`, `Secure` en production, `SameSite=Lax`, et limités aux chemins `/api` et `/api/auth`.

7) Emails
- Configurer MAILER_DSN avec un SMTP professionnel (SPF/DKIM/DMARC configurés dans DNS).

8) Messenger
- Installer le worker systemd depuis deploy/systemd/hociatec-messenger.service:
  sudo cp ../deploy/systemd/hociatec-messenger.service /etc/systemd/system/
  sudo systemctl daemon-reload
  sudo systemctl enable --now hociatec-messenger
- Vérifier les files:
  APP_ENV=prod php bin/console messenger:stats
  APP_ENV=prod php bin/console messenger:failed:show --stats
- Relancer les messages échoués après correction:
  APP_ENV=prod php bin/console messenger:failed:retry

9) Logs
- Conserver les logs applicatifs et du reverse proxy.
- Les logs JSON contiennent `X-Request-Id`; conserver cet identifiant lors des incidents.
- Surveiller les erreurs 5xx, les échecs Stripe, les échecs d’envoi email et la file Messenger.

11) Documentation API
- Le contrat OpenAPI est disponible dans `docs/openapi.yaml`.
- Publier ce fichier avec la documentation interne sans exposer les secrets de production.

12) Migrations
- La migration de suivi des webhooks Stripe doit être exécutée avant le déploiement du code correspondant:
  `APP_ENV=prod php bin/console doctrine:migrations:migrate -n`.
- Vérifier `doctrine:migrations:status` après chaque déploiement.

10) Sécurité applicative
- Mettre à jour régulièrement dépendances Composer/NPM.
- Forcer les secrets hors dépôt (.env.local / secrets Symfony).

