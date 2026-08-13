import SwiftUI

struct ProductInfoSection: View {
    let product: Product

    var body: some View {
        Section("Informations") {
            LabeledContent("Type", value: product.sellingType.label)
            LabeledContent("Catégorie", value: product.category.name)
            LabeledContent("Référence", value: product.sku)
            LabeledContent("Stock") {
                Text("\(product.stock) disponible(s)")
                    .foregroundColor(product.stock > 0 ? .primary : .red)
            }
            ProductPriceRow(product: product)
            if let createdAt = product.createdAt {
                LabeledContent("Ajouté le", value: DateFormatters.frDay.string(from: createdAt))
            }
            if let updatedAt = product.updatedAt {
                LabeledContent("Mis à jour le", value: DateFormatters.frDay.string(from: updatedAt))
            }
        }
    }
}

struct ProductPriceRow: View {
    let product: Product

    var body: some View {
        HStack(spacing: 4) {
            Text("Prix ")
                .font(.subheadline)
                .foregroundStyle(.secondary)
            Text(PriceFormatter.format(cents: product.effectivePriceCents))
                .font(.title3)
                .fontWeight(.bold)
            if product.sellingType == .rental {
                Text("par mois")
                    .font(.subheadline)
                    .foregroundStyle(.secondary)
            }
            if product.effectivePriceCents < product.priceCents {
                Text(PriceFormatter.format(cents: product.priceCents))
                    .font(.subheadline)
                    .foregroundStyle(.secondary)
                    .strikethrough()
            }
        }
        .lineLimit(1)
        .minimumScaleFactor(0.7)
        .allowsTightening(true)
        .truncationMode(.tail)
    }
}
