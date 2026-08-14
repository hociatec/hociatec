import SwiftUI

struct CartItemsSection: View {
    let cartData: Cart
    let isLoading: Bool
    let onRemoveRequest: (CartItem) -> Void
    let onDecreaseQuantity: (CartItem) async -> Void
    let onIncreaseQuantity: (CartItem) async -> Void
    let onDecreaseRentalMonths: (CartItem) async -> Void
    let onIncreaseRentalMonths: (CartItem) async -> Void

    var body: some View {
        Section {
            if cartData.items.isEmpty {
                Text("Votre panier est vide.")
                    .foregroundStyle(.secondary)
            } else {
                ForEach(cartData.items, id: \.product.id) { item in
                    CartItemRow(
                        item: item,
                        isLoading: isLoading,
                        decreaseRentalMonths: {
                            Task { await onDecreaseRentalMonths(item) }
                        },
                        increaseRentalMonths: {
                            Task { await onIncreaseRentalMonths(item) }
                        },
                        decreaseQuantity: {
                            if item.quantity <= 1 {
                                onRemoveRequest(item)
                            } else {
                                Task { await onDecreaseQuantity(item) }
                            }
                        },
                        increaseQuantity: {
                            Task { await onIncreaseQuantity(item) }
                        }
                    )
                }
            }
        }
    }
}

struct CartEmptyStateSection: View {
    let isLoading: Bool

    var body: some View {
        Section {
            if isLoading {
                ProgressView("Chargement du panier...")
            } else {
                Text("Votre panier est vide.")
                    .foregroundStyle(.secondary)
            }
        }
    }
}
