import SwiftUI

struct HomeFeaturedProductsSection: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var cart: CartViewModel
    @ObservedObject var home: HomeViewModel

    var body: some View {
        Section("Produits recommandés") {
            if home.isLoading && home.featured.isEmpty {
                ProgressView("Chargement...")
                    .frame(maxWidth: .infinity, alignment: .center)
            } else if home.featured.isEmpty {
                Text("Aucun produit disponible pour le moment.")
                    .foregroundStyle(.secondary)
            } else {
                ForEach(home.featured.prefix(5)) { product in
                    ProductCatalogCard(
                        product: product,
                        imageURL: container.services.assets.assetURL(for: product.imageUrl),
                        cart: cart,
                        selectedTab: .constant(0),
                        isCompact: false
                    )
                    .environmentObject(container)
                }
            }
        }
    }
}
