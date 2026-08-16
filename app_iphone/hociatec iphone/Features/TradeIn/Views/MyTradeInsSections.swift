import SwiftUI

struct TradeInListSection: View {
    @ObservedObject var viewModel: MyTradeInsViewModel

    var body: some View {
        Section("Mes reprises") {
            if viewModel.isLoading && viewModel.items.isEmpty {
                ProgressView("Chargement...")
            } else if viewModel.items.isEmpty {
                Text("Aucune demande de reprise pour le moment.")
                    .foregroundStyle(.secondary)
            } else {
                ForEach(viewModel.items) { item in
                    TradeInRow(item: item, viewModel: viewModel)
                }

                if viewModel.isLoading {
                    InlineLoadingStatus(message: "Actualisation des reprises…")
                }
            }
        }
    }
}

private struct TradeInRow: View {
    let item: TradeInSummary
    @ObservedObject var viewModel: MyTradeInsViewModel

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            HStack {
                Text(item.reference)
                    .fontWeight(.semibold)
                Spacer()
                Text(item.statusLabel)
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }
            Text(item.productName)
            if let offerCents = item.offerCents {
                Text(PriceFormatter.format(cents: offerCents))
                    .font(.footnote.weight(.semibold))
            }
            if let adminNote = item.adminNote, !adminNote.isEmpty {
                Text(adminNote)
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
            if let voucherCode = item.voucherCode, !voucherCode.isEmpty {
                Text("Avoir client : \(voucherCode)")
                    .font(.footnote)
            }
            if item.status == "offer_sent" {
                HStack {
                    Button("Accepter") {
                        Task { await viewModel.respond(id: item.id, action: "accept") }
                    }
                    .disabled(viewModel.respondingTradeInID != nil)
                    .accessibilityLabel("Accepter l’offre de reprise \(item.reference)")
                    Button("Refuser", role: .destructive) {
                        Task { await viewModel.respond(id: item.id, action: "decline") }
                    }
                    .disabled(viewModel.respondingTradeInID != nil)
                    .accessibilityLabel("Refuser l’offre de reprise \(item.reference)")
                }
            }
            Button("Télécharger le justificatif") {
                Task { await viewModel.shareReceipt(id: item.id, reference: item.reference) }
            }
            .font(.footnote)
            .accessibilityHint("Télécharge le justificatif PDF de la reprise")
        }
        .padding(.vertical, 6)
        .accessibilityElement(children: .combine)
        .accessibilityLabel(accessibilityLabel)
    }

    private var accessibilityLabel: String {
        var components = [item.reference, item.productName, item.statusLabel]
        if let offerCents = item.offerCents {
            components.append(PriceFormatter.format(cents: offerCents))
        }
        if let voucherCode = item.voucherCode, !voucherCode.isEmpty {
            components.append("Avoir client : \(voucherCode)")
        }
        return components.joined(separator: ", ")
    }
}
