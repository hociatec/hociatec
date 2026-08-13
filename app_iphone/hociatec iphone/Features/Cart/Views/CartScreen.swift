import SwiftUI
import UIKit

struct CartScreen: View {
    @EnvironmentObject private var cart: CartViewModel
    @State private var showingClearConfirm = false
    @State private var itemPendingRemoval: CartItem? = nil

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
                            VStack(alignment: .leading, spacing: 8) {
                                Text(item.product.name).fontWeight(.semibold)
                                if item.product.sellingType == .rental {
                                    let currentMonths = max(1, item.rentalMonths ?? 1)
                                    HStack(spacing: 12) {
                                        Text("Durée")
                                            .font(.subheadline)
                                            .foregroundStyle(.secondary)
                                        Text("\(currentMonths) mois")
                                            .font(.subheadline)
                                            .fontWeight(.semibold)
                                        Spacer()
                                        Button {
                                            guard currentMonths > 1 else { return }
                                            Task {
                                                await cart.update(
                                                    item: item,
                                                    quantity: item.quantity,
                                                    rentalMonths: currentMonths - 1
                                                )
                                            }
                                        } label: {
                                            Image(systemName: "minus").frame(width: 32, height: 32)
                                        }
                                        .buttonStyle(.bordered)
                                        .accessibilityLabel("Réduire la durée de location")
                                        .disabled(cart.isLoading || currentMonths <= 1)

                                        Button {
                                            guard currentMonths < 36 else { return }
                                            Task {
                                                await cart.update(
                                                    item: item,
                                                    quantity: item.quantity,
                                                    rentalMonths: currentMonths + 1
                                                )
                                            }
                                        } label: {
                                            Image(systemName: "plus").frame(width: 32, height: 32)
                                        }
                                        .buttonStyle(.bordered)
                                        .accessibilityLabel("Augmenter la durée de location")
                                        .disabled(cart.isLoading || currentMonths >= 36)
                                    }
                                    .accessibilityElement(children: .combine)
                                    .accessibilityLabel("Durée de location : \(currentMonths) mois")
                                }
                                HStack {
                                    Text(PriceFormatter.format(cents: item.product.effectivePriceCents))
                                    if item.product.sellingType == .rental { Text("/mois").foregroundStyle(.secondary) }
                                    Spacer()
                                    HStack(spacing: 12) {
                                        Button {
                                            if item.quantity <= 1 {
                                                itemPendingRemoval = item
                                            } else {
                                                Task { await cart.update(item: item, quantity: item.quantity - 1) }
                                            }
                                        } label: { Image(systemName: "minus").frame(width: 32, height: 32) }
                                        .buttonStyle(.bordered)
                                        .accessibilityLabel("Moins")

                                        Text("\(item.quantity)")
                                            .fontWeight(.semibold)

                                        Button {
                                            Task {
                                                let newQ = item.quantity + 1
                                                await cart.update(item: item, quantity: newQ)
                                            }
                                        } label: { Image(systemName: "plus").frame(width: 32, height: 32) }
                                        .buttonStyle(.bordered)
                                        .accessibilityLabel("Plus")
                                    }
                                }
                            }
                            .padding(.vertical, 6)
                            .accessibilityElement(children: .contain)
                        }
                    }
                }

                Section {
                    HStack {
                        Text("Total articles")
                        Spacer()
                        Text("\(cartData.totalQuantity)")
                            .accessibilityLabel(cartData.totalQuantity == 1 ? "1 article" : "\(cartData.totalQuantity) articles")
                    }
                    HStack {
                        Text("Total")
                        Spacer()
                        Text(PriceFormatter.format(cents: cartData.totalPriceCents))
                            .fontWeight(.semibold)
                    }
                }

                Section {
                    Button {
                        Task {
                            if let order = await cart.checkout() {
                                let generator = UINotificationFeedbackGenerator(); generator.notificationOccurred(.success)
                                print("Commande créée: \\ (\(order.number))")
                            }
                        }
                    } label: {
                        if cart.isLoading {
                            ProgressView().frame(maxWidth: .infinity)
                        } else {
                            Text("Passer la commande").fontWeight(.semibold).frame(maxWidth: .infinity)
                        }
                    }
                    .disabled(cart.cart?.items.isEmpty ?? true)

                    Button(role: .destructive) {
                        showingClearConfirm = true
                    } label: {
                        Text("Vider le panier").frame(maxWidth: .infinity)
                    }
                    .disabled(cart.cart?.items.isEmpty ?? true)
                }
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
        .alert("Vider le panier ?", isPresented: $showingClearConfirm) {
            Button("Annuler", role: .cancel) { showingClearConfirm = false }
            Button("Vider", role: .destructive) {
                Task { await cart.clear() }
            }
        } message: {
            Text("Cette action supprimera tous les articles de votre panier. Voulez-vous continuer ?")
        }
        .alert("Supprimer cet article ?", isPresented: Binding(
            get: { itemPendingRemoval != nil },
            set: { newVal in if !newVal { itemPendingRemoval = nil } }
        )) {
            Button("Annuler", role: .cancel) { itemPendingRemoval = nil }
            Button("Supprimer", role: .destructive) {
                guard let item = itemPendingRemoval else { return }
                Task { await cart.remove(item: item) }
                itemPendingRemoval = nil
            }
        } message: {
            if let item = itemPendingRemoval {
                Text("Voulez-vous retirer \(item.product.name) du panier ?")
            } else {
                Text("")
            }
        }
        .navigationTitle("Panier")
        .task { await cart.refresh() }
        .refreshable { await cart.refresh() }
        .onChangeCompat(cart.statusMessage) { _ in }
    }
}
