import SwiftUI

struct GlobalSearchProductRow: View {
    let product: Product

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(product.name)
                .fontWeight(.semibold)
            Text(product.shortDescription)
                .font(.footnote)
                .foregroundStyle(.secondary)
                .lineLimit(2)
            Text(PriceFormatter.format(cents: product.priceCents))
                .font(.footnote.weight(.semibold))
        }
        .padding(.vertical, 4)
    }
}
