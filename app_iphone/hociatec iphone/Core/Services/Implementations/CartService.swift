import Foundation

struct CartService: CartServing {
    let api: APIClient

    func fetchCart() async throws -> Cart { try await api.fetchCart() }
    func addToCart(productId: Int, quantity: Int, sellingType: SellingType, rentalMonths: Int?, rentalStartDate: String?) async throws -> Cart {
        try await api.addToCart(
            productId: productId,
            quantity: quantity,
            sellingType: sellingType,
            rentalMonths: rentalMonths,
            rentalStartDate: rentalStartDate
        )
    }
    func updateCart(
        productId: Int,
        quantity: Int,
        sellingType: SellingType,
        currentSellingType: SellingType,
        rentalMonths: Int?,
        currentRentalMonths: Int?,
        rentalStartDate: String?,
        currentRentalStartDate: String?
    ) async throws -> Cart {
        try await api.updateCart(
            productId: productId,
            quantity: quantity,
            sellingType: sellingType,
            currentSellingType: currentSellingType,
            rentalMonths: rentalMonths,
            currentRentalMonths: currentRentalMonths,
            rentalStartDate: rentalStartDate,
            currentRentalStartDate: currentRentalStartDate
        )
    }
    func removeFromCart(productId: Int, sellingType: SellingType, rentalMonths: Int?, rentalStartDate: String?) async throws -> Cart {
        try await api.removeFromCart(
            productId: productId,
            sellingType: sellingType,
            rentalMonths: rentalMonths,
            rentalStartDate: rentalStartDate
        )
    }
    func clearCart() async throws -> Cart { try await api.clearCart() }
    func checkout() async throws -> CheckoutResult { try await api.checkout() }
}
