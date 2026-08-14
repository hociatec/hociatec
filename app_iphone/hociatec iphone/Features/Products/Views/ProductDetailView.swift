import SwiftUI

struct ProductDetailView: View {
    @EnvironmentObject private var container: AppContainer
    @Environment(\.dismiss) private var dismiss
    @StateObject var viewModel: ProductDetailViewModel
    @Binding var selectedTab: Int
    @State var alertState = ProductDetailAlertState()

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
                ProductDetailErrorBanner(detailError: viewModel.detailError)
                ProductInfoSection(product: viewModel.product)
                ProductDetailDescriptionSection(description: viewModel.product.description)
                ProductDetailReviewsSectionContainer(
                    container: container,
                    viewModel: viewModel
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
        .productDetailAlerts(alertState: $alertState, dismiss: dismiss, selectedTab: $selectedTab)
        .productDetailFavoriteToolbar(viewModel: viewModel)
        .feedbackDialog(
            error: Binding(
                get: { viewModel.detailError ?? viewModel.reviewsError },
                set: { _ in
                    viewModel.detailError = nil
                    viewModel.reviewsError = nil
                }
            )
        )
    }

    private var imageURL: URL? {
        container.services.assets.assetURL(for: viewModel.product.imageUrl) ?? initialImageURL
    }
    private var placeholder: some View { ProductDetailImagePlaceholder() }
}
