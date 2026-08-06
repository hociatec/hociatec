# Décision 0001: Doctrine Dans Domain

Les entités du domaine peuvent porter les attributs Doctrine et Validator.

Ce compromis garde le backend Symfony simple et évite une couche de mapping séparée lourde. En contrepartie, le domaine ne doit pas importer de services techniques, repositories, query builders, request/response HTTP ou filesystem.

