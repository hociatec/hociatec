import SwiftUI

struct GlobalSearchServiceRow: View {
    let service: QuoteService
    var showsTitle: Bool = true

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            if showsTitle {
                Text(service.title)
                    .fontWeight(.semibold)
                    .accessibilityAddTraits(.isHeader)
            }
            Text(service.description ?? "")
                .font(.footnote)
                .foregroundStyle(.secondary)
                .lineLimit(2)
            Text("Mode de facturation : \(serviceBillingModeLabel(service.unit))")
                .font(.footnote)
                .foregroundStyle(.secondary)
            Text("Prix HT : \(PriceFormatter.format(cents: service.priceCents))")
                .font(.footnote.weight(.semibold))
            Text("Durée : \(service.durationLabel ?? "Sur étude")")
                .font(.footnote)
                .foregroundStyle(.secondary)
        }
        .accessibilityElement(children: .contain)
    }
}
