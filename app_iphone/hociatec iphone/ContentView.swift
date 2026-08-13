import SwiftUI
import Foundation
import UniformTypeIdentifiers
#if canImport(UIKit)
import UIKit
#endif

private struct CartTabLabel: View {
    let cart: Cart?
    var body: some View {
        let label: String = {
            guard let cart else { return "Panier, chargement…" }
            let count = cart.totalQuantity
            return count == 1 ? "Panier, 1 article" : "Panier, \(count) articles"
        }()
        return Label("Panier", systemImage: "cart")
            .accessibilityLabel(label)
    }
}

struct ContentView: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var cart: CartViewModel
    @State private var selectedTab: Int = 0
    @State private var productFiltersBadge: Int? = nil
    @StateObject private var cartVM: CartViewModel = CartViewModel(api: APIClient(sessionStore: SessionStore()))
    @State private var bannerMessage: String? = nil
    @State private var bannerIsError: Bool = false

    var body: some View {
        TabView(selection: $selectedTab) {
            NavigationStack {
                HomeScreen(api: container.api, selectedTab: $selectedTab)
            }
            .tabItem { Label("Accueil", systemImage: "house") }
            .tag(0)

            NavigationStack {
                OfferHubView(api: container.api, selectedTab: $selectedTab, filtersBadge: $productFiltersBadge)
            }
            .tabItem { Label("Notre offre", systemImage: "square.grid.2x2") }
            .badge(productFiltersBadge.map { Text("\($0)") })
            .tag(1)

            NavigationStack {
                CartScreen()
            }
            .tabItem {
                CartTabLabel(cart: cart.cart)
            }
            .badge(cart.cart?.totalQuantity ?? 0)
            .tag(2)

            NavigationStack {
                NewsListView(api: container.api)
            }
            .tabItem { Label("Actualités", systemImage: "newspaper") }
            .tag(3)

            NavigationStack {
                AccountScreen()
            }
            .tabItem { Label("Compte", systemImage: "person") }
            .tag(4)
        }
        .task { await cart.refresh() }
        .overlay(alignment: .top) {
            if let message = bannerMessage {
                BannerView(message: message, isError: bannerIsError)
                    .transition(.move(edge: .top).combined(with: .opacity))
                    .padding(.top, 8)
            }
        }
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
