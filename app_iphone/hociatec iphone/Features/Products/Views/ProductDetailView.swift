import SwiftUI

struct ProductDetailView: View {
    @EnvironmentObject private var container: AppContainer
    @Environment(\.dismiss) var dismiss
    @StateObject var viewModel: ProductDetailViewModel
    @Binding var selectedTab: Int
    @State var feedbackDialog: FeedbackDialogState?

    let initialImageURL: URL?
    @ObservedObject var cart: CartViewModel

    private var currentQuantity: Int {
        if viewModel.product.sellingType == .rental {
            return viewModel.matchingRentalItem(using: cart)?.quantity ?? 0
        }

        return cart.cart?.items.first(where: {
            $0.product.id == viewModel.product.id && $0.sellingType == viewModel.product.sellingType
        })?.quantity ?? 0
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
                    addButtonLabel: viewModel.product.sellingType == .rental ? "Ajouter la location" : "Ajouter au panier",
                    showRentalSelector: viewModel.product.sellingType == .rental,
                    rentalMonths: viewModel.rentalMonths,
                    rentalStartDateLabel: DatePresentation.formatAPIDay(viewModel.currentRentalStartDateString()),
                    rentalEndDateLabel: DatePresentation.formatAPIDay(
                        viewModel.computedRentalEndDate().map(DatePresentation.encodeAPIDay)
                    ),
                    decreaseRentalMonths: viewModel.decreaseRentalMonths,
                    increaseRentalMonths: viewModel.increaseRentalMonths,
                    configureRental: viewModel.openRentalSheet,
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
        .productDetailFavoriteToolbar(viewModel: viewModel)
        .sheet(isPresented: $viewModel.isShowingRentalSheet) {
            RentalConfigurationSheet(
                rentalMonths: $viewModel.rentalMonths,
                rentalStartDate: $viewModel.rentalStartDate,
                onCancel: viewModel.closeRentalSheet,
                onConfirm: viewModel.closeRentalSheet
            )
        }
        .feedbackDialog($feedbackDialog)
        .feedbackDialog(
            error: Binding(
                get: { viewModel.detailError ?? viewModel.reviewsError },
                set: { _ in
                    viewModel.detailError = nil
                    viewModel.reviewsError = nil
                }
            )
        )
        .feedbackDialog(
            Binding(
                get: { viewModel.favoriteFeedback },
                set: { newValue in
                    if newValue == nil {
                        viewModel.favoriteFeedback = nil
                    }
                }
            )
        )
    }

    private var imageURL: URL? {
        container.services.assets.assetURL(for: viewModel.product.imageUrl) ?? initialImageURL
    }
    private var placeholder: some View { ProductDetailImagePlaceholder() }
}
