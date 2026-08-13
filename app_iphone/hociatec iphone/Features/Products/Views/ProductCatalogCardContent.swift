import SwiftUI

struct ProductCatalogCardContent: View {
    let product: Product
    let imageURL: URL?
    let isCompact: Bool

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            ProductCatalogImage(imageURL: imageURL, height: isCompact ? 140 : 180)

            VStack(alignment: .leading, spacing: 8) {
                Text(product.name)
                    .font(isCompact ? .subheadline.weight(.semibold) : .headline)
                    .lineLimit(2)
                    .multilineTextAlignment(.leading)

                ProductCatalogFactGroup(product: product)

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
}

private struct ProductCatalogFactGroup: View {
    let product: Product

    var body: some View {
        VStack(alignment: .leading, spacing: 4) {
            ProductFactLine(label: "Référence", value: product.sku)
            ProductFactLine(label: "Type", value: productSellingContext(product))
            if let configuration = productConfiguration(product) {
                ProductFactLine(label: "Configuration", value: configuration)
            }
        }
        .font(.footnote)
        .foregroundStyle(.secondary)
    }
}
