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
- Ne jamais utiliser `*`, `0.0.0.0/0` ou `::/0` dans `TRUSTED_PROXIES`.
- Si vous êtes derrière Cloudflare, Traefik, Nginx ou un load balancer, renseigner uniquement les IP/CIDR réellement maîtrisés par l’infra ou utiliser `REMOTE_ADDR` quand le proxy local termine TLS puis relaie vers PHP.
- Exemples acceptables: `TRUSTED_PROXIES=127.0.0.1,REMOTE_ADDR`, `TRUSTED_PROXIES=10.0.0.0/8,127.0.0.1`, `TRUSTED_PROXIES=172.18.0.0/16`.
- Après déploiement, vérifier que les logs applicatifs voient la bonne IP cliente et que le rate limiting ne retombe pas systématiquement sur l’IP du reverse proxy.
- Activer HSTS côté proxy (ex: Strict-Transport-Security: max-age=31536000; includeSubDomains; preload).
- Vérifier que `/api/*` renvoie les en-têtes de sécurité: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `Content-Security-Policy`.
- Servir aussi le frontend avec des en-têtes de sécurité. Le fichier `frontend/public/_headers` fournit une base compatible avec les hébergeurs statiques qui le supportent. Si Nginx/Apache sert le frontend, reporter ces mêmes valeurs dans la configuration du virtual host.
- Pour Nginx, inclure `deploy/nginx/frontend-security-headers.conf` dans le server block HTTPS qui sert `frontend/dist`, puis vérifier avec `curl -I https://hociatec.fr/` que `Content-Security-Policy` et `Strict-Transport-Security` sont présents sur la réponse HTML.
- Vérifier que la CSP frontend autorise seulement les origines nécessaires: site, API `https://api.hociatec.fr`, images, et Stripe Checkout si le paiement redirigé est actif.

5) Base de données
- Créer un utilisateur dédié avec mot de passe fort (pas de root en prod).
- Sauvegardes: voir tools/backup_db.sh et planifier un cron quotidien + rétention.

5bis) Documents sensibles (RIB / justificatifs)
- Les RIB et justificatifs de reprise sont stockés sous `var/private/trade-ins`, hors `public/`.
- Ne pas se limiter au `chmod 0600`: le volume ou disque qui porte `var/private` doit être chiffré au repos côté infrastructure (LUKS, volume cloud chiffré, bucket chiffré ou équivalent).
- L’antivirus ClamAV doit être disponible sur l’hôte avant mise en production; le contrôle `tools/production_check.sh` échoue sinon.
- Les téléchargements admin de documents trade-in doivent rester journalisés avec l’identifiant de l’admin, le document demandé et la référence métier.
- Exécuter régulièrement `APP_ENV=prod php bin/console app:trade-in:purge-private-documents --retention-days=180` pour supprimer les RIB et justificatifs devenus inutiles.

6) CORS
- Définir CORS_ALLOW_ORIGIN sur votre domaine exact (regex), pas de joker global.
- Pour Hociatec, la valeur attendue est `^https://(www\.)?hociatec\.fr$`.
- L'authentification utilise des cookies `HttpOnly`; conserver `allow_credentials: true` et vérifier que le reverse proxy renvoie un `Access-Control-Allow-Origin` explicite, jamais `*`.
- Après déploiement, vérifier que `Access-Control-Allow-Headers` ne contient plus `authorization` lorsque l'authentification par cookies HttpOnly est active.
- Vérifier dans le navigateur que les cookies `hociatec_access` et `hociatec_refresh` sont `HttpOnly`, `Secure` en production, `SameSite=Lax`, et limités aux chemins `/api` et `/api/auth`.

6bis) Politique de session / tokens
- Le cookie d’accès est émis pour `/api` avec une durée de vie d’environ `1 heure`.
- Le refresh token a une durée de vie de `30 jours` et le backend conserve au plus `10` sessions actives par utilisateur.
- Lors d’une rotation réussie, l’ancien refresh token devient inutilisable. Sa réutilisation ultérieure doit échouer sans réauthentifier l’utilisateur.
- Le comportement actuel n’effectue pas de révocation globale sur simple réutilisation d’un ancien token déjà rotaté; seules les sessions explicitement révoquées ou dépassant la limite active sont coupées.
- Une réinitialisation de mot de passe, un changement de mot de passe depuis le profil ou une suppression de compte révoque toutes les sessions refresh encore actives de l’utilisateur.
- En cas de compromission ou d’incident support, exécuter `APP_ENV=prod php bin/console app:auth:revoke-user-refresh-tokens user@example.com`.

7) Emails
- Configurer `MAILER_DSN` avec un transport professionnel et un expéditeur autorisé par le fournisseur (SPF/DKIM/DMARC configurés dans DNS).
- Brevo API:
  `MAILER_DSN=brevo+api://CLE_API_ACTIVE@default`
- Brevo SMTP:
  `MAILER_DSN=brevo+smtp://LOGIN_SMTP:MOT_DE_PASSE_SMTP@default`
- Après modification de `.env.prod.local`, régénérer l'environnement compilé et vider le cache:
  `composer dump-env prod`
  `APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear`
- Tester le transport hors Messenger:
  `APP_ENV=prod php bin/console mailer:test contact@votre-domaine.tld --from=contact@votre-domaine.tld`
- Si Brevo répond `API Key is not enabled (code 401)`, la clé configurée n'est pas active pour l'envoi transactionnel: créer ou réactiver une clé API transactionnelle Brevo, puis refaire `composer dump-env prod`.

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

11) Rotation des secrets
- Toujours préparer la rotation dans cet ordre: ajouter la nouvelle valeur, recharger la configuration, vérifier les dépendances, puis révoquer l’ancienne valeur.
- `APP_SECRET`: planifier une fenêtre de maintenance légère. La rotation invalide les jetons CSRF et peut casser des sessions/outils dépendants du secret Symfony; purger le cache applicatif juste après la bascule.
- JWT: générer une nouvelle paire et une nouvelle `JWT_PASSPHRASE`, déployer les nouvelles clés sur tous les nœuds, puis redémarrer PHP-FPM/workers. Les JWT déjà émis deviennent invalides si la clé privée/publique change; prévenir une reconnexion utilisateur.
- Stripe: créer d’abord les nouveaux secrets (`STRIPE_SECRET_KEY`, `STRIPE_WEBHOOK_SECRET`, `STRIPE_REFUND_WEBHOOK_SECRET`) dans le dashboard Stripe, mettre à jour l’environnement du backend, redéployer, puis faire un test webhook avant de supprimer les anciens secrets.
- Base de données: créer d’abord un nouvel utilisateur applicatif avec les mêmes droits minimaux, mettre à jour `DATABASE_URL`, valider les connexions, puis seulement retirer l’ancien compte.
- Mailer/SMTP/API email: créer une nouvelle clé ou un nouveau mot de passe applicatif, mettre à jour `MAILER_DSN`, tester `mailer:test`, puis révoquer l’ancien identifiant.
- Après chaque rotation: exécuter `composer dump-env prod`, `APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear`, redémarrer les workers Messenger et vérifier `tools/production_check.sh`.

