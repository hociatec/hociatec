import SwiftUI
import UIKit

struct CartScreen: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var cart: CartViewModel
    @State private var screenState = CartScreenState()
    @State private var completedOrder: OrderSummary?

    var body: some View {
        List {
            if let cartData = cart.cart {
                CartErrorSection(error: cart.error)
                CartItemsSection(
                    cartData: cartData,
                    isLoading: cart.isLoading,
                    onRemoveRequest: { screenState.itemPendingRemoval = $0 },
                    onDecreaseQuantity: decreaseQuantity,
                    onIncreaseQuantity: increaseQuantity,
                    onDecreaseRentalMonths: decreaseRentalMonths,
                    onIncreaseRentalMonths: increaseRentalMonths
                )
                CartSummarySection(cart: cartData)
                CartActionsSection(
                    isLoading: cart.isLoading,
                    isEmpty: cart.cart?.items.isEmpty ?? true,
                    checkout: { Task { await checkout() } },
                    clear: {
                        screenState.showingClearConfirm = true
                    }
                )
            } else {
                CartEmptyStateSection(isLoading: cart.isLoading)
            }
        }
        .cartScreenAlerts(screenState: $screenState, cart: cart)
        .navigationTitle("Panier")
        .navigationDestination(
            isPresented: Binding(
                get: { completedOrder != nil },
                set: { newValue in
                    if !newValue {
                        completedOrder = nil
                    }
                }
            )
        ) {
            if let order = completedOrder {
                CheckoutSuccessView(order: order, orderService: container.services.orders)
            }
        }
        .task { await cart.refresh() }
        .refreshable { await cart.refresh() }
        .onChangeCompat(cart.statusMessage) { _ in }
    }

    private func checkout() async {
        if let order = await cart.checkout() {
            UINotificationFeedbackGenerator().notificationOccurred(.success)
            completedOrder = order
        }
    }

    private func decreaseRentalMonths(for item: CartItem) async {
        let currentMonths = max(1, item.rentalMonths ?? 1)
        guard currentMonths > 1 else { return }
        await cart.update(
            item: item,
            quantity: item.quantity,
            rentalMonths: currentMonths - 1
        )
    }

    private func increaseRentalMonths(for item: CartItem) async {
        let currentMonths = max(1, item.rentalMonths ?? 1)
        guard currentMonths < 36 else { return }
        await cart.update(
            item: item,
            quantity: item.quantity,
            rentalMonths: currentMonths + 1
        )
    }

    private func decreaseQuantity(for item: CartItem) async {
        if item.quantity <= 1 {
            screenState.itemPendingRemoval = item
        } else {
            await cart.update(item: item, quantity: item.quantity - 1)
        }
    }

    private func increaseQuantity(for item: CartItem) async {
        await cart.update(item: item, quantity: item.quantity + 1)
    }
}
