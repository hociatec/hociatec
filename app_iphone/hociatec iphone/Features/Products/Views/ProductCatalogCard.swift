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

            ProductCatalogActions(product: product, cart: cart)
        }
        .padding(.vertical, 6)
    }
}
