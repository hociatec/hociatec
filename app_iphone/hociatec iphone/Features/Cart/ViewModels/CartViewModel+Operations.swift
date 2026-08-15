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

    func add(
        product: Product,
        quantity: Int = 1,
        rentalMonths: Int? = nil,
        rentalStartDate: String? = nil
    ) async {
        isLoading = true
        error = nil
        statusMessage = nil
        do {
            cart = try await service.addToCart(
                productId: product.id,
                quantity: quantity,
                sellingType: product.sellingType,
                rentalMonths: rentalMonths,
                rentalStartDate: rentalStartDate
            )
            if product.sellingType == .rental, let rentalMonths {
                let dateLabel = DatePresentation.formatAPIDay(rentalStartDate)
                presentSuccess("\(product.name) loué pour \(rentalMonths) mois à partir du \(dateLabel).")
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
            cart = try await service.removeFromCart(
                productId: item.product.id,
                sellingType: item.sellingType,
                rentalMonths: item.rentalMonths,
                rentalStartDate: item.rentalStartDate
            )
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

    func presentSuccess(_ message: String) {
        statusMessage = message
        feedbackCenter.presentSuccess(message)
    }

    func presentError(_ message: String) {
        error = message
        feedbackCenter.presentError(message)
    }
}
