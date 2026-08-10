# Hociatec Mobile

Base Flutter de l'application mobile Hociatec, créée pour fournir une fondation propre, modulaire et prête à évoluer avec l'API Symfony existante.

## Principes

- séparation claire entre `app`, `core`, `features` et `shared`
- navigation déclarative via `go_router`
- état et injection via `flutter_riverpod`
- client HTTP centralisé via `dio`
- configuration runtime par `--dart-define`, sans secret versionné
- workflow iOS GitHub Actions capable de compiler sans signature Apple

## Arborescence

```text
lib/
  app/        point d'entrée, bootstrap, routing, thème
  core/       configuration, réseau, abstractions transverses
  features/   modules métier isolés par domaine fonctionnel
  shared/     composants UI et primitives réutilisables
```

## Intégration future avec Symfony

- définir `API_BASE_URL` par environnement avec `--dart-define`
- brancher les services métier dans `features/*/data`
- centraliser l'authentification, les cookies et le flux CSRF Symfony dans `core/network`
- garder les écrans et l'état indépendants du transport HTTP

## Commandes utiles

```bash
flutter pub get
flutter analyze
flutter test
flutter run
flutter run --dart-define=API_BASE_URL=http://localhost:8000
```

Par defaut, l'application mobile pointe vers `https://api.hociatec.fr`.
Utiliser `--dart-define=API_BASE_URL=...` uniquement pour forcer un environnement local ou de preproduction.

## iOS sans signature

Le workflow GitHub Actions macOS vérifie la base Flutter, exécute les tests et compile iOS sans certificat Apple stocké dans le dépôt.

Date de préparation de cette base : 10 août 2026.
