import SwiftUI

struct QuoteDetailView: View {
    @ObservedObject var viewModel: MyQuotesViewModel
    let quote: QuoteSummary

    var body: some View {
        Form {
            metadataSection
            customerSection
            itemsSection
            totalsSection
            documentSection
            conditionsSection
        }
        .navigationTitle(quote.number ?? "Devis")
        .navigationBarTitleDisplayMode(.inline)
    }

    private var metadataSection: some View {
        Section {
            LabeledContent("Numéro") { Text(quote.number ?? "—") }
            LabeledContent("Statut") { Text(quote.statusLabel) }
            LabeledContent("Créé le") { Text(QuotePresentation.dateFormatter.string(from: quote.createdAt)) }
        }
    }

    private var customerSection: some View {
        Section {
            LabeledContent("Nom") { Text(quote.customer.name) }
            LabeledContent("Email") { Text(quote.customer.email) }
            if let company = quote.customer.company, !company.isEmpty {
                LabeledContent("Société") { Text(company) }
            }
            if let address = quote.customer.address, !address.isEmpty {
                LabeledContent("Adresse") { Text(address) }
            }
        }
    }

    private var itemsSection: some View {
        Section {
            ForEach(quote.items) { item in
                VStack(alignment: .leading, spacing: 6) {
                    Text(item.name).fontWeight(.semibold)
                    if let desc = item.description, !desc.isEmpty {
                        Text(desc).font(.footnote).foregroundStyle(.secondary)
                    }
                    HStack {
                        Text("\(item.quantity) × \(PriceFormatter.format(cents: item.unitPriceCents))")
                            .font(.footnote)
                            .foregroundStyle(.secondary)
                        Spacer()
                        Text(PriceFormatter.format(cents: item.lineTotals.ttc))
                            .fontWeight(.semibold)
                    }
                }
                .padding(.vertical, 4)
            }
        }
    }

    private var totalsSection: some View {
        Section {
            LabeledContent("HT") { Text(PriceFormatter.format(cents: quote.totals.ht)) }
            LabeledContent("TVA") { Text(PriceFormatter.format(cents: quote.totals.vat)) }
            LabeledContent("TTC") { Text(PriceFormatter.format(cents: quote.totals.ttc)).fontWeight(.semibold) }
        }
    }

    private var documentSection: some View {
        Section {
            Button {
                Task { await viewModel.shareQuotePdf(quote: quote) }
            } label: {
                Label("Télécharger le PDF", systemImage: "arrow.down.doc")
                    .fontWeight(.semibold)
            }
            .accessibilityHint("Télécharge le devis au format PDF")
        }
    }

    @ViewBuilder
    private var conditionsSection: some View {
        if let conditions = quote.conditions, !conditions.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
            Section {
                Text(conditions)
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
        }
    }
}
