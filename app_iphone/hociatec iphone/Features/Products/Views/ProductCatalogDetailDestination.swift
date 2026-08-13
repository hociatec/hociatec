import SwiftUI

struct ProductCatalogDetailDestination: View {
    let product: Product
    let imageURL: URL?
    let cart: CartViewModel
    @Binding var selectedTab: Int

    @EnvironmentObject private var container: AppContainer

    var body: some View {
        ProductDetailView(
            viewModel: container.makeProductDetailViewModel(product: product),
            imageURL: imageURL,
            cart: cart,
            selectedTab: $selectedTab
        )
        .environmentObject(container)
    }
}
