# Décision 0004: Ressources Privées

Les ressources sensibles ne sont jamais manipulées directement par les cas d’usage via des objets HTTP.

`Application` dépend d’un port intentionnel. L’implémentation `Infrastructure` valide le fichier concret, écrit dans `var/private` si nécessaire et garantit que la lecture reste confinée au répertoire autorisé.

