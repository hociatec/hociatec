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
            presentError(err.localizedDescription)
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
                presentSuccess("\(product.name) loué pour \(rentalMonths) mois.")
            } else {
                presentSuccess("\(product.name) ajouté au panier.")
            }
        } catch let err {
            presentError(err.localizedDescription)
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
            presentError(err.localizedDescription)
        }

        isLoading = false
    }

    func clear() async {
        isLoading = true
        error = nil
        statusMessage = nil

        do {
            cart = try await service.clearCart()
            presentSuccess("Panier vidé.")
        } catch let err {
            presentError(err.localizedDescription)
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
                presentSuccess("Commande créée (\(order.number)).")
                cart = nil
            } else if result.requiresRedirect {
                presentSuccess("Redirection vers le paiement.")
            }
            return result
        } catch let err {
            presentError(err.localizedDescription)
            return nil
        }
    }

    fileprivate func presentSuccess(_ message: String) {
        statusMessage = message
        globalDialog = .success(message)
    }

    fileprivate func presentError(_ message: String) {
        error = message
        globalDialog = .error(message)
    }
}
