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

    init(product: Product) {
        self.product = product
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
        productService: ProductServing,
        favoritesService: FavoritesServing,
        cart: CartViewModel
    ) async {
        await loadProductDetail(productService: productService, cart: cart)
        await loadReviews(productService: productService, page: 1)
        await refreshFavorite(favoritesService: favoritesService)
    }

    func loadReviews(productService: ProductServing, page: Int = 1) async {
        guard !isLoadingReviews else { return }
        isLoadingReviews = true
        reviewsError = nil
        defer { isLoadingReviews = false }

        do {
            let data = try await productService.productReviews(slug: product.slug, page: page, perPage: reviewsPerPage)
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

    func loadProductDetail(productService: ProductServing, cart: CartViewModel) async {
        guard !isLoadingDetail else { return }
        isLoadingDetail = true
        detailError = nil
        defer { isLoadingDetail = false }

        do {
            product = try await productService.product(slug: product.slug)
            if product.sellingType == .rental,
               let existing = cart.cart?.items.first(where: { $0.product.id == product.id })?.rentalMonths {
                rentalMonths = max(1, existing)
            }
        } catch {
            detailError = error.localizedDescription
        }
    }

    func refreshFavorite(favoritesService: FavoritesServing) async {
        do {
            let favorites = try await favoritesService.listFavorites()
            isFavorite = favorites.contains { $0.product.id == product.id }
        } catch {
            isFavorite = false
        }
    }

    func toggleFavorite(favoritesService: FavoritesServing) async {
        do {
            if isFavorite {
                _ = try await favoritesService.removeFavorite(productId: product.id)
            } else {
                _ = try await favoritesService.addFavorite(productId: product.id)
            }
            await refreshFavorite(favoritesService: favoritesService)
        } catch {
        }
    }
}
