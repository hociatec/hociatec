import Foundation

extension ProductDetailViewModel {
    func decreaseRentalMonths() {
        rentalMonths = max(1, rentalMonths - 1)
    }

    func increaseRentalMonths() {
        rentalMonths = min(36, rentalMonths + 1)
    }

    func effectiveRentalMonths(using cart: CartViewModel) -> Int {
        max(1, cart.cart?.items.first(where: { $0.product.id == product.id })?.rentalMonths ?? rentalMonths)
    }

    func stockLimit(using cart: CartViewModel) -> Int {
        let currentQuantity = cart.cart?.items.first(where: { $0.product.id == product.id })?.quantity ?? 0
        let cartItemStock = cart.cart?.items.first(where: { $0.product.id == product.id })?.product.stock
        return max(cartItemStock ?? product.stock, currentQuantity)
    }
}
