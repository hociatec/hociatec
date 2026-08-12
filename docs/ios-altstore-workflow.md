# iOS AltStore workflow

## Objectif

Un `push` sur `main` ou `master` dans `app_iphone/` doit :

1. lancer les tests iOS natifs ;
2. compiler une IPA non signee compatible AltStore ;
3. publier l'IPA versionnee dans `hociatec/hociatec-downloads` ;
4. publier aussi `ios-latest/hociatec-altstore-latest.ipa` ;
5. mettre a jour `ios-latest/hociatec-altstore-source.json`.

Le site peut ensuite telecharger directement :

- `https://github.com/hociatec/hociatec-downloads/releases/download/ios-latest/hociatec-altstore-latest.ipa`
- `https://github.com/hociatec/hociatec-downloads/releases/download/ios-latest/hociatec-altstore-source.json`

## Workflows concernes

- `.github/workflows/mobile-quality.yml`
- `.github/workflows/publish-ios-download.yml`

## Configuration GitHub requise

Le depot principal doit contenir un secret Actions :

- `DOWNLOADS_REPO_TOKEN`

Ce token doit avoir au minimum les droits suivants sur `hociatec/hociatec-downloads` :

- `contents: write`

## Convention de version

La CI publie les artefacts a partir de ces valeurs Xcode :

- `MARKETING_VERSION`
- `CURRENT_PROJECT_VERSION`

Exemple :

- `hociatec-altstore-v1.0-b1.ipa`
- release publique `ios-v1.0-b1`
