import SwiftUI

@MainActor
struct FavoritesScreen: View {
    @EnvironmentObject private var container: AppContainer
    @StateObject private var viewModel: FavoritesViewModel

    init(service: FavoritesServing) {
        _viewModel = StateObject(wrappedValue: FavoritesViewModel(service: service))
    }

    var body: some View {
        List {
            if let error = viewModel.error {
                Section { Text(error).foregroundStyle(.red) }
            }

            if viewModel.isLoading && viewModel.items.isEmpty {
                Section { ProgressView("Chargement...") }
            } else if viewModel.items.isEmpty {
                Section { Text("Aucun favori enregistré.").foregroundStyle(.secondary) }
            } else {
                Section {
                    ForEach(viewModel.items) { product in
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
                                Text(product.name).fontWeight(.semibold)
                                Text(product.shortDescription)
                                    .font(.footnote)
                                    .foregroundStyle(.secondary)
                                    .lineLimit(2)
                            }
                        }
                    }
                }
            }
        }
        .navigationTitle("Mes favoris")
        .task { await viewModel.load() }
        .refreshable { await viewModel.load() }
    }
}
