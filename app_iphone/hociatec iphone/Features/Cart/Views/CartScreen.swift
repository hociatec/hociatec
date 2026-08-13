import SwiftUI
import UIKit

struct CartScreen: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var cart: CartViewModel
    @EnvironmentObject private var account: AccountViewModel
    @Environment(\.openURL) private var openURL
    @Environment(\.scenePhase) private var scenePhase
    @State private var screenState = CartScreenState()
    @State private var completedOrder: OrderSummary?

    var body: some View {
        List {
            if let cartData = cart.cart {
                CartErrorSection(error: cart.error)
                CartStatusSection(
                    message: screenState.checkoutStatusMessage ?? cart.statusMessage,
                    isLoading: screenState.isCheckingCheckoutStatus
                )
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
                    canCheckout: canCheckout,
                    showsAddressRequirement: needsDefaultAddress,
                    checkout: { Task { await checkout() } },
                    clear: {
                        screenState.showingClearConfirm = true
                    },
                    addressesDestination: AnyView(AddressesManagerView(account: account))
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
        .task {
            await loadScreenData()
            if let sessionId = container.session.pendingCheckoutSessionId {
                screenState.pendingCheckoutSessionId = sessionId
                screenState.checkoutStatusMessage = "Vérification d’un paiement en attente..."
                await verifyPendingCheckoutIfNeeded()
            }
        }
        .refreshable {
            await loadScreenData()
        }
        .onChangeCompat(account.isLoggedIn) { isLoggedIn in
            Task {
                if isLoggedIn {
                    await account.loadAddresses()
                }
                await cart.refresh()
            }
        }
        .onChangeCompat(cart.statusMessage) { _ in }
        .onChangeCompat(container.session.checkoutCallback) { _ in
            Task { await handleCheckoutCallback() }
        }
        .onChangeCompat(scenePhase) { phase in
            guard phase == .active else { return }
            guard screenState.pendingCheckoutSessionId != nil else { return }
            Task { await verifyPendingCheckoutIfNeeded() }
        }
    }

    private var canCheckout: Bool {
        account.isLoggedIn && hasDefaultAddress && !(cart.cart?.items.isEmpty ?? true) && !cart.isLoading
    }

    private var hasDefaultAddress: Bool {
        account.addresses.contains(where: \.isDefault)
    }

    private var needsDefaultAddress: Bool {
        account.isLoggedIn && !(cart.cart?.items.isEmpty ?? true) && !hasDefaultAddress
    }

    private func checkout() async {
        guard canCheckout else { return }
        if let result = await cart.checkout() {
            UINotificationFeedbackGenerator().notificationOccurred(.success)
            if let order = result.order {
                screenState.pendingCheckoutSessionId = nil
                container.session.pendingCheckoutSessionId = nil
                screenState.checkoutStatusMessage = nil
                completedOrder = order
            } else if let checkoutURL = result.checkoutURL {
                screenState.pendingCheckoutSessionId = result.checkoutSessionId
                container.session.pendingCheckoutSessionId = result.checkoutSessionId
                screenState.checkoutStatusMessage = "Paiement en cours. Revenez dans l’app après validation pour finaliser la commande."
                openURL(checkoutURL)
            }
        }
    }

    private func verifyPendingCheckoutIfNeeded() async {
        guard !screenState.isCheckingCheckoutStatus else { return }
        guard let sessionId = screenState.pendingCheckoutSessionId else { return }

        screenState.isCheckingCheckoutStatus = true
        cart.error = nil
        defer { screenState.isCheckingCheckoutStatus = false }

        for attempt in 1...20 {
            do {
                let status = try await container.services.orders.checkoutSessionStatus(stripeSessionId: sessionId)

                if status.status == "paid" {
                    let order: OrderSummary?
                    if let existingOrder = status.order {
                        order = existingOrder
                    } else if let orderId = status.orderId {
                        order = try await container.services.orders.order(id: orderId)
                    } else {
                        order = nil
                    }

                    if let order {
                        await cart.refresh()
                        screenState.pendingCheckoutSessionId = nil
                        container.session.pendingCheckoutSessionId = nil
                        screenState.checkoutStatusMessage = nil
                        completedOrder = order
                        return
                    }
                }

                if status.status == "failed" || status.status == "expired" {
                    screenState.pendingCheckoutSessionId = nil
                    container.session.pendingCheckoutSessionId = nil
                    screenState.checkoutStatusMessage = nil
                    cart.error = status.status == "expired"
                        ? "Le paiement a expiré. Vous pouvez relancer la validation depuis le panier."
                        : "Le paiement a échoué. Vérifiez votre moyen de paiement puis réessayez."
                    return
                }

                screenState.checkoutStatusMessage = attempt >= 20
                    ? "Le paiement n'est pas encore finalisé. Revenez dans quelques secondes ou relancez la validation si vous avez interrompu le paiement."
                    : "Confirmation du paiement en cours..."
            } catch {
                if attempt >= 20 {
                    cart.error = error.localizedDescription
                    return
                }
            }

            try? await Task.sleep(nanoseconds: 2_000_000_000)
        }
    }

    private func handleCheckoutCallback() async {
        guard let callback = container.session.consumeCheckoutCallback() else { return }

        switch callback {
        case let .success(sessionId):
            screenState.pendingCheckoutSessionId = sessionId
            container.session.pendingCheckoutSessionId = sessionId
            screenState.checkoutStatusMessage = "Confirmation du paiement en cours..."
            await verifyPendingCheckoutIfNeeded()
        case .cancelled:
            if let sessionId = screenState.pendingCheckoutSessionId ?? container.session.pendingCheckoutSessionId {
                _ = try? await container.services.orders.cancelCheckoutSession(stripeSessionId: sessionId)
            }
            screenState.pendingCheckoutSessionId = nil
            container.session.pendingCheckoutSessionId = nil
            screenState.checkoutStatusMessage = nil
            cart.error = "Le paiement a été annulé. Vous pouvez reprendre la validation quand vous voulez."
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

    private func loadScreenData() async {
        if account.isLoggedIn {
            await account.loadAddresses()
        }
        await cart.refresh()
    }
}
