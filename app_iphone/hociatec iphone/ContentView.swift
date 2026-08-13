import SwiftUI
import Foundation
import UniformTypeIdentifiers
#if canImport(UIKit)
import UIKit
#endif

struct ContentView: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var cart: CartViewModel
    @State private var selectedTab: Int = 0
    @State private var productFiltersBadge: Int? = nil
    @State private var bannerMessage: String? = nil
    @State private var bannerIsError: Bool = false

    var body: some View {
        ZStack(alignment: .top) {
            VStack(spacing: 0) {
                Group {
                    switch selectedTab {
                    case 0:
                        NavigationStack {
                            HomeScreen(api: container.api, selectedTab: $selectedTab)
                        }
                    case 1:
                        NavigationStack {
                            OfferHubView(api: container.api, selectedTab: $selectedTab, filtersBadge: $productFiltersBadge)
                        }
                    case 2:
                        NavigationStack {
                            CartScreen()
                        }
                    case 3:
                        NavigationStack {
                            NewsListView(api: container.api)
                        }
                    case 4:
                        NavigationStack {
                            AccountScreen()
                        }
                    case 5:
                        NavigationStack {
                            AboutHubView(api: container.api)
                        }
                    default:
                        NavigationStack {
                            HomeScreen(api: container.api, selectedTab: $selectedTab)
                        }
                    }
                }

                CustomTabBar(
                    selectedTab: $selectedTab,
                    cartCount: cart.cart?.totalQuantity ?? 0,
                    productFiltersBadge: productFiltersBadge
                )
            }
            if let message = bannerMessage {
                BannerView(message: message, isError: bannerIsError)
                    .transition(.move(edge: .top).combined(with: .opacity))
                    .padding(.top, 8)
            }
        }
        .task { await cart.refresh() }
        .animation(.spring(), value: bannerMessage)
        .onChangeCompat(container.cart.statusMessage) { newValue in
            guard let msg = newValue, !msg.isEmpty else { return }
            bannerIsError = false
            bannerMessage = msg
            DispatchQueue.main.asyncAfter(deadline: .now() + 2.5) {
                if bannerMessage == msg { bannerMessage = nil }
            }
        }
        .onChangeCompat(container.account.statusMessage) { newValue in
            guard let msg = newValue, !msg.isEmpty else { return }
            bannerIsError = false
            bannerMessage = msg
            DispatchQueue.main.asyncAfter(deadline: .now() + 2.5) {
                if bannerMessage == msg { bannerMessage = nil }
            }
        }
        .onChangeCompat(container.cart.error) { newValue in
            guard let msg = newValue, !msg.isEmpty else { return }
            bannerIsError = true
            bannerMessage = msg
            DispatchQueue.main.asyncAfter(deadline: .now() + 4.0) {
                if bannerMessage == msg { bannerMessage = nil }
            }
        }
        .onChangeCompat(container.account.error) { newValue in
            guard let msg = newValue, !msg.isEmpty else { return }
            bannerIsError = true
            bannerMessage = msg
            DispatchQueue.main.asyncAfter(deadline: .now() + 4.0) {
                if bannerMessage == msg { bannerMessage = nil }
            }
        }
    }
}

private struct CustomTabBar: View {
    @Binding var selectedTab: Int
    let cartCount: Int
    let productFiltersBadge: Int?

    var body: some View {
        HStack(spacing: 0) {
            CustomTabBarButton(
                title: "Accueil",
                systemImage: "house",
                isSelected: selectedTab == 0,
                badge: nil
            ) { selectedTab = 0 }
            CustomTabBarButton(
                title: "Offre",
                systemImage: "square.grid.2x2",
                isSelected: selectedTab == 1,
                badge: productFiltersBadge
            ) { selectedTab = 1 }
            CustomTabBarButton(
                title: "Panier",
                systemImage: "cart",
                isSelected: selectedTab == 2,
                badge: cartCount > 0 ? cartCount : nil
            ) { selectedTab = 2 }
            CustomTabBarButton(
                title: "Actus",
                systemImage: "newspaper",
                isSelected: selectedTab == 3,
                badge: nil
            ) { selectedTab = 3 }
            CustomTabBarButton(
                title: "Compte",
                systemImage: "person",
                isSelected: selectedTab == 4,
                badge: nil
            ) { selectedTab = 4 }
            CustomTabBarButton(
                title: "À propos",
                systemImage: "info.circle",
                isSelected: selectedTab == 5,
                badge: nil
            ) { selectedTab = 5 }
        }
        .padding(.horizontal, 4)
        .padding(.top, 8)
        .padding(.bottom, 12)
        .background(.ultraThinMaterial)
    }
}

private struct CustomTabBarButton: View {
    let title: String
    let systemImage: String
    let isSelected: Bool
    let badge: Int?
    let action: () -> Void

    var body: some View {
        Button(action: action) {
            VStack(spacing: 4) {
                ZStack(alignment: .topTrailing) {
                    Image(systemName: systemImage)
                        .font(.system(size: 18, weight: .semibold))
                    if let badge {
                        Text("\(badge)")
                            .font(.caption2.weight(.bold))
                            .foregroundStyle(.white)
                            .padding(.horizontal, 5)
                            .padding(.vertical, 1)
                            .background(Color.red)
                            .clipShape(Capsule())
                            .offset(x: 10, y: -8)
                    }
                }
                Text(title)
                    .font(.caption2)
                    .lineLimit(1)
            }
            .frame(maxWidth: .infinity)
            .foregroundStyle(isSelected ? Color.accentColor : Color.secondary)
            .padding(.vertical, 4)
        }
        .buttonStyle(.plain)
    }
}

private struct HomeScreen: View {
    @StateObject private var home: HomeViewModel
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var account: AccountViewModel
    @Binding private var selectedTab: Int

    init(api: APIClient, selectedTab: Binding<Int>) {
        _home = StateObject(wrappedValue: HomeViewModel(api: api))
        _selectedTab = selectedTab
    }

    var body: some View {
        List {
            Section {
                NavigationLink {
                    AppointmentBookingView(api: container.api)
                } label: {
                    Label("Rendez-vous", systemImage: "calendar.badge.plus")
                }

                NavigationLink {
                    TradeInRequestView(api: container.api, account: account)
                } label: {
                    Label("Reprise", systemImage: "arrow.triangle.2.circlepath")
                }

                NavigationLink {
                    QuoteRequestView(api: container.api, account: account)
                } label: {
                    Label("Devis", systemImage: "doc.badge.plus")
                }
            }

            Section("Services") {
                if home.isLoading && home.services.isEmpty {
                    ProgressView("Chargement...")
                        .frame(maxWidth: .infinity, alignment: .center)
                } else if let error = home.error, home.services.isEmpty {
                    Text(error)
                        .foregroundStyle(.red)
                } else if home.services.isEmpty {
                    Text("Aucun service mis en avant pour le moment.")
                        .foregroundStyle(.secondary)
                } else {
                    ForEach(home.services.prefix(6)) { service in
                        NavigationLink {
                            ServiceDetailView(api: container.api, serviceID: service.id)
                        } label: {
                            VStack(alignment: .leading, spacing: 6) {
                                Text(service.title)
                                    .fontWeight(.semibold)
                                if let description = service.description, !description.isEmpty {
                                    Text(description)
                                        .lineLimit(2)
                                        .foregroundStyle(.secondary)
                                }
                                HStack {
                                    Text(PriceFormatter.format(cents: service.priceCents))
                                        .font(.footnote)
                                        .fontWeight(.semibold)
                                    Spacer()
                                    if let durationLabel = service.durationLabel, !durationLabel.isEmpty {
                                        Text(durationLabel)
                                            .font(.footnote)
                                            .foregroundStyle(.secondary)
                                    }
                                }
                            }
                            .padding(.vertical, 4)
                        }
                    }
                }

                NavigationLink {
                    ServicesCatalogView(api: container.api)
                } label: {
                    HStack {
                        Label("Tous les services", systemImage: "wrench.and.screwdriver")
                            .fontWeight(.semibold)
                        Spacer()
                        Image(systemName: "chevron.right")
                            .foregroundStyle(.tertiary)
                    }
                }
            }

            Section("Produits en vedette") {
                if home.isLoading && home.featured.isEmpty {
                    ProgressView("Chargement...")
                        .frame(maxWidth: .infinity, alignment: .center)
                } else if let error = home.error {
                    Text(error)
                        .foregroundStyle(.red)
                } else if home.featured.isEmpty {
                    Text("Aucun produit disponible pour le moment.")
                        .foregroundStyle(.secondary)
                } else {
                    ForEach(home.featured.prefix(5)) { product in
                        NavigationLink {
                            // Navigate to detail
                            ProductDetailView(
                                product: product,
                                imageURL: container.api.assetURL(for: product.imageUrl),
                                cart: container.cart,
                                selectedTab: .constant(0)
                            )
                            .environmentObject(container)
                        } label: {
                            VStack(alignment: .leading, spacing: 4) {
                                Text(product.name)
                                    .fontWeight(.semibold)
                                Text(product.shortDescription)
                                    .lineLimit(2)
                                    .foregroundStyle(.secondary)
                            }
                            .accessibilityElement(children: .ignore)
                            .accessibilityLabel("Produit: \(product.name)")
                            .accessibilityHint("Afficher le détail du produit")
                        }
                    }
                }

                Button {
                    selectedTab = 1
                } label: {
                    HStack {
                        Label("Voir notre offre", systemImage: "square.grid.2x2")
                            .fontWeight(.semibold)
                        Spacer()
                        Image(systemName: "chevron.right")
                            .foregroundStyle(.tertiary)
                    }
                }
                .accessibilityHint("Ouvrir l’onglet Notre offre")
            }

            Section("Actualités") {
                if home.isLoading && home.news.isEmpty {
                    ProgressView("Chargement...")
                        .frame(maxWidth: .infinity, alignment: .center)
                } else if let error = home.error, home.news.isEmpty {
                    Text(error)
                        .foregroundStyle(.red)
                } else if home.news.isEmpty {
                    Text("Aucune actualité disponible pour le moment.")
                        .foregroundStyle(.secondary)
                } else {
                    ForEach(home.news) { article in
                        NavigationLink {
                            NewsDetailView(api: container.api, slug: article.slug)
                        } label: {
                            VStack(alignment: .leading, spacing: 6) {
                                HStack {
                                    if let publishedAt = article.publishedAt {
                                        Text(newsDateFormatter.string(from: publishedAt))
                                            .font(.caption)
                                            .foregroundStyle(.secondary)
                                    }
                                    if let category = article.category, !category.isEmpty {
                                        Spacer()
                                        Text(category)
                                            .font(.caption)
                                            .foregroundStyle(.secondary)
                                    }
                                }
                                Text(article.title)
                                    .fontWeight(.semibold)
                                Text(article.excerpt)
                                    .lineLimit(3)
                                    .foregroundStyle(.secondary)
                            }
                            .padding(.vertical, 4)
                        }
                    }
                }

                NavigationLink {
                    NewsListView(api: container.api)
                } label: {
                    HStack {
                        Label("Toutes les actualités", systemImage: "newspaper")
                            .fontWeight(.semibold)
                        Spacer()
                        Image(systemName: "chevron.right")
                            .foregroundStyle(.tertiary)
                    }
                }
            }
        }
        .navigationTitle("Accueil")
        .task { await home.load() }
    }
}

private struct OfferHubView: View {
    let api: APIClient
    @EnvironmentObject private var account: AccountViewModel
    @Binding var selectedTab: Int
    @Binding var filtersBadge: Int?

    var body: some View {
        List {
            Section("Produits") {
                NavigationLink {
                    ProductsListView(
                        api: api,
                        selectedTab: $selectedTab,
                        filtersBadge: $filtersBadge,
                        navigationTitle: "Produits"
                    )
                } label: {
                    Label("Tous les produits", systemImage: "shippingbox")
                }

                NavigationLink {
                    ProductsListView(
                        api: api,
                        selectedTab: $selectedTab,
                        filtersBadge: $filtersBadge,
                        initialSellingType: .sale,
                        navigationTitle: "Produits en vente"
                    )
                } label: {
                    Label("Vente", systemImage: "cart")
                }

                NavigationLink {
                    ProductsListView(
                        api: api,
                        selectedTab: $selectedTab,
                        filtersBadge: $filtersBadge,
                        initialSellingType: .rental,
                        navigationTitle: "Produits en location"
                    )
                } label: {
                    Label("Location", systemImage: "clock.arrow.circlepath")
                }
            }

            Section("Services") {
                NavigationLink {
                    ServicesCatalogView(api: api)
                } label: {
                    Label("Services", systemImage: "wrench.and.screwdriver")
                }

                NavigationLink {
                    TrainingsCatalogView(api: api)
                } label: {
                    Label("Formation", systemImage: "graduationcap")
                }

                NavigationLink {
                    TradeInRequestView(api: api, account: account)
                } label: {
                    Label("Reprise de matériel", systemImage: "arrow.triangle.2.circlepath")
                }
            }
        }
        .navigationTitle("Notre offre")
    }
}

private struct AboutHubView: View {
    let api: APIClient

    var body: some View {
        List {
            NavigationLink {
                ContactView(api: api)
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

private let newsDateFormatter: DateFormatter = {
    let formatter = DateFormatter()
    formatter.locale = Locale(identifier: "fr_FR")
    formatter.dateStyle = .medium
    formatter.timeStyle = .none
    return formatter
}()

private struct ServiceDetailView: View {
    let api: APIClient
    let serviceID: Int
    @EnvironmentObject private var account: AccountViewModel
    @State private var service: QuoteService?
    @State private var isLoading = false
    @State private var error: String?

    var body: some View {
        List {
            if isLoading && service == nil {
                Section {
                    ProgressView("Chargement du service...")
                        .frame(maxWidth: .infinity, alignment: .center)
                }
            } else if let error {
                Section {
                    Text(error).foregroundStyle(.red)
                }
            } else if let service {
                Section {
                    if let imageURL = api.assetURL(for: service.imageUrl) {
                        AsyncImage(url: imageURL) { phase in
                            switch phase {
                            case .success(let image):
                                image
                                    .resizable()
                                    .scaledToFit()
                                    .frame(maxWidth: .infinity, maxHeight: 220)
                                    .clipShape(RoundedRectangle(cornerRadius: 16))
                            case .failure:
                                servicePlaceholder
                            default:
                                ProgressView()
                                    .frame(maxWidth: .infinity, minHeight: 180)
                            }
                        }
                        .listRowInsets(EdgeInsets())
                    } else {
                        servicePlaceholder
                    }

                    VStack(alignment: .leading, spacing: 10) {
                        Text(service.title)
                            .font(.title2)
                            .fontWeight(.bold)
                        Text(service.description?.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty == false
                            ? (service.description ?? "")
                            : "Les informations détaillées de ce service seront précisées avec votre besoin.")
                            .foregroundStyle(.secondary)
                    }
                    .padding(.top, 8)
                }

                Section {
                    HStack(spacing: 12) {
                        serviceFactCard(
                            title: "Base tarifaire",
                            value: PriceFormatter.format(cents: service.priceCents)
                        )
                        serviceFactCard(
                            title: "Facturation",
                            value: serviceBillingModeLabel(service.unit)
                        )
                    }
                    HStack(spacing: 12) {
                        serviceFactCard(
                            title: "Durée estimée",
                            value: service.durationLabel ?? "Sur étude"
                        )
                        serviceFactCard(
                            title: "TVA",
                            value: "\(Int(service.vatRate.rounded())) %"
                        )
                    }
                }

                Section("Actions") {
                    NavigationLink {
                        QuoteRequestView(api: api, account: account)
                    } label: {
                        Label("Demander un devis", systemImage: "doc.badge.plus")
                    }

                    NavigationLink {
                        AppointmentBookingView(api: api)
                    } label: {
                        Label("Prendre rendez-vous", systemImage: "calendar.badge.plus")
                    }
                }
            }
        }
        .navigationTitle(service?.title ?? "Service")
        .navigationBarTitleDisplayMode(.inline)
        .task { await load() }
    }

    private var servicePlaceholder: some View {
        ZStack {
            RoundedRectangle(cornerRadius: 16)
                .fill(Color.gray.opacity(0.08))
            Image(systemName: "wrench.and.screwdriver")
                .font(.system(size: 42))
                .foregroundStyle(.secondary)
        }
        .frame(maxWidth: .infinity, minHeight: 180)
    }

    private func serviceFactCard(title: String, value: String) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title)
                .font(.caption)
                .foregroundStyle(.secondary)
            Text(value)
                .font(.headline)
                .fontWeight(.semibold)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(14)
        .background(Color(.secondarySystemBackground))
        .clipShape(RoundedRectangle(cornerRadius: 14))
    }

    private func load() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            service = try await api.publicService(id: serviceID)
        } catch {
            self.error = error.localizedDescription
        }
    }
}

private struct NewsDetailView: View {
    let api: APIClient
    let slug: String
    @EnvironmentObject private var account: AccountViewModel
    @State private var article: NewsArticle?
    @State private var comments: [NewsComment] = []
    @State private var commentsPage = 1
    @State private var commentsTotalPages = 1
    @State private var isLoading = false
    @State private var isLoadingComments = false
    @State private var isSubmittingComment = false
    @State private var error: String?
    @State private var commentsError: String?
    @State private var newComment = ""

    var body: some View {
        List {
            if isLoading && article == nil {
                Section {
                    ProgressView("Chargement de l’actualité...")
                        .frame(maxWidth: .infinity, alignment: .center)
                }
            } else if let error {
                Section {
                    Text(error).foregroundStyle(.red)
                }
            } else if let article {
                Section {
                    VStack(alignment: .leading, spacing: 12) {
                        HStack {
                            if let publishedAt = article.publishedAt {
                                Label(newsDateFormatter.string(from: publishedAt), systemImage: "calendar")
                                    .font(.footnote)
                                    .foregroundStyle(.secondary)
                            }
                            if let category = article.category, !category.isEmpty {
                                Spacer()
                                Text(category)
                                    .font(.caption)
                                    .padding(.horizontal, 8)
                                    .padding(.vertical, 4)
                                    .background(Color(.secondarySystemBackground))
                                    .clipShape(Capsule())
                            }
                        }
                        Text(article.title)
                            .font(.title2)
                            .fontWeight(.bold)
                        Text(article.excerpt)
                            .foregroundStyle(.secondary)
#if canImport(UIKit)
                        ShareLink(
                            item: newsShareURL(for: article),
                            subject: Text(article.title),
                            message: Text(article.excerpt)
                        ) {
                            Label("Partager l’actualité", systemImage: "square.and.arrow.up")
                                .fontWeight(.semibold)
                        }
#endif
                    }
                }

                Section("Contenu") {
                    Text(article.content)
                        .textSelection(.enabled)
                }

                Section("Commentaires") {
                    if let commentsError {
                        Text(commentsError).foregroundStyle(.red)
                    } else if isLoadingComments && comments.isEmpty {
                        ProgressView("Chargement des commentaires...")
                    } else if comments.isEmpty {
                        Text("Aucun commentaire pour le moment.")
                            .foregroundStyle(.secondary)
                    } else {
                        ForEach(comments) { comment in
                            VStack(alignment: .leading, spacing: 6) {
                                HStack {
                                    Text(comment.author.name)
                                        .fontWeight(.semibold)
                                    Spacer()
                                    Text(comment.createdAt.formatted(date: .abbreviated, time: .shortened))
                                        .font(.caption)
                                        .foregroundStyle(.secondary)
                                }
                                Text(comment.content)
                            }
                            .padding(.vertical, 6)
                        }
                    }

                    if commentsTotalPages > 1 {
                        HStack {
                            Button("Précédents") {
                                guard commentsPage > 1 else { return }
                                commentsPage -= 1
                                Task { await loadComments() }
                            }
                            .disabled(commentsPage <= 1 || isLoadingComments)
                            Spacer()
                            Text("\(commentsPage)/\(commentsTotalPages)")
                                .font(.footnote)
                                .foregroundStyle(.secondary)
                            Spacer()
                            Button("Suivants") {
                                guard commentsPage < commentsTotalPages else { return }
                                commentsPage += 1
                                Task { await loadComments() }
                            }
                            .disabled(commentsPage >= commentsTotalPages || isLoadingComments)
                        }
                    }

                    if account.isLoggedIn {
                        VStack(alignment: .leading, spacing: 8) {
                            Text("Ajouter un commentaire")
                                .fontWeight(.semibold)
                            TextEditor(text: $newComment)
                                .frame(minHeight: 120)
                            Button {
                                Task { await submitComment() }
                            } label: {
                                if isSubmittingComment {
                                    ProgressView()
                                        .frame(maxWidth: .infinity)
                                } else {
                                    Text("Publier le commentaire")
                                        .fontWeight(.semibold)
                                        .frame(maxWidth: .infinity)
                                }
                            }
                            .disabled(isSubmittingComment || newComment.trimmingCharacters(in: .whitespacesAndNewlines).count < 3)
                        }
                    } else {
                        Text("Connectez-vous pour ajouter un commentaire.")
                            .foregroundStyle(.secondary)
                    }
                }
            }
        }
        .navigationTitle(article?.title ?? "Actualité")
        .navigationBarTitleDisplayMode(.inline)
        .task {
            await loadArticle()
            await loadComments()
        }
    }

    private func loadArticle() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            article = try await api.newsArticle(slug: slug)
        } catch {
            self.error = error.localizedDescription
        }
    }

    private func loadComments() async {
        guard !isLoadingComments else { return }
        isLoadingComments = true
        commentsError = nil
        defer { isLoadingComments = false }

        do {
            let data = try await api.newsComments(slug: slug, page: commentsPage, perPage: 10)
            comments = data.items
            commentsTotalPages = max(1, data.meta.totalPages)
        } catch {
            self.commentsError = error.localizedDescription
        }
    }

    private func submitComment() async {
        let content = newComment.trimmingCharacters(in: .whitespacesAndNewlines)
        guard content.count >= 3 else { return }
        guard !isSubmittingComment else { return }
        isSubmittingComment = true
        commentsError = nil
        defer { isSubmittingComment = false }

        do {
            _ = try await api.createNewsComment(slug: slug, content: content)
            newComment = ""
            commentsPage = 1
            await loadComments()
        } catch {
            commentsError = error.localizedDescription
        }
    }

    private func newsShareURL(for article: NewsArticle) -> URL {
        URL(string: "https://hociatec.fr/actualites/\(article.slug)") ?? URL(string: "https://hociatec.fr/actualites")!
    }
}

private struct ServicesCatalogView: View {
    let api: APIClient
    @State private var services: [QuoteService] = []
    @State private var page = 1
    @State private var totalPages = 1
    @State private var searchText = ""
    @State private var appliedSearch = ""
    @State private var isLoading = false
    @State private var error: String?

    var body: some View {
        List {
            Section {
                TextField("Rechercher un service", text: $searchText)
                Button("Rechercher") {
                    appliedSearch = searchText.trimmingCharacters(in: .whitespacesAndNewlines)
                    page = 1
                    Task { await load() }
                }
            }

            Section("Services") {
                if isLoading && services.isEmpty {
                    ProgressView("Chargement des services...")
                } else if let error {
                    Text(error).foregroundStyle(.red)
                } else if services.isEmpty {
                    Text(appliedSearch.isEmpty ? "Aucun service publié pour le moment." : "Aucun service ne correspond à cette recherche.")
                        .foregroundStyle(.secondary)
                } else {
                    ForEach(services) { service in
                        NavigationLink {
                            ServiceDetailView(api: api, serviceID: service.id)
                        } label: {
                            VStack(alignment: .leading, spacing: 6) {
                                Text(service.title)
                                    .fontWeight(.semibold)
                                if let description = service.description, !description.isEmpty {
                                    Text(description)
                                        .lineLimit(2)
                                        .foregroundStyle(.secondary)
                                }
                                HStack {
                                    Text(PriceFormatter.format(cents: service.priceCents))
                                        .font(.footnote)
                                        .fontWeight(.semibold)
                                    Spacer()
                                    Text(service.durationLabel ?? "Sur étude")
                                        .font(.footnote)
                                        .foregroundStyle(.secondary)
                                }
                            }
                            .padding(.vertical, 4)
                        }
                    }
                }
            }

            if totalPages > 1 {
                Section {
                    HStack {
                        Button("Précédent") {
                            guard page > 1 else { return }
                            page -= 1
                            Task { await load() }
                        }
                        .disabled(page <= 1 || isLoading)
                        Spacer()
                        Text("\(page)/\(totalPages)")
                            .font(.footnote)
                            .foregroundStyle(.secondary)
                        Spacer()
                        Button("Suivant") {
                            guard page < totalPages else { return }
                            page += 1
                            Task { await load() }
                        }
                        .disabled(page >= totalPages || isLoading)
                    }
                }
            }
        }
        .navigationTitle("Services")
        .task { await load() }
    }

    private func load() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            let data = try await api.quoteServices(page: page, perPage: 7, query: appliedSearch.isEmpty ? nil : appliedSearch)
            services = data.items
            totalPages = max(1, data.meta?.totalPages ?? 1)
        } catch {
            self.error = error.localizedDescription
        }
    }
}

private struct NewsListView: View {
    let api: APIClient
    @State private var articles: [NewsArticle] = []
    @State private var page = 1
    @State private var totalPages = 1
    @State private var searchText = ""
    @State private var appliedSearch = ""
    @State private var isLoading = false
    @State private var error: String?

    var body: some View {
        List {
            Section {
                TextField("Rechercher une actualité", text: $searchText)
                Button("Rechercher") {
                    appliedSearch = searchText.trimmingCharacters(in: .whitespacesAndNewlines)
                    page = 1
                    Task { await load() }
                }
            }

            Section("Actualités") {
                if isLoading && articles.isEmpty {
                    ProgressView("Chargement des actualités...")
                } else if let error {
                    Text(error).foregroundStyle(.red)
                } else if articles.isEmpty {
                    Text("Aucune actualité disponible pour le moment.")
                        .foregroundStyle(.secondary)
                } else {
                    ForEach(articles) { article in
                        NavigationLink {
                            NewsDetailView(api: api, slug: article.slug)
                        } label: {
                            VStack(alignment: .leading, spacing: 6) {
                                HStack {
                                    if let publishedAt = article.publishedAt {
                                        Text(newsDateFormatter.string(from: publishedAt))
                                            .font(.caption)
                                            .foregroundStyle(.secondary)
                                    }
                                    if let category = article.category, !category.isEmpty {
                                        Spacer()
                                        Text(category)
                                            .font(.caption)
                                            .foregroundStyle(.secondary)
                                    }
                                }
                                Text(article.title)
                                    .fontWeight(.semibold)
                                Text(article.excerpt)
                                    .lineLimit(3)
                                    .foregroundStyle(.secondary)
                            }
                            .padding(.vertical, 4)
                        }
                    }
                }
            }

            if totalPages > 1 {
                Section {
                    HStack {
                        Button("Précédent") {
                            guard page > 1 else { return }
                            page -= 1
                            Task { await load() }
                        }
                        .disabled(page <= 1 || isLoading)
                        Spacer()
                        Text("\(page)/\(totalPages)")
                            .font(.footnote)
                            .foregroundStyle(.secondary)
                        Spacer()
                        Button("Suivant") {
                            guard page < totalPages else { return }
                            page += 1
                            Task { await load() }
                        }
                        .disabled(page >= totalPages || isLoading)
                    }
                }
            }
        }
        .navigationTitle("Actualités")
        .task { await load() }
    }

    private func load() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            let data = try await api.newsArticles(page: page, perPage: 9, query: appliedSearch.isEmpty ? nil : appliedSearch)
            articles = data.items
            totalPages = max(1, data.meta.totalPages)
        } catch {
            self.error = error.localizedDescription
        }
    }
}

private struct TrainingsCatalogView: View {
    let api: APIClient
    @State private var trainings: [Training] = []
    @State private var categories: [TrainingCategory] = []
    @State private var selectedCategorySlug = ""
    @State private var page = 1
    @State private var totalPages = 1
    @State private var searchText = ""
    @State private var appliedSearch = ""
    @State private var isLoading = false
    @State private var error: String?

    var body: some View {
        List {
            Section {
                TextField("Rechercher une formation", text: $searchText)
                if !categories.isEmpty {
                    Picker("Catégorie", selection: $selectedCategorySlug) {
                        Text("Toutes").tag("")
                        ForEach(categories) { category in
                            Text(category.name).tag(category.slug)
                        }
                    }
                }
                Button("Rechercher") {
                    appliedSearch = searchText.trimmingCharacters(in: .whitespacesAndNewlines)
                    page = 1
                    Task { await load() }
                }
            }

            Section("Formations") {
                if isLoading && trainings.isEmpty {
                    ProgressView("Chargement des formations...")
                } else if let error {
                    Text(error).foregroundStyle(.red)
                } else if trainings.isEmpty {
                    Text("Aucune formation publiée pour le moment.")
                        .foregroundStyle(.secondary)
                } else {
                    ForEach(trainings) { training in
                        NavigationLink {
                            TrainingDetailView(api: api, slug: training.slug)
                        } label: {
                            VStack(alignment: .leading, spacing: 8) {
                                Text(training.title)
                                    .fontWeight(.semibold)
                                Text(training.objective ?? training.shortDescription ?? "Formation accompagnée avec feuille de route pratique.")
                                    .lineLimit(3)
                                    .foregroundStyle(.secondary)
                                HStack {
                                    Text(training.categoryDetails?.name ?? training.category)
                                        .font(.footnote)
                                        .foregroundStyle(.secondary)
                                    Spacer()
                                    Text(PriceFormatter.format(cents: training.priceCents))
                                        .font(.footnote)
                                        .fontWeight(.semibold)
                                }
                            }
                            .padding(.vertical, 4)
                        }
                    }
                }
            }

            if totalPages > 1 {
                Section {
                    HStack {
                        Button("Précédent") {
                            guard page > 1 else { return }
                            page -= 1
                            Task { await load() }
                        }
                        .disabled(page <= 1 || isLoading)
                        Spacer()
                        Text("\(page)/\(totalPages)")
                            .font(.footnote)
                            .foregroundStyle(.secondary)
                        Spacer()
                        Button("Suivant") {
                            guard page < totalPages else { return }
                            page += 1
                            Task { await load() }
                        }
                        .disabled(page >= totalPages || isLoading)
                    }
                }
            }
        }
        .navigationTitle("Formations")
        .task {
            await loadCategoriesIfNeeded()
            await load()
        }
        .onChangeCompat(selectedCategorySlug) { _ in
            page = 1
            Task { await load() }
        }
    }

    private func loadCategoriesIfNeeded() async {
        guard categories.isEmpty else { return }
        do {
            categories = try await api.trainingCategories().filter(\.isActive)
        } catch {
            if self.error == nil {
                self.error = error.localizedDescription
            }
        }
    }

    private func load() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            let data = try await api.trainings(
                page: page,
                perPage: 8,
                query: appliedSearch.isEmpty ? nil : appliedSearch,
                category: selectedCategorySlug.isEmpty ? nil : selectedCategorySlug
            )
            trainings = data.items
            totalPages = max(1, data.meta.totalPages)
        } catch {
            self.error = error.localizedDescription
        }
    }
}

private struct TrainingDetailView: View {
    let api: APIClient
    let slug: String
    @State private var training: Training?
    @State private var sessions: [TrainingSession] = []
    @State private var isLoading = false
    @State private var error: String?

    var body: some View {
        List {
            if isLoading && training == nil {
                Section {
                    ProgressView("Chargement de la formation...")
                }
            } else if let error, training == nil {
                Section {
                    Text(error).foregroundStyle(.red)
                }
            } else if let training {
                Section {
                    VStack(alignment: .leading, spacing: 10) {
                        Text(training.title)
                            .font(.title3.weight(.semibold))
                        Text(training.objective ?? training.shortDescription ?? "Formation accompagnée avec feuille de route pratique.")
                            .foregroundStyle(.secondary)
                        LabeledContent("Catégorie", value: training.categoryDetails?.name ?? training.category)
                        LabeledContent("Modalité", value: nonEmptyText(training.availableFormatDetails.map(\.label).joined(separator: ", ")) ?? "À confirmer")
                        LabeledContent("Durée", value: trainingDurationLabel(training.durationMinutes))
                        LabeledContent("Tarif", value: PriceFormatter.format(cents: training.priceCents))
                        if let audience = nonEmptyText(training.audience) {
                            LabeledContent("Public concerné", value: audience)
                        }
                    }
                    .padding(.vertical, 4)
                }

                Section("Feuille de route") {
                    if training.roadmap.isEmpty {
                        Text("Le programme détaillé sera communiqué avec les informations de session.")
                            .foregroundStyle(.secondary)
                    } else {
                        ForEach(training.roadmap.sorted { $0.position < $1.position }) { item in
                            VStack(alignment: .leading, spacing: 4) {
                                Text("\(item.position). \(item.title)")
                                    .fontWeight(.semibold)
                            }
                            .padding(.vertical, 2)
                        }
                    }
                }

                Section("Sessions") {
                    if sessions.isEmpty {
                        Text("Aucune session ouverte pour le moment.")
                            .foregroundStyle(.secondary)
                    } else {
                        ForEach(sessions) { session in
                            VStack(alignment: .leading, spacing: 8) {
                                HStack {
                                    Text(session.formatLabel)
                                        .fontWeight(.semibold)
                                    Spacer()
                                    Text(session.statusLabel)
                                        .font(.footnote)
                                        .foregroundStyle(.secondary)
                                }
                                LabeledContent("Début", value: trainingDateTimeFormatter.string(from: session.startsAt))
                                LabeledContent("Fin", value: trainingDateTimeFormatter.string(from: session.endsAt))
                                LabeledContent("Places restantes", value: "\(max(0, session.remainingSeats))/\(session.capacity)")
                                if let location = nonEmptyText(session.location) {
                                    LabeledContent("Lieu", value: location)
                                }
                                if let meetingURL = nonEmptyText(session.meetingUrl) {
                                    Link(destination: URL(string: meetingURL) ?? URL(string: "https://hociatec.fr/formations/\(slug)")!) {
                                        Label("Lien de session", systemImage: "link")
                                    }
                                }
                            }
                            .padding(.vertical, 4)
                        }
                    }
                }
            }
        }
        .navigationTitle("Formation")
        .task { await load() }
    }

    private func load() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            let data = try await api.training(slug: slug)
            training = data.training
            sessions = data.sessions
        } catch {
            self.error = error.localizedDescription
        }
    }
}

private func serviceBillingModeLabel(_ value: String?) -> String {
    let normalized = (value ?? "")
        .folding(options: .diacriticInsensitive, locale: .current)
        .trimmingCharacters(in: .whitespacesAndNewlines)
        .lowercased()

    switch normalized {
    case "", "prix fixe":
        return "Prix fixe"
    case "heure", "horaire":
        return "Horaire"
    case "jour":
        return "À la journée"
    case "intervention":
        return "Par intervention"
    case "audit":
        return "Audit"
    case "installation":
        return "Installation"
    case "maintenance":
        return "Maintenance"
    default:
        return value?.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty == false ? (value ?? "Prix fixe") : "Prix fixe"
    }
}

private func nonEmptyText(_ value: String?) -> String? {
    guard let trimmed = value?.trimmingCharacters(in: .whitespacesAndNewlines), !trimmed.isEmpty else {
        return nil
    }
    return trimmed
}

private func trainingDurationLabel(_ minutes: Int) -> String {
    if minutes >= 60 {
        let hours = Double(minutes) / 60.0
        if hours.rounded() == hours {
            return "\(Int(hours)) h"
        }
        return String(format: "%.1f h", hours).replacingOccurrences(of: ".", with: ",")
    }
    return "\(minutes) min"
}

private let trainingDateTimeFormatter: DateFormatter = {
    let formatter = DateFormatter()
    formatter.locale = Locale(identifier: "fr_FR")
    formatter.dateStyle = .medium
    formatter.timeStyle = .short
    return formatter
}()

private struct BannerView: View {
    let message: String
    var isError: Bool = false
    var body: some View {
        Text(message)
            .font(.subheadline)
            .padding(.horizontal, 12)
            .padding(.vertical, 8)
            .foregroundStyle(isError ? Color.white : Color.primary)
            .background(isError ? Color.red.opacity(0.9) : Color(.systemBackground).opacity(0.9))
            .clipShape(Capsule())
            .shadow(radius: 3)
            .accessibilityLabel(isError ? "Erreur: \(message)" : message)
    }
}

private struct TradeInRequestView: View {
    @StateObject private var viewModel: TradeInViewModel
    @State private var showingFileImporter = false
    @Environment(\.dismiss) private var dismiss

    init(api: APIClient, account: AccountViewModel) {
        _viewModel = StateObject(wrappedValue: TradeInViewModel(api: api, account: account))
    }

    var body: some View {
        Form {
            if let error = viewModel.error, !error.isEmpty {
                Section { Text(error).foregroundStyle(.red) }
            }
            if let success = viewModel.successMessage, !success.isEmpty {
                Section { Label(success, systemImage: "checkmark.seal.fill").foregroundStyle(.green) }
            }

            Section {
                Picker("Catégorie", selection: $viewModel.selectedCategory) {
                    ForEach(viewModel.categories) { option in
                        Text(option.label).tag(option.value)
                    }
                }
                TextField("Nom du produit", text: $viewModel.productName)
                TextField("Marque", text: $viewModel.brand)
                TextField("Modèle", text: $viewModel.model)
                TextField("Numéro de série", text: $viewModel.serialNumber)
                TextField("Prix d’achat (€)", text: $viewModel.purchasePrice)
                    .keyboardType(.decimalPad)
                TextField("Année d’achat", text: $viewModel.purchaseYear)
                    .keyboardType(.numberPad)
            }

            Section("État") {
                Picker("État", selection: $viewModel.selectedCondition) {
                    ForEach(viewModel.conditions) { option in
                        Text(option.label).tag(option.value)
                    }
                }
                Toggle("Appareil fonctionnel", isOn: $viewModel.functional)
                Toggle("Accessoires inclus", isOn: $viewModel.hasAccessories)
                Toggle("Preuve d’achat disponible", isOn: $viewModel.hasProofOfPurchase)
                TextEditor(text: $viewModel.description)
                    .frame(minHeight: 120)
            }

            Section("Contact") {
                TextField("Prénom", text: $viewModel.firstName)
                    .textInputAutocapitalization(.words)
                TextField("Nom", text: $viewModel.lastName)
                    .textInputAutocapitalization(.words)
                TextField("E-mail", text: $viewModel.email)
                    .keyboardType(.emailAddress)
                    .textInputAutocapitalization(.never)
                    .autocorrectionDisabled()
                TextField("Téléphone", text: $viewModel.phone)
                    .keyboardType(.phonePad)
            }

            Section("RIB") {
                Button("Choisir un PDF") {
                    showingFileImporter = true
                }
                if let ribFileName = viewModel.ribFileName {
                    Text(ribFileName)
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                }
            }

            Section {
                Toggle("J’accepte le traitement de ma demande", isOn: $viewModel.consent)
            }

            Section {
                Button {
                    Task {
                        let ok = await viewModel.submit()
                        if ok {
#if canImport(UIKit)
                            UINotificationFeedbackGenerator().notificationOccurred(.success)
#endif
                            dismiss()
                        }
                    }
                } label: {
                    if viewModel.isSubmitting {
                        ProgressView()
                            .frame(maxWidth: .infinity)
                    } else {
                        Text("Envoyer la reprise")
                            .fontWeight(.semibold)
                            .frame(maxWidth: .infinity)
                    }
                }
                .disabled(viewModel.isSubmitting)
            }
        }
        .navigationTitle("Reprise")
        .task { await viewModel.loadMetadata() }
        .fileImporter(
            isPresented: $showingFileImporter,
            allowedContentTypes: [.pdf],
            allowsMultipleSelection: false
        ) { result in
            switch result {
            case .success(let urls):
                guard let url = urls.first else { return }
                let accessed = url.startAccessingSecurityScopedResource()
                defer {
                    if accessed {
                        url.stopAccessingSecurityScopedResource()
                    }
                }

                do {
                    let data = try Data(contentsOf: url)
                    let fileName = url.lastPathComponent.isEmpty ? "rib.pdf" : url.lastPathComponent
                    viewModel.setRib(fileName: fileName, data: data)
                } catch {
                    viewModel.error = "Impossible de lire le PDF sélectionné."
                }
            case .failure:
                viewModel.error = "Sélection du PDF annulée ou invalide."
            }
        }
    }
}
