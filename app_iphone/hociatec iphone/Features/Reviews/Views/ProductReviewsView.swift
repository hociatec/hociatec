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
                ProductReviewsSummarySection(average: average, total: viewModel.total)
            }

            if let myReview = viewModel.myReview {
                ProductMyReviewSection(review: myReview)
            }

            if viewModel.isLoading && viewModel.reviews.isEmpty {
                Section { ProgressView("Chargement des avis…") }
            } else if viewModel.reviews.isEmpty && viewModel.error == nil {
                ProductReviewsEmptySection(message: emptyMessage)
            } else {
                ProductReviewsListSection(reviews: viewModel.reviews)

                if canLoadMore {
                    ProductReviewsLoadMoreSection(isLoading: viewModel.isLoading) {
                        Task {
                            await viewModel.loadMore(
                                productService: productService,
                                orderService: orderService,
                                isLoggedIn: container.account.isLoggedIn
                            )
                        }
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
        .feedbackDialog(error: $viewModel.error)
    }

    private var canLoadMore: Bool {
        !viewModel.isLoading && viewModel.reviews.count < viewModel.total
    }

    private var emptyMessage: String {
        if viewModel.total == 0 { return "Aucun avis pour l’instant." }
        if container.account.isLoggedIn {
            return viewModel.myReview == nil
                ? "Aucun commentaire publié pour le moment."
                : "Aucun autre commentaire public pour le moment."
        }
        return "Connectez-vous pour voir les avis."
    }
}
