import SwiftUI

struct CGVLegalView: View {
    var body: some View {
        LegalTextView(
            title: "CGV",
            updatedAt: "20 juillet 2026",
            sections: [
                LegalSection(title: "Champ d'application", paragraphs: [
                    "Les CGV s'appliquent aux ventes de produits, locations de matériel, devis et prestations de services proposés par Hociatec via le site, par devis ou par tout autre canal commercial.",
                    "Toute commande implique l'acceptation des CGV, éventuellement complétées par des conditions particulières figurant sur un devis, une facture ou un contrat."
                ]),
                LegalSection(title: "Identification du vendeur", paragraphs: [
                    "Hociatec est une SARL au capital de 1 000 €, immatriculée au RCS de Nanterre sous le numéro SIREN 934 814 559.",
                    "Siège social : 2 allée Anatoli Vaisser, 92600 Asnières-sur-Seine, France. Contact commercial et service client : contact@hociatec.fr."
                ]),
                LegalSection(title: "Produits, services et prix", paragraphs: [
                    "Hociatec propose la vente et la location de matériel informatique, l'accompagnement technique, les audits, formations et prestations associées.",
                    "Les prix sont indiqués en euros et peuvent évoluer. Le prix applicable est celui affiché ou accepté au moment de la validation de la commande ou du devis."
                ]),
                LegalSection(title: "Commande, paiement et livraison", paragraphs: [
                    "La commande devient ferme après confirmation et, le cas échéant, après paiement effectif. Hociatec peut refuser ou annuler une commande anormale, frauduleuse ou incomplète.",
                    "Les paiements en ligne peuvent être traités par des prestataires sécurisés, notamment Stripe. Les délais de livraison ou d'intervention sont communiqués à titre indicatif sauf engagement écrit contraire."
                ]),
                LegalSection(title: "Garanties, rétractation et litiges", paragraphs: [
                    "Le consommateur bénéficie, lorsque la loi le prévoit, d'un droit de rétractation de 14 jours hors exceptions légales, ainsi que des garanties légales applicables.",
                    "En cas de question ou de litige, le client peut contacter Hociatec à l'adresse contact@hociatec.fr."
                ])
            ]
        )
    }
}
