import SwiftUI

struct QuoteRow: View {
    let quote: QuoteSummary

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            HStack {
                Text(quote.number ?? "Devis #\(quote.id)")
                    .fontWeight(.semibold)
                Spacer()
                Text(quote.statusLabel)
                    .font(.caption2)
                    .padding(.horizontal, 8)
                    .padding(.vertical, 4)
                    .background(Color.gray.opacity(0.12))
                    .clipShape(Capsule())
                    .foregroundStyle(.secondary)
            }
            HStack {
                Text("Créé le \(QuotePresentation.dateFormatter.string(from: quote.createdAt))")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
                Spacer()
                Text(PriceFormatter.format(cents: quote.totals.ttc))
                    .fontWeight(.semibold)
            }
        }
        .padding(.vertical, 4)
    }
}
