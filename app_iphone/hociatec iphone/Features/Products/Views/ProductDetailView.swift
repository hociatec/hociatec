import SwiftUI
import UIKit

struct ProductDetailView: View {
    @EnvironmentObject private var container: AppContainer
    @Environment(\.dismiss) private var dismiss
    @StateObject private var viewModel: ProductDetailViewModel
    @Binding private var selectedTab: Int
    @State private var showStockAlert = false
    @State private var stockAlertMessage = ""
    @State private var showAddAlert = false
    @State private var addedProductName: String = ""

    let initialImageURL: URL?
    @ObservedObject var cart: CartViewModel

    private var currentQuantity: Int {
        cart.cart?.items.first(where: { $0.product.id == viewModel.product.id })?.quantity ?? 0
    }

    init(product: Product, imageURL: URL?, cart: CartViewModel, selectedTab: Binding<Int>) {
        _viewModel = StateObject(wrappedValue: ProductDetailViewModel(product: product))
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
                            await viewModel.loadReviews(
                                productService: container.services.products,
                                page: viewModel.nextReviewsPage
                            )
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
            await viewModel.loadInitialData(
                productService: container.services.products,
                favoritesService: container.services.favorites,
                cart: cart
            )
        }
        .onChangeCompat(container.account.isLoggedIn) { isLoggedIn in
            guard isLoggedIn else { return }
            Task {
                await viewModel.loadReviews(productService: container.services.products, page: 1)
            }
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
                    Task { await viewModel.toggleFavorite(favoritesService: container.services.favorites) }
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

    private var stockLimit: Int {
        viewModel.stockLimit(using: cart)
    }

    private var rentalMonthsIfNeeded: Int {
        viewModel.effectiveRentalMonths(using: cart)
    }

    private var placeholder: some View {
        ZStack {
            RoundedRectangle(cornerRadius: 12)
                .fill(.gray.opacity(0.08))
            Image(systemName: "photo")
                .foregroundStyle(.secondary)
        }
    }

    private func decreaseQuantity() async {
        guard let item = cart.cart?.items.first(where: { $0.product.id == viewModel.product.id }) else { return }
        let newQuantity = item.quantity - 1
        if newQuantity <= 0 {
            await cart.remove(item: item)
        } else {
            await cart.update(item: item, quantity: newQuantity)
        }
    }

    private func increaseQuantity() async {
        let currentCartQuantity = cart.cart?.items.first(where: { $0.product.id == viewModel.product.id })?.quantity ?? 0
        if currentCartQuantity >= stockLimit {
            stockAlertMessage = "Stock insuffisant pour \(viewModel.product.name). Quantité max: \(stockLimit)."
            showStockAlert = true
            return
        }

        if let item = cart.cart?.items.first(where: { $0.product.id == viewModel.product.id }) {
            await cart.update(
                item: item,
                quantity: item.quantity + 1,
                rentalMonths: item.rentalMonths ?? rentalMonthsIfNeeded
            )
        } else {
            await cart.add(
                product: viewModel.product,
                quantity: 1,
                rentalMonths: viewModel.product.sellingType == .rental ? rentalMonthsIfNeeded : nil
            )
            addedProductName = viewModel.product.name
            showAddAlert = true
        }
        UINotificationFeedbackGenerator().notificationOccurred(.success)
    }

    private func addCurrentProductToCart() async {
        if stockLimit <= 0 {
            stockAlertMessage = "Stock insuffisant pour \(viewModel.product.name)."
            showStockAlert = true
            return
        }

        await cart.add(
            product: viewModel.product,
            rentalMonths: viewModel.product.sellingType == .rental ? rentalMonthsIfNeeded : nil
        )
        addedProductName = viewModel.product.name
        showAddAlert = true
        UINotificationFeedbackGenerator().notificationOccurred(.success)
    }
}
