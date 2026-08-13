import SwiftUI

struct AboutHubView: View {
    let services: AppServices

    var body: some View {
        List {
            NavigationLink {
                ContactView(service: services.contact)
            } label: {
                Label("Contact", systemImage: "envelope")
            }

            NavigationLink {
                AboutStoryView()
            } label: {
                Label("Notre histoire", systemImage: "building.2")
            }

            NavigationLink {
                OpeningHoursView()
            } label: {
                Label("Horaires d'ouverture", systemImage: "clock")
            }

            NavigationLink {
                SocialLinksView()
            } label: {
                Label("Réseaux sociaux", systemImage: "network")
            }

            NavigationLink {
                LegalMenuView()
            } label: {
                Label("Informations légales", systemImage: "doc.text")
            }
        }
        .navigationTitle("À propos")
    }
}

private struct AboutStoryView: View {
    var body: some View {
        List {
            Section {
                Text("Hociatec accompagne la vente et la location de matériel informatique, les services numériques, l'assistance, les audits et la formation.")
                Text("L'objectif est de proposer un accompagnement concret, lisible et réactif, aussi bien pour les particuliers que pour les professionnels.")
                Text("L'activité couvre les interventions partout en France, avec une intervention sous 2 heures en Ile-de-France lorsque la situation le permet.")
            }
        }
        .navigationTitle("Notre histoire")
    }
}

private struct OpeningHoursView: View {
    private let entries: [(String, String)] = [
        ("Lundi", "09h00 - 20h00"),
        ("Mardi", "09h00 - 20h00"),
        ("Mercredi", "09h00 - 20h00"),
        ("Jeudi", "09h00 - 20h00"),
        ("Vendredi", "09h00 - 20h00"),
        ("Samedi", "09h00 - 17h00"),
        ("Dimanche", "Fermé")
    ]

    var body: some View {
        List {
            Section("Horaires d'ouverture") {
                ForEach(entries, id: \.0) { label, hours in
                    LabeledContent(label, value: hours)
                }
            }
        }
        .navigationTitle("Horaires")
    }
}

private struct SocialLinksView: View {
    private let links: [(String, String)] = [
        ("Facebook", "https://www.facebook.com/hociatec"),
        ("LinkedIn", "https://www.linkedin.com/company/hociatec"),
        ("TikTok", "https://www.tiktok.com/@hociatec"),
        ("X", "https://x.com/hociatec"),
        ("Instagram", "https://www.instagram.com/hociatec")
    ]

    var body: some View {
        List {
            Section("Réseaux sociaux") {
                ForEach(links, id: \.0) { label, href in
                    Link(destination: URL(string: href)!) {
                        HStack {
                            Text(label)
                            Spacer()
                            Image(systemName: "arrow.up.right.square")
                                .foregroundStyle(.secondary)
                        }
                    }
                }
            }
        }
        .navigationTitle("Réseaux sociaux")
    }
}

private struct LegalMenuView: View {
    var body: some View {
        List {
            NavigationLink("CGU") {
                LegalTextView(
                    title: "CGU",
                    updatedAt: "20 juillet 2026",
                    sections: [
                        LegalSection(title: "Objet", paragraphs: [
                            "Les CGU encadrent l'accès et l'utilisation du site hociatec.fr, du compte client, des formulaires, des espaces de suivi et des fonctionnalités proposées par Hociatec.",
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
                            "Toute anomalie ou suspicion d'accès non autorisé peut être signalée à contact@hociatec.fr."
                        ]),
                        LegalSection(title: "Responsabilité et contact", paragraphs: [
                            "Hociatec s'efforce de publier des informations exactes et actualisées, sans que cela constitue à lui seul un engagement contractuel hors validation expresse.",
                            "Pour toute question relative aux CGU, vous pouvez écrire à contact@hociatec.fr."
                        ])
                    ]
                )
            }

            NavigationLink("CGV") {
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

            NavigationLink("Confidentialité") {
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

            NavigationLink("Mentions légales") {
                LegalTextView(
                    title: "Mentions légales",
                    updatedAt: "20 juillet 2026",
                    sections: [
                        LegalSection(title: "Éditeur du site", paragraphs: [
                            "Le site hociatec.fr est édité par Hociatec, société à responsabilité limitée au capital social de 1 000 €.",
                            "Siège social : 2 allée Anatoli Vaisser, 92600 Asnières-sur-Seine, France. SIREN : 934 814 559. SIRET : 934 814 559 00019. TVA intracommunautaire : FR59 934 814 559. RCS : Nanterre. Code APE : 4791B."
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
                            "Pour toute question relative au site ou pour signaler un contenu, vous pouvez écrire à contact@hociatec.fr."
                        ])
                    ]
                )
            }
        }
        .navigationTitle("Informations légales")
    }
}

private struct LegalSection: Identifiable {
    let id = UUID()
    let title: String
    let paragraphs: [String]
}

private struct LegalTextView: View {
    let title: String
    let updatedAt: String
    let sections: [LegalSection]

    var body: some View {
        List {
            Section {
                LabeledContent("Dernière mise à jour", value: updatedAt)
            }

            ForEach(sections) { section in
                Section(section.title) {
                    ForEach(Array(section.paragraphs.enumerated()), id: \.offset) { _, paragraph in
                        Text(paragraph)
                    }
                }
            }
        }
        .navigationTitle(title)
    }
}
