import Foundation

@MainActor
final class ProductDetailViewModel: ObservableObject {
    @Published var product: Product
    @Published var rentalMonths: Int = 1
    @Published var isLoadingDetail = false
    @Published var detailError: String?
    @Published var reviews: [Review] = []
    @Published var reviewsPerPage: Int = 3
    @Published var reviewsTotal: Int = 0
    @Published var reviewsAverage: Double?
    @Published var isLoadingReviews = false
    @Published var reviewsError: String?
    @Published var isFavorite = false

    private let loadDetailUseCase: LoadProductDetailUseCase
    private let loadReviewsUseCase: LoadProductReviewsUseCase
    private let loadFavoriteStatusUseCase: LoadProductFavoriteStatusUseCase
    private let toggleFavoriteUseCase: ToggleProductFavoriteUseCase

    init(product: Product, useCases: ProductsUseCases) {
        self.product = product
        self.loadDetailUseCase = useCases.loadProductDetail
        self.loadReviewsUseCase = useCases.loadProductReviews
        self.loadFavoriteStatusUseCase = useCases.loadFavoriteStatus
        self.toggleFavoriteUseCase = useCases.toggleFavorite
    }

    var hasMoreReviews: Bool {
        reviews.count < reviewsTotal
    }

    var nextReviewsPage: Int {
        max(2, (reviews.count / max(1, reviewsPerPage)) + 1)
    }

    func decreaseRentalMonths() {
        rentalMonths = max(1, rentalMonths - 1)
    }

    func increaseRentalMonths() {
        rentalMonths = min(36, rentalMonths + 1)
    }

    func effectiveRentalMonths(using cart: CartViewModel) -> Int {
        max(1, cart.cart?.items.first(where: { $0.product.id == product.id })?.rentalMonths ?? rentalMonths)
    }

    func stockLimit(using cart: CartViewModel) -> Int {
        let currentQuantity = cart.cart?.items.first(where: { $0.product.id == product.id })?.quantity ?? 0
        let cartItemStock = cart.cart?.items.first(where: { $0.product.id == product.id })?.product.stock
        return max(cartItemStock ?? product.stock, currentQuantity)
    }

    func loadInitialData(
        cart: CartViewModel
    ) async {
        await loadProductDetail(cart: cart)
        await loadReviews(page: 1)
        await refreshFavorite()
    }

    func loadReviews(page: Int = 1) async {
        guard !isLoadingReviews else { return }
        isLoadingReviews = true
        reviewsError = nil
        defer { isLoadingReviews = false }

        do {
            let data = try await loadReviewsUseCase.execute(slug: product.slug, page: page, perPage: reviewsPerPage)
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

    func loadProductDetail(cart: CartViewModel) async {
        guard !isLoadingDetail else { return }
        isLoadingDetail = true
        detailError = nil
        defer { isLoadingDetail = false }

        do {
            product = try await loadDetailUseCase.execute(slug: product.slug)
            if product.sellingType == .rental,
               let existing = cart.cart?.items.first(where: { $0.product.id == product.id })?.rentalMonths {
                rentalMonths = max(1, existing)
            }
        } catch {
            detailError = error.localizedDescription
        }
    }

    func refreshFavorite() async {
        do {
            isFavorite = try await loadFavoriteStatusUseCase.execute(productId: product.id)
        } catch {
            isFavorite = false
        }
    }

    func toggleFavorite() async {
        do {
            isFavorite = try await toggleFavoriteUseCase.execute(productId: product.id, isFavorite: isFavorite)
        } catch {
        }
    }
}
