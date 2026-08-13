import SwiftUI

struct PrivacyLegalView: View {
    var body: some View {
        LegalTextView(
            title: "Confidentialité",
            updatedAt: "26 juillet 2026",
            sections: [
                LegalSection(title: "Responsable de traitement", paragraphs: [
                    "Le responsable des traitements de données personnelles réalisés via hociatec.fr est Hociatec, SARL immatriculée au RCS de Nanterre sous le numéro SIREN 934 814 559.",
                    "Siège social : 2 allée Anatoli Vaisser, 92600 Asnières-sur-Seine, France. Contact : contact@hociatec.fr."
                ]),
                LegalSection(title: "Données collectées", paragraphs: [
                    "Selon les fonctionnalités utilisées, Hociatec peut collecter des données d'identification, de contact, de compte, de commande, de devis, de paiement, de livraison, de rendez-vous, de support et des données techniques strictement nécessaires.",
                    "Des données de consentement, et dans certains cas des données liées au programme bêta, peuvent également être collectées."
                ]),
                LegalSection(title: "Finalités", paragraphs: [
                    "Les données servent notamment à la gestion du compte client, des commandes, devis, locations, paiements, livraisons, demandes de contact, support, rendez-vous, sécurité et obligations légales.",
                    "Certaines communications commerciales reposent sur le consentement ou l'intérêt légitime selon le cas."
                ]),
                LegalSection(title: "Conservation et sécurité", paragraphs: [
                    "Les données sont conservées pendant des durées proportionnées aux finalités : compte client, facturation, support, sécurité, ou obligations légales selon les cas.",
                    "Hociatec met en oeuvre des mesures techniques et organisationnelles pour protéger les données contre l'accès non autorisé, la perte, l'altération ou la divulgation."
                ]),
                LegalSection(title: "Vos droits", paragraphs: [
                    "Les personnes concernées disposent notamment de droits d'accès, de rectification, d'effacement, d'opposition, de limitation, de portabilité lorsque applicable, et du droit de retirer leur consentement.",
                    "Les demandes peuvent être adressées à contact@hociatec.fr. En cas de désaccord, une réclamation peut être adressée à la CNIL."
                ])
            ]
        )
    }
}
