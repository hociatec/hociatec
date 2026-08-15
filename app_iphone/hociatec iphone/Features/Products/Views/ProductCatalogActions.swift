import SwiftUI

struct ProductCatalogActions: View {
    let product: Product
    let cart: CartViewModel
    let addToCart: () -> Void
    let configureRental: () -> Void
    var onFavoriteRemoved: (() -> Void)? = nil
    @Environment(\.openURL) private var openURL

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            ProductAddToCartButton(
                isLoading: cart.isLoading,
                isDisabled: false,
                label: product.sellingType == .rental ? "Configurer la location" : "Ajouter au panier"
            ) {
                product.sellingType == .rental ? configureRental() : addToCart()
            }

            HStack(spacing: 12) {
                FavoriteToggleButton(
                    category: .product,
                    targetId: product.id,
                    onRemoved: onFavoriteRemoved
                )

                Button {
                    openURL(emailShareURL(for: product))
                } label: {
                    Label("Partager par e-mail", systemImage: "envelope")
                        .font(.footnote)
                }
                .buttonStyle(.borderless)

                Button {
                    openURL(facebookShareURL(for: product))
                } label: {
                    Label("Partager sur Facebook", systemImage: "square.and.arrow.up")
                        .font(.footnote)
                }
                .buttonStyle(.borderless)
            }
            .foregroundStyle(.blue)
        }
    }
}
