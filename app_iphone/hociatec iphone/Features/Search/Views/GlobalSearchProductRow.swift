import SwiftUI

struct GlobalSearchProductRow: View {
    let product: Product
    var showsTitle: Bool = true
    @EnvironmentObject private var cart: CartViewModel

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            if showsTitle {
                Text(product.name)
                    .fontWeight(.semibold)
                    .accessibilityAddTraits(.isHeader)
            }
            Text("Référence : \(product.sku)")
                .font(.footnote)
                .foregroundStyle(.secondary)
            Text("Type : \(productSellingContext(product))")
                .font(.footnote)
                .foregroundStyle(.secondary)
            if let configuration = productConfiguration(product) {
                Text("Configuration : \(configuration)")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
            Text(product.shortDescription)
                .font(.footnote)
                .foregroundStyle(.secondary)
                .lineLimit(2)
            Text(PriceFormatter.format(cents: product.priceCents))
                .font(.footnote.weight(.semibold))

            ProductCatalogActions(product: product, cart: cart)
        }
        .accessibilityElement(children: .contain)
    }
}
