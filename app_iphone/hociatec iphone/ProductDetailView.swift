import SwiftUI
import Foundation
import UIKit

struct ProductDetailView: View {
    @EnvironmentObject private var container: AppContainer
    @Environment(\.dismiss) private var dismiss
    @State private var product: Product
    @Binding private var selectedTab: Int
    @State private var rentalMonths: Int = 1
    @State private var isLoadingDetail = false
    @State private var detailError: String?
    @State private var showStockAlert = false
    @State private var stockAlertMessage = ""
    @State private var showAddAlert = false
    @State private var addedProductName: String = ""
    @State private var reviews: [Review] = []
    @State private var reviewsPerPage: Int = 3
    @State private var reviewsTotal: Int = 0
    @State private var reviewsAverage: Double? = nil
    @State private var isLoadingReviews: Bool = false
    @State private var reviewsError: String? = nil
    @State private var isFavorite: Bool = false

    let initialImageURL: URL?
    @ObservedObject var cart: CartViewModel

    private var currentQuantity: Int {
        cart.cart?.items.first(where: { $0.product.id == product.id })?.quantity ?? 0
    }

    init(product: Product, imageURL: URL?, cart: CartViewModel, selectedTab: Binding<Int>) {
        self._product = State(initialValue: product)
        self._selectedTab = selectedTab
        self.initialImageURL = imageURL
        self.cart = cart
        _rentalMonths = State(initialValue: 1)
    }

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 16) {
                if let imageURL {
                    AsyncImage(url: imageURL) { phase in
                        switch phase {
                        case .success(let image):
                            image
                                .resizable()
                                .scaledToFit()
                                .frame(maxWidth: .infinity)
                                .cornerRadius(12)
                        case .failure:
                            placeholder
                        default:
                            ZStack {
                                RoundedRectangle(cornerRadius: 12)
                                    .fill(.gray.opacity(0.1))
                                    .frame(height: 220)
                                ProgressView()
                            }
                        }
                    }
                } else {
                    placeholder
                        .frame(height: 220)
                }

                if let detailError {
                    Label(detailError, systemImage: "exclamationmark.triangle.fill")
                        .foregroundStyle(.red)
                        .font(.footnote)
                }

                VStack(alignment: .leading, spacing: 8) {
                    Text(product.name)
                        .font(.title2)
                        .fontWeight(.bold)
                    HStack(spacing: 10) {
                        Label(product.category.name, systemImage: "tag")
                            .font(.footnote)
                            .foregroundStyle(.secondary)
                        if let average = reviewsAverage, reviewsTotal > 0 {
                            HStack(spacing: 4) {
                                Image(systemName: "star.fill")
                                    .foregroundStyle(.yellow)
                                Text(String(format: "%.1f", average))
                                    .font(.footnote)
                                    .fontWeight(.semibold)
                                Text("(\(reviewsTotal))")
                                    .font(.footnote)
                                    .foregroundStyle(.secondary)
                            }
                        }
                    }
                    Text(product.shortDescription)
                        .font(.subheadline)
                        .foregroundStyle(.secondary)
                }

                HStack(spacing: 12) {
                    productHighlightCard(
                        title: "Disponibilité",
                        value: product.stock > 0 ? "En stock" : "Indisponible",
                        detail: "\(stockLimit) unité(s)"
                    )
                    productHighlightCard(
                        title: "Référence",
                        value: product.sku,
                        detail: product.sellingType.label
                    )
                }

                Section("Informations") {
                    LabeledContent("Type", value: product.sellingType.label)
                    LabeledContent("Catégorie", value: product.category.name)
                    LabeledContent("Référence", value: product.sku)
                    LabeledContent("Stock") {
                        Text("\(product.stock) disponible(s)")
                            .foregroundColor(product.stock > 0 ? .primary : .red)
                    }
                    priceRow
                    if let createdAt = product.createdAt {
                        LabeledContent("Ajouté le", value: DateFormatters.frDay.string(from: createdAt))
                    }
                    if let updatedAt = product.updatedAt {
                        LabeledContent("Mis à jour le", value: DateFormatters.frDay.string(from: updatedAt))
                    }
                }

                Section("Description") {
                    Text(product.description)
                        .font(.body)
                        .foregroundStyle(.primary)
                }
                Section("Avis") {
                    if let avg = reviewsAverage {
                        HStack(spacing: 6) {
                            ratingStars(for: avg)
                            Text(String(format: "%.1f/5", avg))
                                .font(.subheadline)
                                .foregroundStyle(.secondary)
                        }
                    }
                    if let err = reviewsError {
                        Text(err)
                            .foregroundStyle(.red)
                    } else if isLoadingReviews {
                        ProgressView("Chargement des avis…")
                    } else if reviews.isEmpty {
                        let message: String = {
                            if reviewsTotal == 0 { return "Aucun avis pour l’instant." }
                            if container.account.isLoggedIn { return "Aucun commentaire publié pour le moment." }
                            return "Connectez-vous pour voir les avis."
                        }()
                        Text(message)
                            .foregroundStyle(.secondary)
                    } else {
                        ForEach(reviews.prefix(3), id: \.id) { rev in
                            VStack(alignment: .leading, spacing: 4) {
                                HStack {
                                    ratingStars(for: Double(rev.score))
                                    Spacer()
                                    Text(DateFormatters.frDay.string(from: rev.createdAt))
                                        .font(.caption)
                                        .foregroundStyle(.secondary)
                                }
                                if let c = rev.comment, !c.isEmpty {
                                    Text(c)
                                } else {
                                    Text("Sans commentaire.")
                                        .font(.footnote)
                                        .foregroundStyle(.secondary)
                                }
                                Text(rev.author.displayName)
                                    .font(.footnote)
                                    .foregroundStyle(.secondary)
                            }
                            .padding(.vertical, 4)
                        }
                        NavigationLink {
                            ProductReviewsView(productName: product.name, productSlug: product.slug, productSku: product.sku)
                                .environmentObject(container)
                        } label: {
                            HStack(spacing: 8) {
                                Text("Voir tous les avis")
                                    .fontWeight(.semibold)
                                if reviewsTotal > 0 {
                                    Text("(\(reviewsTotal))")
                                        .foregroundStyle(.secondary)
                                }
                                Spacer()
                                Image(systemName: "chevron.right")
                                    .foregroundStyle(.tertiary)
                            }
                            .font(.subheadline)
                            .padding(.horizontal, 12)
                            .padding(.vertical, 8)
                            .background(Color.blue.opacity(0.12))
                            .foregroundStyle(.blue)
                            .clipShape(Capsule())
                        }
                        .buttonStyle(.plain)
                        .accessibilityLabel(reviewsTotal > 0 ? "Voir tous les avis (\(reviewsTotal))" : "Voir tous les avis")

                        if reviews.count < reviewsTotal {
                            Button {
                                Task { await loadReviews(page: nextReviewsPage) }
                            } label: {
                                if isLoadingReviews {
                                    ProgressView()
                                        .frame(maxWidth: .infinity)
                                } else {
                                    Text("Charger plus d’avis")
                                        .fontWeight(.semibold)
                                        .frame(maxWidth: .infinity)
                                }
                            }
                            .disabled(isLoadingReviews)
                        }
                    }
                }

                if product.sellingType == .rental {
                    VStack(alignment: .leading, spacing: 8) {
                        Text("Durée de location")
                            .font(.headline)
                        HStack(spacing: 12) {
                            Text("\(rentalMonths) mois")
                                .fontWeight(.semibold)
                            Spacer()
                            Button {
                                rentalMonths = max(1, rentalMonths - 1)
                            } label: {
                                Image(systemName: "minus")
                                    .frame(width: 30, height: 30)
                            }
                            .buttonStyle(.bordered)
                            .accessibilityLabel("Moins")
                            Button {
                                rentalMonths = min(36, rentalMonths + 1)
                            } label: {
                                Image(systemName: "plus")
                                    .frame(width: 30, height: 30)
                            }
                            .buttonStyle(.bordered)
                            .accessibilityLabel("Plus")
                        }
                    }
                }

                if currentQuantity > 0 {
                    HStack(spacing: 16) {
                        Button {
                            Task {
                                if let item = cart.cart?.items.first(where: { $0.product.id == product.id }) {
                                    let newQ = item.quantity - 1
                                    if newQ <= 0 {
                                        await cart.remove(item: item)
                                    } else {
                                        await cart.update(item: item, quantity: newQ)
                                    }
                                }
                            }
                        } label: {
                            Image(systemName: "minus")
                                .frame(width: 44, height: 44)
                        }
                        .buttonStyle(.bordered)
                        .accessibilityLabel("Moins")

                        Text("Quantité: \(currentQuantity)")
                            .fontWeight(.semibold)

                        if currentQuantity < stockLimit {
                            Button {
                                Task {
                                    let qty = cart.cart?.items.first(where: { $0.product.id == product.id })?.quantity ?? 0
                                    if qty >= stockLimit {
                                        stockAlertMessage = "Stock insuffisant pour \(product.name). Quantité max: \(stockLimit)."
                                        showStockAlert = true
                                    } else if let item = cart.cart?.items.first(where: { $0.product.id == product.id }) {
                                        await cart.update(
                                            item: item,
                                            quantity: item.quantity + 1,
                                            rentalMonths: item.rentalMonths ?? rentalMonthsIfNeeded
                                        )
                                        let generator = UINotificationFeedbackGenerator()
                                        generator.notificationOccurred(.success)
                                    } else {
                                        await cart.add(
                                            product: product,
                                            rentalMonths: product.sellingType == .rental ? rentalMonthsIfNeeded : nil
                                        )
                                        addedProductName = product.name
                                        showAddAlert = true
                                        let generator = UINotificationFeedbackGenerator()
                                        generator.notificationOccurred(.success)
                                    }
                                }
                            } label: {
                                Image(systemName: "plus")
                                    .frame(width: 44, height: 44)
                            }
                            .buttonStyle(.bordered)
                            .accessibilityLabel("Plus")
                            .disabled(cart.isLoading)
                            .allowsHitTesting(!cart.isLoading)
                        }
                    }
                    .padding(.top, 8)
                } else {
                    Button {
                        Task {
                            if stockLimit <= 0 {
                                stockAlertMessage = "Stock insuffisant pour \(product.name)."
                                showStockAlert = true
                            } else {
                                await cart.add(
                                    product: product,
                                    rentalMonths: product.sellingType == .rental ? rentalMonthsIfNeeded : nil
                                )
                                addedProductName = product.name
                                showAddAlert = true
                                let generator = UINotificationFeedbackGenerator()
                                generator.notificationOccurred(.success)
                            }
                        }
                    } label: {
                        HStack {
                            Spacer()
                            if cart.isLoading {
                                ProgressView()
                            } else {
                                Text("Ajouter au panier")
                                    .fontWeight(.semibold)
                            }
                            Spacer()
                        }
                        .padding()
                        .background(Color.teal.opacity(0.15))
                        .foregroundStyle(.teal)
                        .clipShape(RoundedRectangle(cornerRadius: 12))
                    }
                    .disabled(cart.isLoading || product.stock == 0)
                    .padding(.top, 8)
                }
            }
            .padding()
        }
        .navigationTitle(product.name)
        .navigationBarTitleDisplayMode(.inline)
        .task {
            await loadProductDetail()
            await loadReviews(page: 1)
            await refreshFavorite()
        }
        .onChangeCompat(container.account.isLoggedIn) { isLoggedIn in
            guard isLoggedIn else { return }
            Task { await loadReviews(page: 1) }
        }
        .alert("Ajout au panier", isPresented: $showAddAlert) {
            Button("Continuer", role: .cancel) { dismiss() }
            Button("Voir le panier") {
                selectedTab = 2
                dismiss()
            }
        } message: {
            Text("\(addedProductName) a été ajouté au panier.")
        }
        .alert("Stock insuffisant", isPresented: $showStockAlert) {
            Button("OK", role: .cancel) {}
        } message: {
            Text(stockAlertMessage)
        }
        .toolbar {
            ToolbarItem(placement: .topBarTrailing) {
                Button {
                    Task { await toggleFavorite() }
                } label: {
                    Image(systemName: isFavorite ? "heart.fill" : "heart")
                }
                .accessibilityLabel(isFavorite ? "Retirer des favoris" : "Ajouter aux favoris")
            }
        }
    }

    private var imageURL: URL? {
        container.api.assetURL(for: product.imageUrl) ?? initialImageURL
    }

    /// Use freshest stock from cart if available to align UI limits with server.
    private var stockLimit: Int {
        let cartItemStock = cart.cart?.items.first(where: { $0.product.id == product.id })?.product.stock
        // If backend stock dropped to 0 while quantity exists, keep at least currentQuantity to avoid false blocking.
        return max(cartItemStock ?? product.stock, currentQuantity)
    }
    
    private var rentalMonthsIfNeeded: Int {
        max(1, cart.cart?.items.first(where: { $0.product.id == product.id })?.rentalMonths ?? rentalMonths)
    }

    private var nextReviewsPage: Int {
        max(2, (reviews.count / max(1, reviewsPerPage)) + 1)
    }

    private var placeholder: some View {
        ZStack {
            RoundedRectangle(cornerRadius: 12)
                .fill(.gray.opacity(0.08))
            Image(systemName: "photo")
                .foregroundStyle(.secondary)
        }
    }

    private func loadReviews(page: Int = 1) async {
        guard !isLoadingReviews else { return }
        isLoadingReviews = true
        reviewsError = nil
        defer { isLoadingReviews = false }
        do {
            let data = try await container.api.productReviews(slug: product.slug, page: page, perPage: reviewsPerPage)
            reviewsPerPage = data.meta.perPage
            reviewsTotal = data.meta.total
            reviewsAverage = data.meta.average
            if page == 1 {
                reviews = data.items
            } else {
                reviews.append(contentsOf: data.items)
            }
        } catch {
            reviewsError = error.localizedDescription
        }
    }

    private func loadProductDetail() async {
        guard !isLoadingDetail else { return }
        isLoadingDetail = true
        detailError = nil
        defer { isLoadingDetail = false }
        do {
            product = try await container.api.product(slug: product.slug)
            if product.sellingType == .rental {
                if let existing = cart.cart?.items.first(where: { $0.product.id == product.id })?.rentalMonths {
                    rentalMonths = max(1, existing)
                }
            }
        } catch let err {
            detailError = err.localizedDescription
        }
    }
}

// Split price composition to keep type-checking fast
private extension ProductDetailView {
    var priceRow: some View {
        HStack(spacing: 4) {
            Text("Prix ")
                .font(.subheadline)
                .foregroundStyle(.secondary)
            Text(PriceFormatter.format(cents: product.effectivePriceCents))
                .font(.title3)
                .fontWeight(.bold)
            if product.sellingType == .rental {
                Text("par mois")
                    .font(.subheadline)
                    .foregroundStyle(.secondary)
            }
            if product.effectivePriceCents < product.priceCents {
                Text(PriceFormatter.format(cents: product.priceCents))
                    .font(.subheadline)
                    .foregroundStyle(.secondary)
                    .strikethrough()
            }
        }
        .lineLimit(1)
        .minimumScaleFactor(0.7)
        .allowsTightening(true)
        .truncationMode(.tail)
    }
    
    func ratingStars(for average: Double) -> some View {
        HStack(spacing: 2) {
            ForEach(0..<5, id: \.self) { idx in
                let threshold = Double(idx + 1)
                Image(systemName: average >= threshold ? "star.fill" : (average > Double(idx) ? "star.leadinghalf.filled" : "star"))
                    .foregroundStyle(.yellow)
                    .accessibilityHidden(true)
            }
        }
        .accessibilityLabel(String(format: "Note moyenne %.1f sur 5", average))
    }

    func productHighlightCard(title: String, value: String, detail: String) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title)
                .font(.caption)
                .foregroundStyle(.secondary)
            Text(value)
                .font(.headline)
                .fontWeight(.semibold)
            Text(detail)
                .font(.footnote)
                .foregroundStyle(.secondary)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(14)
        .background(Color(.secondarySystemBackground))
        .clipShape(RoundedRectangle(cornerRadius: 14))
    }
    
    private func refreshFavorite() async {
        do {
            let favs = try await container.api.listFavorites()
            isFavorite = favs.contains { $0.product.id == product.id }
        } catch {
            // Ignore errors silently for unauthenticated users
            isFavorite = false
        }
    }

    private func toggleFavorite() async {
        do {
            if isFavorite {
                _ = try await container.api.removeFavorite(productId: product.id)
            } else {
                _ = try await container.api.addFavorite(productId: product.id)
            }
            await refreshFavorite()
        } catch {
            // Optionally surface an error via a toast/banner if desired
        }
    }
}
