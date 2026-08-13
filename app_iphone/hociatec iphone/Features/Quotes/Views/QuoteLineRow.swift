import SwiftUI

struct QuoteLineRow: View {
    let item: QuoteDraftItem

    private var badge: String {
        if item.serviceId != nil { return "Service" }
        if item.productId != nil { return "Produit" }
        return "Libre"
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            HStack(alignment: .firstTextBaseline, spacing: 8) {
                Text(item.displayTitle)
                    .fontWeight(.semibold)
                    .lineLimit(1)
                Text(badge)
                    .font(.caption2)
                    .padding(.horizontal, 6)
                    .padding(.vertical, 2)
                    .background(Color.blue.opacity(0.1))
                    .foregroundColor(.blue)
                    .clipShape(Capsule())
            }

            HStack {
                Text("\(item.quantity) × \(PriceFormatter.format(cents: item.unitPriceCents ?? 0))")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
                Spacer()
                Text(PriceFormatter.format(cents: item.lineTotalCents))
                    .fontWeight(.semibold)
            }

            if !item.description.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
                Text(item.description)
                    .font(.footnote)
                    .foregroundStyle(.secondary)
                    .lineLimit(2)
            }
        }
    }
}
