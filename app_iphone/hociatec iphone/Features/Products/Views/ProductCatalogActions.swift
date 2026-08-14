import SwiftUI

struct ProductCatalogActions: View {
    let product: Product
    let cart: CartViewModel
    let addToCart: () -> Void
    @Environment(\.openURL) private var openURL

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            ProductAddToCartButton(
                isLoading: cart.isLoading,
                isDisabled: false
            ) { addToCart() }

            HStack(spacing: 12) {
                Button {
                    openURL(facebookShareURL(for: product))
                } label: {
                    Label("Partager sur Facebook", systemImage: "square.and.arrow.up")
                        .font(.footnote)
                }

                Button {
                    openURL(emailShareURL(for: product))
                } label: {
                    Label("Partager par e-mail", systemImage: "envelope")
                        .font(.footnote)
                }
            }
            .foregroundStyle(.blue)
        }
    }
}
