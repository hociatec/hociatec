import SwiftUI

struct HomeFeaturedProductsSection: View {
    @EnvironmentObject private var container: AppContainer
    @ObservedObject var home: HomeViewModel

    var body: some View {
        Section("Produits en vedette") {
            if home.isLoading && home.featured.isEmpty {
                ProgressView("Chargement...")
                    .frame(maxWidth: .infinity, alignment: .center)
            } else if let error = home.error {
                Text(error)
                    .foregroundStyle(.red)
            } else if home.featured.isEmpty {
                Text("Aucun produit disponible pour le moment.")
                    .foregroundStyle(.secondary)
            } else {
                ForEach(home.featured.prefix(5)) { product in
                    NavigationLink {
                        ProductDetailView(
                            viewModel: container.makeProductDetailViewModel(product: product),
                            imageURL: container.services.assets.assetURL(for: product.imageUrl),
                            cart: container.cart,
                            selectedTab: .constant(0)
                        )
                        .environmentObject(container)
                    } label: {
                        VStack(alignment: .leading, spacing: 4) {
                            Text(product.name)
                                .fontWeight(.semibold)
                            Text(product.shortDescription)
                                .lineLimit(2)
                                .foregroundStyle(.secondary)
                        }
                        .accessibilityElement(children: .ignore)
                        .accessibilityLabel("Produit: \(product.name)")
                        .accessibilityHint("Afficher le détail du produit")
                    }
                }
            }
        }
    }
}
