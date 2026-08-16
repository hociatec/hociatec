import SwiftUI

struct GlobalSearchProductsSection: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var cart: CartViewModel

    @ObservedObject var viewModel: GlobalSearchViewModel

    var body: some View {
        Section {
            if viewModel.products.isEmpty {
                GlobalSearchEmptyRow(message: "Aucun résultat produit.")
            } else {
                ForEach(viewModel.products) { product in
                    VStack(alignment: .leading, spacing: 6) {
                        Text(product.name)
                            .fontWeight(.semibold)
                            .multilineTextAlignment(.leading)
                            .frame(maxWidth: .infinity, alignment: .leading)
                            .accessibilityAddTraits(.isHeader)

                        GlobalSearchProductRow(product: product, showsTitle: false)
                        NavigationLink {
                            ProductDetailView(
                                viewModel: container.makeProductDetailViewModel(product: product),
                                imageURL: container.services.assets.assetURL(for: product.imageUrl),
                                cart: cart,
                                selectedTab: .constant(0)
                            )
                            .environmentObject(container)
                        } label: {
                            Label("Voir le détail", systemImage: "arrow.right.circle")
                                .font(.footnote.weight(.semibold))
                        }
                        .buttonStyle(.borderless)
                        .accessibilityHint("Afficher le détail du produit")
                    }
                    .padding(.vertical, 4)
                    .accessibilityElement(children: .contain)
                }
            }
        } header: {
            GlobalSearchResultHeader(title: "Produits", total: viewModel.productTotal, query: viewModel.query) {
                ProductsListView(
                    viewModel: container.makeProductsViewModel(),
                    selectedTab: .constant(1),
                    filtersBadge: .constant(nil),
                    navigationTitle: "Produits",
                    initialSearch: viewModel.query
                )
            }
        } footer: {
            GlobalSearchPaginationSection(viewModel: viewModel, filter: .products)
        }
    }
}
