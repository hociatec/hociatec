import SwiftUI

struct ProductDetailView: View {
    @EnvironmentObject private var container: AppContainer
    @Environment(\.dismiss) private var dismiss
    @StateObject fileprivate var viewModel: ProductDetailViewModel
    @Binding fileprivate var selectedTab: Int
    @State fileprivate var alertState = ProductDetailAlertState()

    let initialImageURL: URL?
    @ObservedObject var cart: CartViewModel

    private var currentQuantity: Int {
        cart.cart?.items.first(where: { $0.product.id == viewModel.product.id })?.quantity ?? 0
    }

    init(viewModel: ProductDetailViewModel, imageURL: URL?, cart: CartViewModel, selectedTab: Binding<Int>) {
        _viewModel = StateObject(wrappedValue: viewModel)
        self._selectedTab = selectedTab
        self.initialImageURL = imageURL
        self.cart = cart
    }

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 16) {
                ProductDetailHeroView(
                    product: viewModel.product,
                    imageURL: imageURL,
                    placeholder: AnyView(placeholder),
                    reviewsAverage: viewModel.reviewsAverage,
                    reviewsTotal: viewModel.reviewsTotal
                )

                if let detailError = viewModel.detailError {
                    Label(detailError, systemImage: "exclamationmark.triangle.fill")
                        .foregroundStyle(.red)
                        .font(.footnote)
                }

                ProductInfoSection(product: viewModel.product)

                Section("Description") {
                    Text(viewModel.product.description)
                        .font(.body)
                        .foregroundStyle(.primary)
                }
                ProductReviewsPreviewSection(
                    reviewsAverage: viewModel.reviewsAverage,
                    reviewsTotal: viewModel.reviewsTotal,
                    reviews: viewModel.reviews,
                    isLoadingReviews: viewModel.isLoadingReviews,
                    reviewsError: viewModel.reviewsError,
                    isLoggedIn: container.account.isLoggedIn,
                    canLoadMore: viewModel.hasMoreReviews,
                    loadMoreAction: {
                        Task {
                            await viewModel.loadReviews(page: viewModel.nextReviewsPage)
                        }
                    },
                    reviewsDestination: AnyView(
                        ProductReviewsView(
                            service: container.services.products,
                            orderService: container.services.orders,
                            productName: viewModel.product.name,
                            productSlug: viewModel.product.slug,
                            productSku: viewModel.product.sku
                        )
                        .environmentObject(container)
                    )
                )

                ProductPurchaseSection(
                    currentQuantity: currentQuantity,
                    stockLimit: stockLimit,
                    isLoading: cart.isLoading,
                    isOutOfStock: viewModel.product.stock == 0,
                    showRentalSelector: viewModel.product.sellingType == .rental,
                    rentalMonths: viewModel.rentalMonths,
                    decreaseRentalMonths: viewModel.decreaseRentalMonths,
                    increaseRentalMonths: viewModel.increaseRentalMonths,
                    decreaseQuantity: {
                        Task { await decreaseQuantity() }
                    },
                    increaseQuantity: {
                        Task { await increaseQuantity() }
                    },
                    addToCart: {
                        Task { await addCurrentProductToCart() }
                    }
                )
            }
            .padding()
        }
        .navigationTitle(viewModel.product.name)
        .navigationBarTitleDisplayMode(.inline)
        .task {
            await viewModel.loadInitialData(cart: cart)
        }
        .onChangeCompat(container.account.isLoggedIn) { isLoggedIn in
            guard isLoggedIn else { return }
            Task {
                await viewModel.loadReviews(page: 1)
            }
        }
        .alert("Ajout au panier", isPresented: $alertState.showAddAlert) {
            Button("Continuer", role: .cancel) { dismiss() }
            Button("Voir le panier") {
                selectedTab = 2
                dismiss()
            }
        } message: {
            Text("\(alertState.addedProductName) a été ajouté au panier.")
        }
        .alert("Stock insuffisant", isPresented: $alertState.showStockAlert) {
            Button("OK", role: .cancel) {}
        } message: {
            Text(alertState.stockAlertMessage)
        }
        .toolbar {
            ToolbarItem(placement: .topBarTrailing) {
                Button {
                    Task { await viewModel.toggleFavorite() }
                } label: {
                    Image(systemName: viewModel.isFavorite ? "heart.fill" : "heart")
                }
                .accessibilityLabel(viewModel.isFavorite ? "Retirer des favoris" : "Ajouter aux favoris")
            }
        }
    }

    private var imageURL: URL? {
        container.services.assets.assetURL(for: viewModel.product.imageUrl) ?? initialImageURL
    }

    private var placeholder: some View {
        ZStack {
            RoundedRectangle(cornerRadius: 12)
                .fill(.gray.opacity(0.08))
            Image(systemName: "photo")
                .foregroundStyle(.secondary)
        }
    }
}
