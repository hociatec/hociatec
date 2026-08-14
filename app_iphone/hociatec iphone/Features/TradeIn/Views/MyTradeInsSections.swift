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
                Text("Avoir client: \(voucherCode)")
                    .font(.footnote)
            }
            if item.status == "offer_sent" {
                HStack {
                    Button("Accepter") {
                        Task { await viewModel.respond(id: item.id, action: "accept") }
                    }
                    Button("Refuser", role: .destructive) {
                        Task { await viewModel.respond(id: item.id, action: "decline") }
                    }
                }
            }
            Button("Télécharger le justificatif") {
                Task { await viewModel.shareReceipt(id: item.id, reference: item.reference) }
            }
            .font(.footnote)
        }
        .padding(.vertical, 6)
    }
}
