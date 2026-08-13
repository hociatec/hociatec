import SwiftUI
import UIKit

extension ProductDetailView {
    var stockLimit: Int {
        viewModel.stockLimit(using: cart)
    }

    var rentalMonthsIfNeeded: Int {
        viewModel.effectiveRentalMonths(using: cart)
    }

    func decreaseQuantity() async {
        guard let item = cart.cart?.items.first(where: { $0.product.id == viewModel.product.id }) else { return }
        let newQuantity = item.quantity - 1
        if newQuantity <= 0 {
            await cart.remove(item: item)
        } else {
            await cart.update(item: item, quantity: newQuantity)
        }
    }

    func increaseQuantity() async {
        let currentCartQuantity = cart.cart?.items.first(where: { $0.product.id == viewModel.product.id })?.quantity ?? 0
        if currentCartQuantity >= stockLimit {
            alertState.presentStock(
                message: "Stock insuffisant pour \(viewModel.product.name). Quantité max: \(stockLimit)."
            )
            return
        }

        if let item = cart.cart?.items.first(where: { $0.product.id == viewModel.product.id }) {
            await cart.update(
                item: item,
                quantity: item.quantity + 1,
                rentalMonths: item.rentalMonths ?? rentalMonthsIfNeeded
            )
        } else {
            await cart.add(
                product: viewModel.product,
                quantity: 1,
                rentalMonths: viewModel.product.sellingType == .rental ? rentalMonthsIfNeeded : nil
            )
            alertState.presentAddConfirmation(productName: viewModel.product.name)
        }
        UINotificationFeedbackGenerator().notificationOccurred(.success)
    }

    func addCurrentProductToCart() async {
        if stockLimit <= 0 {
            alertState.presentStock(message: "Stock insuffisant pour \(viewModel.product.name).")
            return
        }

        await cart.add(
            product: viewModel.product,
            rentalMonths: viewModel.product.sellingType == .rental ? rentalMonthsIfNeeded : nil
        )
        alertState.presentAddConfirmation(productName: viewModel.product.name)
        UINotificationFeedbackGenerator().notificationOccurred(.success)
    }
}
