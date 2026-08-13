import SwiftUI
import UIKit

struct CartScreen: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var cart: CartViewModel
    @State private var screenState = CartScreenState()
    @State private var completedOrder: OrderSummary?

    var body: some View {
        List {
            if let error = cart.error {
                Section { Text(error).foregroundStyle(.red) }
            }

            if let cartData = cart.cart {
                Section {
                    if cartData.items.isEmpty {
                        Text("Votre panier est vide.").foregroundStyle(.secondary)
                    } else {
                        ForEach(cartData.items, id: \.product.id) { item in
                            CartItemRow(
                                item: item,
                                isLoading: cart.isLoading,
                                decreaseRentalMonths: {
                                    let currentMonths = max(1, item.rentalMonths ?? 1)
                                    guard currentMonths > 1 else { return }
                                    Task {
                                        await cart.update(
                                            item: item,
                                            quantity: item.quantity,
                                            rentalMonths: currentMonths - 1
                                        )
                                    }
                                },
                                increaseRentalMonths: {
                                    let currentMonths = max(1, item.rentalMonths ?? 1)
                                    guard currentMonths < 36 else { return }
                                    Task {
                                        await cart.update(
                                            item: item,
                                            quantity: item.quantity,
                                            rentalMonths: currentMonths + 1
                                        )
                                    }
                                },
                                decreaseQuantity: {
                                    if item.quantity <= 1 {
                                        screenState.itemPendingRemoval = item
                                    } else {
                                        Task { await cart.update(item: item, quantity: item.quantity - 1) }
                                    }
                                },
                                increaseQuantity: {
                                    Task {
                                        await cart.update(item: item, quantity: item.quantity + 1)
                                    }
                                }
                            )
                        }
                    }
                }

                CartSummarySection(cart: cartData)

                CartActionsSection(
                    isLoading: cart.isLoading,
                    isEmpty: cart.cart?.items.isEmpty ?? true,
                    checkout: {
                        Task {
                            if let order = await cart.checkout() {
                                let generator = UINotificationFeedbackGenerator()
                                generator.notificationOccurred(.success)
                                completedOrder = order
                            }
                        }
                    },
                    clear: {
                        screenState.showingClearConfirm = true
                    }
                )
            } else {
                Section {
                    if cart.isLoading {
                        ProgressView("Chargement du panier...")
                    } else {
                        Text("Votre panier est vide.").foregroundStyle(.secondary)
                    }
                }
            }
        }
        .alert("Vider le panier ?", isPresented: $screenState.showingClearConfirm) {
            Button("Annuler", role: .cancel) { screenState.showingClearConfirm = false }
            Button("Vider", role: .destructive) {
                Task { await cart.clear() }
            }
        } message: {
            Text("Cette action supprimera tous les articles de votre panier. Voulez-vous continuer ?")
        }
        .alert("Supprimer cet article ?", isPresented: Binding(
            get: { screenState.isShowingRemovalAlert },
            set: { newVal in if !newVal { screenState.itemPendingRemoval = nil } }
        )) {
            Button("Annuler", role: .cancel) { screenState.itemPendingRemoval = nil }
            Button("Supprimer", role: .destructive) {
                guard let item = screenState.itemPendingRemoval else { return }
                Task { await cart.remove(item: item) }
                screenState.itemPendingRemoval = nil
            }
        } message: {
            if let item = screenState.itemPendingRemoval {
                Text("Voulez-vous retirer \(item.product.name) du panier ?")
            } else {
                Text("")
            }
        }
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
}
