import SwiftUI

struct GlobalSearchServiceRow: View {
    let service: QuoteService

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(service.title)
                .fontWeight(.semibold)
            Text(service.description ?? "")
                .font(.footnote)
                .foregroundStyle(.secondary)
                .lineLimit(2)
            Text(PriceFormatter.format(cents: service.priceCents))
                .font(.footnote.weight(.semibold))
        }
        .padding(.vertical, 4)
    }
}
