import SwiftUI

struct LegalMentionsView: View {
    var body: some View {
        LegalTextView(
            title: "Mentions légales",
            updatedAt: "20 juillet 2026",
            sections: [
                LegalSection(title: "Éditeur du site", paragraphs: [
                    "Le site \(AppConfig.websiteHost) est édité par Hociatec, société à responsabilité limitée au capital social de 1 000 €.",
                    "Siège social : \(AppConfig.companyPostalAddress). SIREN : 934 814 559. SIRET : 934 814 559 00019. TVA intracommunautaire : FR59 934 814 559. RCS : Nanterre. Code APE : 4791B."
                ]),
                LegalSection(title: "Activité et direction", paragraphs: [
                    "Hociatec exerce notamment dans la vente et la location de matériel informatique, la conception, le développement et la maintenance de solutions numériques, le conseil, l'assistance et les audits.",
                    "La direction de la publication est assurée par Hacene Sahraoui et Hocine Sahraoui, cogérants."
                ]),
                LegalSection(title: "Hébergement", paragraphs: [
                    "Le site est hébergé par OVH SAS (OVHcloud), 2 rue Kellermann, 59100 Roubaix, France.",
                    "Site web de l'hébergeur : ovhcloud.com."
                ]),
                LegalSection(title: "Propriété intellectuelle", paragraphs: [
                    "Les contenus, interfaces, graphismes, logos, photographies, vidéos, bases de données et codes sources du site sont protégés par le droit de la propriété intellectuelle.",
                    "Toute reproduction ou exploitation sans autorisation écrite préalable est interdite, sauf exception légale."
                ]),
                LegalSection(title: "Responsabilité et contact", paragraphs: [
                    "Hociatec s'efforce de fournir des informations exactes et à jour, mais des erreurs ou indisponibilités temporaires peuvent survenir.",
                    "Pour toute question relative au site ou pour signaler un contenu, vous pouvez écrire à \(AppConfig.contactEmail)."
                ])
            ]
        )
    }
}
