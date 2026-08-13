import Foundation

extension CartViewModel {
    func refresh() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        statusMessage = nil

        do {
            cart = try await service.fetchCart()
        } catch let err {
            error = err.localizedDescription
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
            error = err.localizedDescription
        }
        isLoading = false
    }

    func remove(item: CartItem) async {
        isLoading = true
        error = nil
        statusMessage = nil

        do {
            cart = try await service.removeFromCart(productId: item.product.id)
        } catch let err {
            error = err.localizedDescription
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
            error = err.localizedDescription
        }

        isLoading = false
    }

    func checkout() async -> CheckoutResult? {
        isLoading = true
        error = nil
        statusMessage = nil
        defer { isLoading = false }

        do {
            let result = try await service.checkout()
            if let order = result.order {
                statusMessage = "Commande créée (\(order.number))."
                cart = nil
            } else if result.requiresRedirect {
                statusMessage = "Redirection vers le paiement."
            }
            return result
        } catch let err {
            error = err.localizedDescription
            return nil
        }
    }
}
