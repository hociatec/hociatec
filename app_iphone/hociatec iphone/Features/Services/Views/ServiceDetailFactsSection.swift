import SwiftUI

struct ServiceDetailFactsSection: View {
    let service: QuoteService

    var body: some View {
        Section {
            HStack(spacing: 12) {
                ServiceDetailFactCard(
                    title: "Base tarifaire",
                    value: PriceFormatter.format(cents: service.priceCents)
                )
                ServiceDetailFactCard(
                    title: "Facturation",
                    value: serviceBillingModeLabel(service.unit)
                )
            }
            HStack(spacing: 12) {
                ServiceDetailFactCard(
                    title: "Durée estimée",
                    value: service.durationLabel ?? "Sur étude"
                )
                ServiceDetailFactCard(
                    title: "TVA",
                    value: "\(Int(service.vatRate.rounded())) %"
                )
            }
        }
    }
}

private struct ServiceDetailFactCard: View {
    let title: String
    let value: String

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title)
                .font(.caption)
                .foregroundStyle(.secondary)
            Text(value)
                .font(.headline)
                .fontWeight(.semibold)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(14)
        .background(Color(.secondarySystemBackground))
        .clipShape(RoundedRectangle(cornerRadius: 14))
    }
}
