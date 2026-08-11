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
- `GET /api/health/liveness` ne vérifie pas la base de données et confirme seulement que le process HTTP répond.
- `GET /api/health/readiness` (ainsi que l’alias `GET /api/health`) vérifie la disponibilité minimale du service, notamment la base, sans divulguer d’informations sensibles.
- Vérifier aussi que `GET /api/health/liveness` reste `200` même si une dépendance externe est momentanément indisponible, alors que `GET /api/health/readiness` peut passer en `503`.

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
- `BACKUP_ENCRYPTION_KEY_FILE` doit pointer vers un fichier distinct du dossier `var/backups`; la clé séparée du backup ne doit jamais voyager avec l’archive chiffrée.
- Vérifier que les sauvegardes produites restent chiffrées, avec permissions minimales, et que la rotation journalière/hebdomadaire/mensuelle respecte la politique de rétention décidée côté exploitation.

5ter) Restauration de backup
- Une restauration complète doit être testée régulièrement; un backup non restaurable n’a aucune valeur opérationnelle.
- Procédure minimale: copier une archive `.sql.gz.enc`, la déchiffrer avec la clé `BACKUP_ENCRYPTION_KEY_FILE`, la décompresser, puis restaurer dans une base ou table de restauration isolée avant toute utilisation en production.
- Exemple de vérification hors production: déchiffrer l’archive, confirmer qu’elle contient bien du SQL MySQL attendu, puis importer dans une base temporaire dédiée au drill de restauration.
- Conserver la preuve du dernier exercice de restauration complète avec date, opérateur, archive testée et résultat.
- Run a monthly restore drill et rejouer périodiquement la procédure sur une infra de préproduction ou une base jetable.

5bis) Documents sensibles (RIB / justificatifs)
- Les RIB et justificatifs de reprise sont stockés sous `var/private/trade-ins`, hors `public/`.
- Ne pas se limiter au `chmod 0600`: le volume ou disque qui porte `var/private` doit être chiffré au repos côté infrastructure (LUKS, volume cloud chiffré, bucket chiffré ou équivalent).
- L’antivirus ClamAV doit être disponible sur l’hôte avant mise en production; le contrôle `tools/production_check.sh` échoue sinon.
- Les téléchargements admin de documents trade-in doivent rester journalisés avec l’identifiant de l’admin, le document demandé et la référence métier.
- Exécuter régulièrement `APP_ENV=prod php bin/console app:trade-in:purge-private-documents --retention-days=180` pour supprimer les RIB et justificatifs devenus inutiles.

5quater) Limitation du périmètre système
- Le process PHP-FPM/CLI et les workers ne doivent pouvoir écrire que dans `var/`, le dossier de backups et les stockages privés explicitement utilisés par l’application.
- Le worker Messenger fourni tourne avec `User=www-data` et `Group=www-data`; conserver ce principe de moindre privilège pour chaque worker/cron supplémentaire.
- Éviter tout montage large du dépôt en écriture lorsque l’infrastructure permet une séparation plus fine.
- En cas de quota disque ou volume plein, les uploads privés, les backups et les écritures transitoires doivent échouer sans laisser de fichier partiel exploitable.
- Si le volume portant `var/` ou la base supportant l’outbox n’est plus inscriptible, traiter l’événement comme un incident critique: les réponses API doivent rester normalisées, les fichiers temporaires doivent être nettoyés, et la reprise passe par libération d’espace puis contrôle de `readiness`, des logs et des files Messenger.

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
- En cas d’incident majeur impactant potentiellement plusieurs comptes, exécuter `APP_ENV=prod php bin/console app:auth:revoke-all-refresh-tokens --confirm`.

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
- Vérifier côté PHP-FPM/CLI que `expose_php=0` et côté reverse proxy que les réponses ne divulguent ni version PHP, ni version Symfony, ni stack traces.

11) Documentation API
- Le contrat OpenAPI est disponible dans `docs/openapi.yaml`.
- Publier ce fichier avec la documentation interne sans exposer les secrets de production.

12) Migrations
- La migration de suivi des webhooks Stripe doit être exécutée avant le déploiement du code correspondant:
  `APP_ENV=prod php bin/console doctrine:migrations:migrate -n`.
- Vérifier `doctrine:migrations:status` après chaque déploiement.

12bis) Déploiement applicatif
- En production, installer les dépendances backend avec `composer install --no-dev --classmap-authoritative --no-interaction --prefer-dist`.
- Générer l’environnement compilé avec `composer dump-env prod`.
- Chauffer le cache avant remise en service avec `APP_ENV=prod APP_DEBUG=0 php bin/console cache:warmup`.
- Ne jamais remettre le trafic avant validation des migrations, du cache et du worker Messenger.

12ter) Rollback
- Conserver l’artefact applicatif précédent et la version exacte du lockfile déployé pour permettre un retour arrière rapide.
- Si le rollback implique une migration non rétrocompatible, prévoir une stratégie explicite avant déploiement: migration réversible, phase de compatibilité ou restauration contrôlée.
- Après rollback, relancer `cache:warmup`, vérifier `GET /api/health/readiness`, puis contrôler l’état des files Messenger et des logs 5xx.

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

11bis) Incident response
- En cas de compromission JWT, régénérer la paire de clés, redéployer, invalider les sessions refresh concernées et surveiller les tentatives de réauthentification anormales.
- En cas de webhook Stripe compromis, faire tourner immédiatement `STRIPE_WEBHOOK_SECRET` et `STRIPE_REFUND_WEBHOOK_SECRET`, vérifier les derniers événements persistés et isoler les remboursements ou commandes douteuses.
- En cas de fuite de base ou de fichier sensible, révoquer les sessions concernées, préserver les logs avec `X-Request-Id`, lancer la rotation des secrets et exécuter la procédure de restauration/forensics adaptée.

12) Décisions de sécurité à conserver
- Cookies d’authentification: `HttpOnly`, `SameSite=Lax`, `Secure` en production, avec chemins limités à `/api` et `/api/auth` pour réduire la surface de fuite.
- Le refresh token est rotaté à chaque usage valide; la réutilisation d’un ancien token rotaté doit échouer.
- Les réponses d’erreur API en production sont normalisées pour éviter l’exposition de SQL, chemins locaux, variables d’environnement ou stack traces.
- Les endpoints d’observabilité publics restent minimaux: `liveness`, `readiness` et métriques protégées par localhost ou `X-Metrics-Token`.

13) Revue continue du socle
- Planifier une revue périodique du code mort, des modules peu utilisés, des commandes historiques et des points de complexité transverses sur l’ensemble du dépôt.
- Les prochains gains doivent prioritairement venir des garanties automatiques: tests, quality gates, observabilité, durcissement de configuration et simplification, plutôt que de nouvelles couches d’abstraction.

14) Maintenance des dépendances
- Le dépôt conserve `composer.lock` pour figer exactement les versions déployées.
- Vérifier régulièrement `composer audit --locked` et traiter sans délai toute alerte de sécurité sur l’ensemble verrouillé.
- L’état de support vérifié le `11 août 2026` est documenté dans `docs/dependency-support-status-2026-08-11.md`.
- Tant que la plateforme reste sur Symfony `7.4`, le runtime PHP doit au minimum rester sur une branche officiellement supportée et recevoir rapidement ses patchs de sécurité mineurs.

