import Foundation

struct CartService: CartServing {
    let api: APIClient

    func fetchCart() async throws -> Cart { try await api.fetchCart() }
    func addToCart(productId: Int, quantity: Int, rentalMonths: Int?) async throws -> Cart { try await api.addToCart(productId: productId, quantity: quantity, rentalMonths: rentalMonths) }
    func updateCart(productId: Int, quantity: Int, rentalMonths: Int?, currentRentalMonths: Int?) async throws -> Cart { try await api.updateCart(productId: productId, quantity: quantity, rentalMonths: rentalMonths, currentRentalMonths: currentRentalMonths) }
    func removeFromCart(productId: Int) async throws -> Cart { try await api.removeFromCart(productId: productId) }
    func clearCart() async throws -> Cart { try await api.clearCart() }
    func checkout() async throws -> CheckoutResult { try await api.checkout() }
}
