# iPhone App Architecture

## Target pattern

- Architecture stricte `MVVM + Service Layer`.
- `View`: rendu, navigation, bindings UI uniquement.
- `ViewModel`: état, orchestration, validation, pagination, règles d’interaction.
- `Service`: accès backend, encapsulation des appels `APIClient`, contrats métier injectables.
- `APIClient`: couche HTTP bas niveau, non appelée directement depuis les `View`.

## Principles

- `Features/` regroupe les écrans et états par domaine métier.
- `Core/` regroupe les briques techniques globales (`API`, conteneur d’app, session).
- `Shared/` contient les composants transverses réutilisables.
- Le point d’entrée UI doit rester mince et ne contenir que la composition de navigation.
- Les appels backend passent par la `Service Layer`, jamais directement depuis les `View`.
- Les `ViewModel` portent l’orchestration d’état; les `View` restent centrées sur le rendu et l’interaction.

## Current layout

- `Features/<Module>/Views`: écrans SwiftUI du module.
- `Features/<Module>/ViewModels`: état et orchestration du module.
- `Features/<Module>/Support`: helpers de présentation, formatters et types locaux non-UI.
- `Features/<Module>/Components`: composants locaux si un module grossit.
- `Features/Root`: navigation racine et composition d’onglets.
- `Features/Home`: accueil et chargement de ses données.
- `Features/Products`: catalogue produits et navigation produit.
- `Features/Reviews`: avis, avis en attente et soumission d’avis.
- `Features/Services`: catalogue services et fiches service.
- `Features/News`: liste, détail et commentaires d’actualités.
- `Features/Training`: catalogue et détail des formations.
- `Features/Offer`: hub de navigation commerciale.
- `Features/Account`: compte, authentification et profil.
- `Features/Addresses`: gestion des adresses utilisateur.
- `Features/Cart`: panier.
- `Features/Appointments`: réservation et historique des rendez-vous.
- `Features/Quotes`: devis.
- `Features/TradeIn`: reprise de matériel.
- `Features/Orders`: commandes.
- `Features/Favorites`: favoris.
- `Core/API`: client réseau et modèles alignés backend.
- `Core/App`: composition applicative et dépendances partagées.
- `Core/Services`: service layer injectée dans les `ViewModel`.
- `Core/Session`: persistance de session utilisateur.
- `Shared/Support`: utilitaires transverses et compatibilité SwiftUI.
- `Shared/UI`: composants UI transverses.

## Remaining conventions

- Scinder les fichiers qui mélangent encore plusieurs responsabilités internes lorsqu’un module devient plus dense.
- Préférer plusieurs fichiers petits par feature plutôt qu’un fichier unique qui mélange vues, état et helpers.
- Ajouter une stratégie de tests unitaires ciblant chaque `ViewModel`.
- Garder à la racine uniquement `hociatec_iphoneApp.swift`, qui reste le point d’entrée naturel de l’application.
