import SwiftUI

struct CGULegalView: View {
    var body: some View {
        LegalTextView(
            title: "CGU",
            updatedAt: "20 juillet 2026",
            sections: [
                LegalSection(title: "Objet", paragraphs: [
                    "Les CGU encadrent l'accès et l'utilisation du site \(AppConfig.websiteHost), du compte client, des formulaires, des espaces de suivi et des fonctionnalités proposées par Hociatec.",
                    "L'utilisation du site implique l'acceptation des présentes conditions. Les ventes, locations et prestations restent également soumises aux CGV applicables."
                ]),
                LegalSection(title: "Accès au site", paragraphs: [
                    "Le site est en principe accessible 24h/24 et 7j/7, hors maintenance, évolution technique, force majeure ou incident indépendant de la volonté de Hociatec.",
                    "Hociatec peut faire évoluer, suspendre ou supprimer tout ou partie du site pour des raisons techniques, de sécurité ou de conformité."
                ]),
                LegalSection(title: "Compte utilisateur", paragraphs: [
                    "Certaines fonctionnalités nécessitent un compte. L'utilisateur doit fournir des informations exactes, à jour et ne pas usurper l'identité d'un tiers.",
                    "L'utilisateur est responsable de la confidentialité de ses identifiants."
                ]),
                LegalSection(title: "Sécurité et usages interdits", paragraphs: [
                    "Toute tentative d'accès frauduleux, de perturbation, d'extraction massive de contenus, d'envoi de contenus malveillants ou de contournement des mesures de sécurité est interdite.",
                    "Toute anomalie ou suspicion d'accès non autorisé peut être signalée à \(AppConfig.contactEmail)."
                ]),
                LegalSection(title: "Responsabilité et contact", paragraphs: [
                    "Hociatec s'efforce de publier des informations exactes et actualisées, sans que cela constitue à lui seul un engagement contractuel hors validation expresse.",
                    "Pour toute question relative aux CGU, vous pouvez écrire à \(AppConfig.contactEmail)."
                ])
            ]
        )
    }
}
