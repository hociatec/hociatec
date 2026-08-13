import SwiftUI

@MainActor
struct ProductReviewsView: View {
    let productService: ProductServing
    let orderService: OrderServing
    let productName: String
    let productSlug: String
    let productSku: String

    @EnvironmentObject private var container: AppContainer
    @StateObject private var viewModel: ProductReviewsViewModel

    init(service: ProductServing, orderService: OrderServing, productName: String, productSlug: String, productSku: String) {
        self.productService = service
        self.orderService = orderService
        self.productName = productName
        self.productSlug = productSlug
        self.productSku = productSku
        _viewModel = StateObject(wrappedValue: ProductReviewsViewModel(productSlug: productSlug, productSku: productSku))
    }

    var body: some View {
        List {
            if let average = viewModel.average, viewModel.total > 0 {
                Section {
                    HStack(spacing: 10) {
                        ratingStars(for: average)
                        Text(String(format: "%.1f/5", average))
                            .font(.subheadline)
                            .foregroundStyle(.secondary)
                        Spacer()
                        Text("\(viewModel.total) avis")
                            .font(.subheadline)
                            .foregroundStyle(.secondary)
                    }
                }
            }

            if let myReview = viewModel.myReview {
                Section("Votre avis") {
                    VStack(alignment: .leading, spacing: 6) {
                        HStack {
                            ratingStars(for: Double(myReview.score))
                            Spacer()
                            Text(DateFormatters.frDay.string(from: myReview.createdAt))
                                .font(.caption)
                                .foregroundStyle(.secondary)
                        }
                        if let comment = myReview.comment, !comment.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
                            Text(comment)
                        } else {
                            Text("Sans commentaire.")
                                .font(.footnote)
                                .foregroundStyle(.secondary)
                        }
                    }
                    .padding(.vertical, 4)
                }
            }

            if let error = viewModel.error {
                Section { Text(error).foregroundStyle(.red) }
            }

            if viewModel.isLoading && viewModel.reviews.isEmpty {
                Section { ProgressView("Chargement des avis…") }
            } else if viewModel.reviews.isEmpty && viewModel.error == nil {
                Section {
                    let message: String = {
                        if viewModel.total == 0 { return "Aucun avis pour l’instant." }
                        if container.account.isLoggedIn {
                            return viewModel.myReview == nil ? "Aucun commentaire publié pour le moment." : "Aucun autre commentaire public pour le moment."
                        }
                        return "Connectez-vous pour voir les avis."
                    }()
                    Text(message).foregroundStyle(.secondary)
                }
            } else {
                Section {
                    ForEach(viewModel.reviews, id: \.id) { review in
                        VStack(alignment: .leading, spacing: 6) {
                            HStack {
                                ratingStars(for: Double(review.score))
                                Spacer()
                                Text(DateFormatters.frDay.string(from: review.createdAt))
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                            }
                            if let comment = review.comment, !comment.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
                                Text(comment)
                            } else {
                                Text("Sans commentaire.")
                                    .font(.footnote)
                                    .foregroundStyle(.secondary)
                            }
                            Text(review.author.displayName)
                                .font(.footnote)
                                .foregroundStyle(.secondary)
                        }
                        .padding(.vertical, 6)
                    }
                }

                if canLoadMore {
                    Section {
                        Button {
                            Task { await viewModel.loadMore(productService: productService, orderService: orderService, isLoggedIn: container.account.isLoggedIn) }
                        } label: {
                            if viewModel.isLoading {
                                ProgressView().frame(maxWidth: .infinity)
                            } else {
                                Text("Charger plus")
                                    .fontWeight(.semibold)
                                    .frame(maxWidth: .infinity)
                            }
                        }
                        .disabled(viewModel.isLoading)
                    }
                }
            }
        }
        .navigationTitle("Avis")
        .navigationBarTitleDisplayMode(.inline)
        .task { await viewModel.load(productService: productService, orderService: orderService, page: 1, replace: true, isLoggedIn: container.account.isLoggedIn) }
        .onChangeCompat(container.account.isLoggedIn) { isLoggedIn in
            guard isLoggedIn else { return }
            Task { await viewModel.load(productService: productService, orderService: orderService, page: 1, replace: true, isLoggedIn: isLoggedIn) }
        }
        .refreshable { await viewModel.load(productService: productService, orderService: orderService, page: 1, replace: true, isLoggedIn: container.account.isLoggedIn) }
        .accessibilityLabel("Avis sur \(productName)")
    }

    private var canLoadMore: Bool {
        !viewModel.isLoading && viewModel.reviews.count < viewModel.total
    }

    private func ratingStars(for average: Double) -> some View {
        HStack(spacing: 2) {
            ForEach(0..<5, id: \.self) { idx in
                let threshold = Double(idx + 1)
                Image(systemName: average >= threshold ? "star.fill" : (average > Double(idx) ? "star.leadinghalf.filled" : "star"))
                    .foregroundStyle(.yellow)
                    .accessibilityHidden(true)
            }
        }
        .accessibilityLabel(String(format: "Note %.1f sur 5", average))
    }
}

@MainActor
private final class ProductReviewsViewModel: ObservableObject {
    @Published var reviews: [Review] = []
    @Published var myReview: Review?
    @Published var page: Int = 1
    @Published var perPage: Int = 20
    @Published var total: Int = 0
    @Published var average: Double?
    @Published var isLoading = false
    @Published var error: String?

    private let productSlug: String
    private let productSku: String

    init(productSlug: String, productSku: String) {
        self.productSlug = productSlug
        self.productSku = productSku
    }

    func loadMore(productService: ProductServing, orderService: OrderServing, isLoggedIn: Bool) async {
        guard !isLoading else { return }
        guard reviews.count < total else { return }
        await load(productService: productService, orderService: orderService, page: page + 1, replace: false, isLoggedIn: isLoggedIn)
    }

    func load(productService: ProductServing, orderService: OrderServing, page: Int, replace: Bool, isLoggedIn: Bool) async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            let data = try await productService.productReviews(slug: productSlug, page: page, perPage: perPage)
            self.page = data.meta.page
            self.perPage = data.meta.perPage
            self.total = data.meta.total
            self.average = data.meta.average
            if replace {
                self.reviews = data.items
            } else {
                self.reviews.append(contentsOf: data.items)
            }
            await loadMyReviewIfNeeded(orderService: orderService, isLoggedIn: isLoggedIn)
        } catch {
            self.error = error.localizedDescription
            if replace {
                self.reviews = []
                self.total = 0
                self.average = nil
            }
            await loadMyReviewIfNeeded(orderService: orderService, isLoggedIn: isLoggedIn)
        }
    }

    private func loadMyReviewIfNeeded(orderService: OrderServing, isLoggedIn: Bool) async {
        guard isLoggedIn else {
            myReview = nil
            return
        }
        if !reviews.isEmpty { return }
        do {
            let orders = try await orderService.myOrders()
            var candidates: [Review] = []
            for order in orders {
                for item in order.items where item.productSku == productSku {
                    if let review = item.review {
                        candidates.append(review)
                    }
                }
            }
            myReview = candidates.sorted(by: { $0.createdAt > $1.createdAt }).first
        } catch {
            myReview = nil
        }
    }
}
