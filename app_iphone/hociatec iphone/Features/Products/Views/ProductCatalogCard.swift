import SwiftUI

struct ProductCatalogCard: View {
    let product: Product
    let imageURL: URL?
    let cart: CartViewModel
    @Binding var selectedTab: Int
    let isCompact: Bool

    @EnvironmentObject private var container: AppContainer

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
                ProductCatalogCardContent(
                    product: product,
                    imageURL: imageURL,
                    isCompact: isCompact
                )
            }
            .buttonStyle(.plain)
            .accessibilityHint("Afficher le détail du produit")

            ProductCatalogActions(product: product, cart: cart)
        }
        .padding(.vertical, 6)
    }
}
