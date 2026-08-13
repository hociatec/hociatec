import Foundation
import Combine

@MainActor
final class CartViewModel: ObservableObject {
    @Published var cart: Cart?
    @Published var isLoading = false
    @Published var error: String?
    @Published var statusMessage: String?

    private let service: CartServing

    init(service: CartServing) {
        self.service = service
    }

    func refresh() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        statusMessage = nil

        do {
            cart = try await service.fetchCart()
        } catch let err {
            self.error = err.localizedDescription
        }

        isLoading = false
    }

    func add(product: Product, quantity: Int = 1, rentalMonths: Int? = nil) async {
        isLoading = true
        error = nil
        statusMessage = nil
        do {
            cart = try await service.addToCart(
                productId: product.id,
                quantity: quantity,
                rentalMonths: rentalMonths
            )
            if product.sellingType == .rental, let rentalMonths {
                statusMessage = "\(product.name) loué pour \(rentalMonths) mois."
            } else {
                statusMessage = "\(product.name) ajouté au panier."
            }
        } catch let err {
            self.error = err.localizedDescription
        }
        isLoading = false
    }

    func update(item: CartItem, quantity: Int, rentalMonths: Int? = nil) async {
        guard quantity >= 0 else { return }

        let current: CartItem? = cart?.items.first(where: { $0.product.id == item.product.id })
        let currentQty = current?.quantity ?? item.quantity
        let currentMonths = current?.rentalMonths ?? item.rentalMonths
        let desiredMonths = rentalMonths ?? currentMonths

        let quantityChanged = quantity != currentQty
        let monthsChanged: Bool = {
            switch (currentMonths, desiredMonths) {
            case (nil, nil):
                return false
            case let (a?, b?):
                return a != b
            default:
                return true
            }
        }()

        if !quantityChanged && !monthsChanged {
            return
        }

        let isIncreasing = quantityChanged && quantity > currentQty

        if quantityChanged {
            if isIncreasing {
                let maxStock = cart?.items.first(where: { $0.product.id == item.product.id })?.product.stock ?? item.product.stock
                if quantity > maxStock {
                    self.statusMessage = "Stock insuffisant pour \(item.product.name). Quantité max: \(maxStock)."
                    return
                }
            } else {
                let localMax = cart?.items.first(where: { $0.product.id == item.product.id })?.product.stock ?? item.product.stock
                if quantity > localMax {
                    self.statusMessage = "Stock insuffisant pour \(item.product.name). Quantité max: \(localMax)."
                    return
                }
            }
        }

        let previousCart = cart

        isLoading = true
        error = nil
        statusMessage = nil
        defer { isLoading = false }

        do {
            let updatedCart = try await service.updateCart(
                productId: item.product.id,
                quantity: quantity,
                rentalMonths: desiredMonths,
                currentRentalMonths: currentMonths
            )

            if isIncreasing {
                let previousTotal = previousCart?.totalQuantity ?? 0
                if updatedCart.totalQuantity < previousTotal {
                    self.cart = previousCart
                    self.statusMessage = "Stock insuffisant pour \(item.product.name). L’article n’a pas été augmenté."
                    return
                }
            }

            if isIncreasing {
                if let updatedItem = updatedCart.items.first(where: { $0.product.id == item.product.id }) {
                    if updatedItem.quantity < quantity {
                        self.cart = updatedCart
                        self.statusMessage = "Stock insuffisant pour \(item.product.name). Quantité ajustée à \(updatedItem.quantity)."
                        return
                    }
                } else {
                    self.cart = previousCart
                    self.statusMessage = "Stock insuffisant pour \(item.product.name). L’article n’a pas été augmenté."
                    return
                }
            }

            self.cart = updatedCart
        } catch {
            self.cart = previousCart
            self.error = error.localizedDescription
            self.statusMessage = "La mise à jour du panier a échoué. Réessayez ou vérifiez le stock disponible."
        }
    }

    func remove(item: CartItem) async {
        isLoading = true
        error = nil
        statusMessage = nil

        do {
            cart = try await service.removeFromCart(productId: item.product.id)
        } catch let err {
            self.error = err.localizedDescription
        }

        isLoading = false
    }

    func clear() async {
        isLoading = true
        error = nil
        statusMessage = nil

        do {
            cart = try await service.clearCart()
            statusMessage = "Panier vidé."
        } catch let err {
            self.error = err.localizedDescription
        }

        isLoading = false
    }

    func checkout() async -> OrderSummary? {
        isLoading = true
        error = nil
        statusMessage = nil
        defer { isLoading = false }

        do {
            let order = try await service.checkout()
            statusMessage = "Commande créée (\(order.number))."
            cart = nil
            return order
        } catch let err {
            self.error = err.localizedDescription
            return nil
        }
    }
}
