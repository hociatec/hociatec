import SwiftUI

struct ProductsListContentSection: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var cart: CartViewModel

    @ObservedObject var viewModel: ProductsViewModel
    @Binding var selectedTab: Int
    let useGrid: Bool

    var body: some View {
        Section {
            if viewModel.isLoading && viewModel.products.isEmpty {
                loadingContent
            } else {
                productContent
            }
        }
    }

    @ViewBuilder
    private var loadingContent: some View {
        if useGrid {
            let columns = [GridItem(.flexible()), GridItem(.flexible())]
            LazyVGrid(columns: columns, spacing: 12) {
                ForEach(0..<6, id: \.self) { _ in ShimmerTile() }
            }
            .listRowInsets(EdgeInsets())
        } else {
            VStack(spacing: 12) {
                ForEach(0..<6, id: \.self) { _ in ShimmerRow() }
            }
        }
    }

    @ViewBuilder
    private var productContent: some View {
        if useGrid {
            let columns = [GridItem(.flexible()), GridItem(.flexible())]
            LazyVGrid(columns: columns, spacing: 12) {
                ForEach(viewModel.products) { product in
                    productCard(product, isCompact: true)
                }
            }
            .listRowInsets(EdgeInsets())
        } else {
            ForEach(viewModel.products) { product in
                productCard(product, isCompact: false)
            }
        }
    }

    private func productCard(_ product: Product, isCompact: Bool) -> some View {
        ProductCatalogCard(
            product: product,
            imageURL: container.services.assets.assetURL(for: product.imageUrl),
            cart: cart,
            selectedTab: $selectedTab,
            isCompact: isCompact
        )
        .environmentObject(container)
    }
}
