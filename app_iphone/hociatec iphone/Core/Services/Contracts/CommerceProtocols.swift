import Foundation

protocol CartServing {
    func fetchCart() async throws -> Cart
    func addToCart(productId: Int, quantity: Int, rentalMonths: Int?) async throws -> Cart
    func updateCart(productId: Int, quantity: Int, rentalMonths: Int?, currentRentalMonths: Int?) async throws -> Cart
    func removeFromCart(productId: Int) async throws -> Cart
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
    func listFavorites() async throws -> [FavoriteEntry]
    func addFavorite(productId: Int) async throws -> AddFavoriteResponse
    func removeFavorite(productId: Int) async throws -> Bool
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

protocol VoucherServing {
    func myVouchers(page: Int, perPage: Int) async throws -> VoucherListData
}
