import UIKit

extension ProductDetailView {
    var stockLimit: Int {
        viewModel.stockLimit(using: cart)
    }

    var rentalMonthsIfNeeded: Int {
        viewModel.effectiveRentalMonths(using: cart)
    }

    var rentalStartDateIfNeeded: String {
        viewModel.currentRentalStartDateString()
    }

    var selectedCartItem: CartItem? {
        viewModel.matchingRentalItem(using: cart)
    }

    func decreaseQuantity() async {
        let fallbackItem = viewModel.product.sellingType == .rental
            ? nil
            : cart.cart?.items.first(where: {
                $0.product.id == viewModel.product.id && $0.sellingType == viewModel.product.sellingType
            })
        guard let item = selectedCartItem ?? fallbackItem else { return }
        let newQuantity = item.quantity - 1
        if newQuantity <= 0 {
            await cart.remove(item: item)
        } else {
            await cart.update(item: item, quantity: newQuantity)
        }
    }

    func increaseQuantity() async {
        let fallbackItem = viewModel.product.sellingType == .rental
            ? nil
            : cart.cart?.items.first(where: {
                $0.product.id == viewModel.product.id && $0.sellingType == viewModel.product.sellingType
            })
        let currentCartQuantity = (selectedCartItem ?? fallbackItem)?.quantity ?? 0
        if currentCartQuantity >= stockLimit {
            feedbackDialog = .error(
                "Stock insuffisant pour \(viewModel.product.name). Quantité max: \(stockLimit)."
            )
            return
        }

        if let item = selectedCartItem ?? fallbackItem {
            await cart.update(
                item: item,
                quantity: item.quantity + 1,
                rentalMonths: item.rentalMonths ?? rentalMonthsIfNeeded,
                rentalStartDate: item.rentalStartDate ?? rentalStartDateIfNeeded
            )
        } else {
            await cart.add(
                product: viewModel.product,
                quantity: 1,
                rentalMonths: viewModel.product.sellingType == .rental ? rentalMonthsIfNeeded : nil,
                rentalStartDate: viewModel.product.sellingType == .rental ? rentalStartDateIfNeeded : nil,
                presentsFeedback: false
            )
            if let error = cart.error, !error.isEmpty {
                feedbackDialog = .error(error)
                UINotificationFeedbackGenerator().notificationOccurred(.error)
                return
            }
            feedbackDialog = .success(
                "\(viewModel.product.name) a été ajouté au panier."
            )
        }
        UINotificationFeedbackGenerator().notificationOccurred(.success)
    }

    func addCurrentProductToCart() async {
        if stockLimit <= 0 {
            feedbackDialog = .error("Stock insuffisant pour \(viewModel.product.name).")
            return
        }

        if viewModel.product.sellingType == .rental {
            viewModel.closeRentalSheet()
        }

        await cart.add(
            product: viewModel.product,
            rentalMonths: viewModel.product.sellingType == .rental ? rentalMonthsIfNeeded : nil,
            rentalStartDate: viewModel.product.sellingType == .rental ? rentalStartDateIfNeeded : nil,
            presentsFeedback: false
        )

        if let error = cart.error, !error.isEmpty {
            feedbackDialog = .error(error)
            UINotificationFeedbackGenerator().notificationOccurred(.error)
            return
        }

        let successMessage = viewModel.product.sellingType == .rental
            ? "\(viewModel.product.name) a été ajouté au panier en location."
            : "\(viewModel.product.name) a été ajouté au panier."
        feedbackDialog = .success(successMessage)
        UINotificationFeedbackGenerator().notificationOccurred(.success)
    }
}
