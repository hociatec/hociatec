import Foundation

protocol CartServing {
    func fetchCart() async throws -> Cart
    func addToCart(productId: Int, quantity: Int, rentalMonths: Int?, rentalStartDate: String?) async throws -> Cart
    func updateCart(
        productId: Int,
        quantity: Int,
        rentalMonths: Int?,
        currentRentalMonths: Int?,
        rentalStartDate: String?,
        currentRentalStartDate: String?
    ) async throws -> Cart
    func removeFromCart(productId: Int, rentalMonths: Int?, rentalStartDate: String?) async throws -> Cart
    func clearCart() async throws -> Cart
    func checkout() async throws -> CheckoutResult
}

protocol ProductServing: AssetServing {
    func featuredProducts() async throws -> [Product]
    func productList(search: String?, categorySlug: String?, sellingType: SellingType?, page: Int?, perPage: Int?) async throws -> ProductListData
    func products(search: String?, categorySlug: String?, sellingType: SellingType?) async throws -> [Product]
    func categories() async throws -> [CategorySummary]
    func product(slug: String) async throws -> Product
    func productReviews(slug: String, page: Int, perPage: Int) async throws -> ReviewListData
}

protocol FavoritesServing {
    func listFavorites(category: FavoriteCategory?) async throws -> [FavoriteEntry]
    func addFavorite(category: FavoriteCategory, targetId: Int) async throws -> AddFavoriteResponse
    func removeFavorite(category: FavoriteCategory, targetId: Int) async throws -> Bool
    func favoriteStatus(category: FavoriteCategory, targetId: Int) async throws -> FavoriteStatusResponse
}

protocol OrderServing {
    func myOrders() async throws -> [OrderSummary]
    func order(id: Int) async throws -> OrderSummary
    func cancelOrder(id: Int) async throws -> OrderSummary
    func checkoutSessionStatus(stripeSessionId: String) async throws -> CheckoutSessionStatusData
    func cancelCheckoutSession(stripeSessionId: String) async throws -> CheckoutSessionStatusData
    func pendingReviews() async throws -> [PendingReviewItem]
    func createReview(orderId: Int, orderItemId: Int, score: Int, comment: String?) async throws -> Review
}

protocol RentalServing {
    func myRentals() async throws -> MyRentalsResponse
    func requestRentalChange(orderItemId: Int, action: RentalRequestAction, requestedEndDate: String) async throws -> RentalChangeData
    func planRentalReturn(orderItemId: Int, mode: String, requestedDate: String) async throws -> RentalItem
}

protocol VoucherServing {
    func myVouchers(page: Int, perPage: Int) async throws -> VoucherListData
}
