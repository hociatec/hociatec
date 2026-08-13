import SwiftUI

struct ProductCatalogActions: View {
    let product: Product
    let cart: CartViewModel

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
                Link(destination: facebookShareURL(for: product)) {
                    Label("Partager sur Facebook", systemImage: "square.and.arrow.up")
                        .font(.footnote)
                }

                Link(destination: emailShareURL(for: product)) {
                    Label("Partager par e-mail", systemImage: "envelope")
                        .font(.footnote)
                }
            }
            .foregroundStyle(.blue)
        }
    }
}
