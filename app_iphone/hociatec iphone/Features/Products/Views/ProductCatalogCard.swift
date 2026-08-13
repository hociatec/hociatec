import SwiftUI

struct ProductCatalogCard: View {
    let product: Product
    let imageURL: URL?
    let cart: CartViewModel
    @Binding var selectedTab: Int
    let isCompact: Bool

    @EnvironmentObject private var container: AppContainer

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            NavigationLink {
                ProductDetailView(
                    viewModel: container.makeProductDetailViewModel(product: product),
                    imageURL: imageURL,
                    cart: cart,
                    selectedTab: $selectedTab
                )
                    .environmentObject(container)
            } label: {
                VStack(alignment: .leading, spacing: 12) {
                    ProductCatalogImage(imageURL: imageURL, height: isCompact ? 140 : 180)

                    VStack(alignment: .leading, spacing: 8) {
                        Text(product.name)
                            .font(isCompact ? .subheadline.weight(.semibold) : .headline)
                            .lineLimit(2)
                            .multilineTextAlignment(.leading)

                        VStack(alignment: .leading, spacing: 4) {
                            ProductFactLine(label: "Référence", value: product.sku)
                            ProductFactLine(label: "Type", value: productSellingContext(product))
                            if let configuration = productConfiguration(product) {
                                ProductFactLine(label: "Configuration", value: configuration)
                            }
                        }
                        .font(.footnote)
                        .foregroundStyle(.secondary)

                        if !product.shortDescription.isEmpty {
                            Text(product.shortDescription)
                                .font(.footnote)
                                .foregroundStyle(.secondary)
                                .lineLimit(isCompact ? 3 : 4)
                        }

                        Text(productPriceLabel(product))
                            .font(.title3.weight(.bold))
                            .foregroundStyle(.primary)
                    }
                }
            }
            .buttonStyle(.plain)
            .accessibilityHint("Afficher le détail du produit")

            ProductCatalogActions(product: product, cart: cart)
        }
        .padding(.vertical, 6)
    }
}

private struct ProductCatalogImage: View {
    let imageURL: URL?
    let height: CGFloat

    var body: some View {
        AsyncImage(url: imageURL) { phase in
            switch phase {
            case .success(let image):
                image
                    .resizable()
                    .scaledToFill()
                    .frame(height: height)
                    .frame(maxWidth: .infinity)
                    .clipped()
                    .cornerRadius(12)
            case .failure:
                RoundedRectangle(cornerRadius: 12)
                    .fill(.gray.opacity(0.1))
                    .frame(height: height)
                    .overlay(Image(systemName: "photo").foregroundStyle(.secondary))
            default:
                RoundedRectangle(cornerRadius: 12)
                    .fill(.gray.opacity(0.08))
                    .frame(height: height)
                    .overlay(ProgressView())
            }
        }
        .accessibilityHidden(true)
    }
}

private struct ProductCatalogActions: View {
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

private struct ProductFactLine: View {
    let label: String
    let value: String

    var body: some View {
        Text("\(label): \(value)")
            .multilineTextAlignment(.leading)
    }
}
