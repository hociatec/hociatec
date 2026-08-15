import Foundation

extension ProductDetailViewModel {
    func openRentalSheet() {
        isShowingRentalSheet = true
    }

    func closeRentalSheet() {
        isShowingRentalSheet = false
    }

    func decreaseRentalMonths() {
        rentalMonths = max(1, rentalMonths - 1)
    }

    func increaseRentalMonths() {
        rentalMonths = min(36, rentalMonths + 1)
    }

    func effectiveRentalMonths(using cart: CartViewModel) -> Int {
        max(1, matchingRentalItem(using: cart)?.rentalMonths ?? rentalMonths)
    }

    func stockLimit(using cart: CartViewModel) -> Int {
        let cartItem = matchingRentalItem(using: cart) ?? cart.cart?.items.first(where: {
            $0.product.id == product.id && $0.sellingType == product.sellingType
        })
        let currentQuantity = cartItem?.quantity ?? 0
        let cartItemStock = cartItem?.product.stock
        return max(cartItemStock ?? product.stock, currentQuantity)
    }

    func currentRentalStartDateString() -> String {
        DatePresentation.encodeAPIDay(rentalStartDate)
    }

    func computedRentalEndDate() -> Date? {
        guard let monthAnchor = Calendar.current.date(byAdding: .month, value: max(1, rentalMonths), to: rentalStartDate) else {
            return nil
        }
        return Calendar.current.date(byAdding: .day, value: -1, to: monthAnchor)
    }

    func matchingRentalItem(using cart: CartViewModel) -> CartItem? {
        guard product.sellingType == .rental else {
            return cart.cart?.items.first(where: {
                $0.product.id == product.id && $0.sellingType == product.sellingType
            })
        }

        return cart.cart?.items.first(where: {
            $0.matches(
                productId: product.id,
                sellingType: product.sellingType,
                rentalMonths: rentalMonths,
                rentalStartDate: currentRentalStartDateString()
            )
        })
    }

    func syncRentalSelection(from cart: CartViewModel) {
        guard product.sellingType == .rental,
              let existing = cart.cart?.items.first(where: {
                  $0.product.id == product.id && $0.sellingType == product.sellingType
              }) else {
            return
        }

        if let months = existing.rentalMonths {
            rentalMonths = max(1, months)
        }
        if let startDate = DatePresentation.parseAPIDay(existing.rentalStartDate) {
            rentalStartDate = startDate
        }
    }
}
