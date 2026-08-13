import SwiftUI

struct ProductCatalogActions: View {
    let product: Product
    let cart: CartViewModel
    @Environment(\.openURL) private var openURL

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            Button {
                Task { await cart.add(product: product) }
            } label: {
                Text("Ajouter au panier")
                    .fontWeight(.semibold)
                    .frame(maxWidth: .infinity)
            }
            .buttonStyle(.borderedProminent)

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
