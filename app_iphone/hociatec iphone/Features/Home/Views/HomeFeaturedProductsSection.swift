import SwiftUI

struct HomeFeaturedProductsSection: View {
    @EnvironmentObject private var container: AppContainer
    @ObservedObject var home: HomeViewModel

    var body: some View {
        Section("Produits recommandés") {
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
                    VStack(alignment: .leading, spacing: 4) {
                        NavigationLink {
                            ProductDetailView(
                                viewModel: container.makeProductDetailViewModel(product: product),
                                imageURL: container.services.assets.assetURL(for: product.imageUrl),
                                cart: container.cart,
                                selectedTab: .constant(0)
                            )
                            .environmentObject(container)
                        } label: {
                            Text(product.name)
                                .fontWeight(.semibold)
                                .multilineTextAlignment(.leading)
                                .frame(maxWidth: .infinity, alignment: .leading)
                        }
                        .buttonStyle(.plain)
                        .accessibilityAddTraits(.isHeader)
                        .accessibilityHint("Afficher le détail du produit")

                        Text(product.shortDescription)
                            .lineLimit(2)
                            .foregroundStyle(.secondary)
                    }
                    .padding(.vertical, 4)
                    .accessibilityElement(children: .contain)
                }
            }
        }
    }
}
