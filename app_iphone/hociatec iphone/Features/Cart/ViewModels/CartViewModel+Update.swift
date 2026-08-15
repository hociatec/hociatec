import Foundation

extension CartViewModel {
    func update(
        item: CartItem,
        quantity: Int,
        rentalMonths: Int? = nil,
        rentalStartDate: String? = nil
    ) async {
        guard quantity >= 0 else { return }

        let current = cart?.items.first(where: { $0.identityKey == item.identityKey })
        let currentQty = current?.quantity ?? item.quantity
        let currentMonths = current?.rentalMonths ?? item.rentalMonths
        let currentStartDate = current?.rentalStartDate ?? item.rentalStartDate
        let desiredMonths = rentalMonths ?? currentMonths
        let desiredStartDate = rentalStartDate ?? currentStartDate

        let quantityChanged = quantity != currentQty
        let monthsChanged = rentalMonthsChanged(currentMonths: currentMonths, desiredMonths: desiredMonths)
        let startDateChanged = currentStartDate != desiredStartDate
        if !quantityChanged && !monthsChanged && !startDateChanged {
            return
        }

        let isIncreasing = quantityChanged && quantity > currentQty
        if quantityChanged, let stockMessage = stockValidationMessage(for: item, quantity: quantity) {
            statusMessage = stockMessage
            return
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
                sellingType: item.sellingType,
                currentSellingType: item.sellingType,
                rentalMonths: desiredMonths,
                currentRentalMonths: currentMonths,
                rentalStartDate: desiredStartDate,
                currentRentalStartDate: currentStartDate
            )
            tryApplyUpdatedCart(
                updatedCart,
                previousCart: previousCart,
                item: item,
                requestedQuantity: quantity,
                isIncreasing: isIncreasing,
                desiredMonths: desiredMonths,
                desiredStartDate: desiredStartDate
            )
        } catch {
            cart = previousCart
            presentError(error.localizedDescription)
            statusMessage = "La mise à jour du panier a échoué. Réessayez ou vérifiez le stock disponible."
        }
    }

    private func rentalMonthsChanged(currentMonths: Int?, desiredMonths: Int?) -> Bool {
        switch (currentMonths, desiredMonths) {
        case (nil, nil):
            return false
        case let (a?, b?):
            return a != b
        default:
            return true
        }
    }

    private func stockValidationMessage(for item: CartItem, quantity: Int) -> String? {
        let maxStock = cart?.items.first(where: { $0.product.id == item.product.id })?.product.stock ?? item.product.stock
        guard quantity > maxStock else { return nil }
        return "Stock insuffisant pour \(item.product.name). Quantité max: \(maxStock)."
    }

    private func tryApplyUpdatedCart(
        _ updatedCart: Cart,
        previousCart: Cart?,
        item: CartItem,
        requestedQuantity: Int,
        isIncreasing: Bool,
        desiredMonths: Int?,
        desiredStartDate: String?
    ) {
        if isIncreasing {
            let previousTotal = previousCart?.totalQuantity ?? 0
            if updatedCart.totalQuantity < previousTotal {
                cart = previousCart
                statusMessage = "Stock insuffisant pour \(item.product.name). L’article n’a pas été augmenté."
                return
            }
        }

        if isIncreasing {
            if let updatedItem = updatedCart.items.first(where: {
                $0.matches(
                    productId: item.product.id,
                    sellingType: item.sellingType,
                    rentalMonths: desiredMonths,
                    rentalStartDate: desiredStartDate
                )
            }) {
                if updatedItem.quantity < requestedQuantity {
                    cart = updatedCart
                    statusMessage = "Stock insuffisant pour \(item.product.name). Quantité ajustée à \(updatedItem.quantity)."
                    return
                }
            } else {
                cart = previousCart
                statusMessage = "Stock insuffisant pour \(item.product.name). L’article n’a pas été augmenté."
                return
            }
        }

        cart = updatedCart
    }
}
