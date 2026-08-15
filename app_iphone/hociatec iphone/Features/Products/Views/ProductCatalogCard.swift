import SwiftUI
import UIKit

struct ProductCatalogCard: View {
    let product: Product
    let imageURL: URL?
    let cart: CartViewModel
    @Binding var selectedTab: Int
    let isCompact: Bool
    var onFavoriteRemoved: (() -> Void)? = nil

    @EnvironmentObject private var container: AppContainer
    @State private var alertState = ProductDetailAlertState()
    @State private var showDetail = false

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            NavigationLink {
                ProductCatalogDetailDestination(
                    product: product,
                    imageURL: imageURL,
                    cart: cart,
                    selectedTab: $selectedTab
                )
                .environmentObject(container)
            } label: {
                Text(product.name)
                    .font(isCompact ? .subheadline.weight(.semibold) : .headline)
                    .multilineTextAlignment(.leading)
                    .frame(maxWidth: .infinity, alignment: .leading)
            }
            .buttonStyle(.plain)
            .accessibilityHint("Afficher le détail du produit")
            .accessibilityAddTraits(.isHeader)

            ProductCatalogCardContent(
                product: product,
                imageURL: imageURL,
                isCompact: isCompact,
                showsTitle: false
            )

            ProductCatalogActions(
                product: product,
                cart: cart,
                addToCart: {
                    Task { await addCurrentProductToCart() }
                },
                configureRental: {
                    showDetail = true
                },
                onFavoriteRemoved: onFavoriteRemoved
            )
        }
        .navigationDestination(isPresented: $showDetail) {
            ProductCatalogDetailDestination(
                product: product,
                imageURL: imageURL,
                cart: cart,
                selectedTab: $selectedTab
            )
            .environmentObject(container)
        }
        .padding(.vertical, 6)
        .alert("Ajout au panier", isPresented: $alertState.showAddAlert) {
            Button("Continuer", role: .cancel) {}
            Button("Voir le panier") {
                selectedTab = 2
            }
        } message: {
            Text("\(alertState.addedProductName) a été ajouté au panier.")
        }
        .alert("Stock insuffisant", isPresented: $alertState.showStockAlert) {
            Button("OK", role: .cancel) {}
        } message: {
            Text(alertState.stockAlertMessage)
        }
    }

    private func addCurrentProductToCart() async {
        await cart.add(product: product)

        if let error = cart.error, !error.isEmpty {
            alertState.presentStock(message: error)
            UINotificationFeedbackGenerator().notificationOccurred(.error)
            return
        }

        alertState.presentAddConfirmation(productName: product.name)
        UINotificationFeedbackGenerator().notificationOccurred(.success)
    }
}
