import SwiftUI

struct ProductInfoSection: View {
    let product: Product

    var body: some View {
        Section("Informations") {
            LabeledContent("Référence", value: product.sku)
            LabeledContent("Type", value: productSellingContext(product))
            if let configuration = productConfiguration(product) {
                LabeledContent("Configuration", value: configuration)
            }
            if let unitLabel = nonEmptyValue(product.priceUnitLabel), product.sellingType == .sale {
                LabeledContent("Unité de prix", value: unitLabel)
            }
            if let variantColors = product.variantColors, !variantColors.isEmpty {
                LabeledContent("Coloris", value: variantColors.joined(separator: ", "))
            }
            if let variantStorages = product.variantStorages, !variantStorages.isEmpty {
                LabeledContent("Stockages", value: variantStorages.joined(separator: ", "))
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
