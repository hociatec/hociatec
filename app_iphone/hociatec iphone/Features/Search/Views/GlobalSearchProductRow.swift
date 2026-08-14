import SwiftUI
import UIKit

struct GlobalSearchProductRow: View {
    let product: Product
    var showsTitle: Bool = true
    @EnvironmentObject private var cart: CartViewModel
    @State private var alertState = ProductDetailAlertState()

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            if showsTitle {
                Text(product.name)
                    .fontWeight(.semibold)
                    .accessibilityAddTraits(.isHeader)
            }
            Text("Référence : \(product.sku)")
                .font(.footnote)
                .foregroundStyle(.secondary)
            Text("Type : \(productSellingContext(product))")
                .font(.footnote)
                .foregroundStyle(.secondary)
            if let configuration = productConfiguration(product) {
                Text("Configuration : \(configuration)")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
            Text(product.shortDescription)
                .font(.footnote)
                .foregroundStyle(.secondary)
                .lineLimit(2)
            Text(PriceFormatter.format(cents: product.priceCents))
                .font(.footnote.weight(.semibold))

            ProductCatalogActions(
                product: product,
                cart: cart,
                addToCart: {
                    Task { await addCurrentProductToCart() }
                }
            )
        }
        .accessibilityElement(children: .contain)
        .alert("Ajout au panier", isPresented: $alertState.showAddAlert) {
            Button("OK", role: .cancel) {}
        } message: {
            Text("\(alertState.addedProductName) a été ajouté au panier.")
        }
        .alert("Ajout impossible", isPresented: $alertState.showStockAlert) {
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
