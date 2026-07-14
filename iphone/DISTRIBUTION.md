# Distribution iPhone Hociatec

## Objectif

Sortir du mode `Expo Go` et préparer une vraie distribution iPhone native pour Hociatec.

## Base technique déjà en place

- `expo-dev-client` installé
- `eas.json` configuré
- scripts `start:dev-client` et `start:local:dev-client`
- application Expo Router avec onglets et premières données réelles via l'API Hociatec

## Commandes utiles

```bash
npm run start:dev-client
```

```bash
npm run eas:login
```

```bash
npm run eas:whoami
```

```bash
npm run eas:configure
```

```bash
npm run build:ios:development
```

```bash
npm run build:ios:preview
```

## Cible de diffusion

- iPhone
- cadre de distribution alternatif applicable en Union européenne
- page publique d'installation sur `hociatec.fr/iphone`

## Ce qu'il faudra pour la vraie mise à disposition

- un canal de distribution iPhone final conforme au cadre Apple/UE retenu
- la signature et la préparation du build iOS final
- une page publique Hociatec avec :
  - lien d'installation final
  - prérequis iOS
  - consignes utilisateur
  - support/contact

## Point d'attention

Le projet n'utilise plus `Expo Go` comme cible produit. `Expo Go` peut encore servir ponctuellement au debug local, mais la direction cible est un client natif Hociatec.

## Étape suivante concrète

1. Se connecter à Expo avec `npm run eas:login`
2. Vérifier le compte avec `npm run eas:whoami`
3. Générer le premier build iPhone de développement avec `npm run build:ios:development`
4. Récupérer ensuite le lien de build natif à publier ou à tester selon le canal choisi
