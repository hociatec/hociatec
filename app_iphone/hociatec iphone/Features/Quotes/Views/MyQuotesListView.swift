import SwiftUI

@MainActor
struct MyQuotesListView: View {
    @StateObject private var viewModel: MyQuotesViewModel
    @State private var quoteToDelete: QuoteSummary? = nil

    init(service: QuoteServing) {
        _viewModel = StateObject(wrappedValue: MyQuotesViewModel(service: service))
    }

    var body: some View {
        List {
            if let error = viewModel.error, !error.isEmpty {
                Section { Text(error).foregroundStyle(.red) }
            }

            if viewModel.quotes.isEmpty {
                Section {
                    Text(viewModel.isLoading ? "Chargement..." : "Aucun devis disponible.")
                        .foregroundStyle(.secondary)
                }
            } else {
                Section {
                    ForEach(viewModel.quotes) { quote in
                        NavigationLink {
                            QuoteDetailView(quote: quote)
                        } label: {
                            QuoteRow(quote: quote)
                        }
                        .swipeActions {
                            Button(role: .destructive) {
                                quoteToDelete = quote
                            } label: {
                                Label("Supprimer", systemImage: "trash")
                            }
                        }
                    }
                }
            }
        }
        .navigationTitle("Mes devis")
        .task { await viewModel.load(force: true) }
        .refreshable { await viewModel.load(force: true) }
        .alert(
            "Supprimer ce devis ?",
            isPresented: Binding(
                get: { quoteToDelete != nil },
                set: { newValue in if !newValue { quoteToDelete = nil } }
            )
        ) {
            Button("Annuler", role: .cancel) {
                quoteToDelete = nil
            }
            Button("Supprimer le devis", role: .destructive) {
                guard let q = quoteToDelete else { return }
                Task {
                    await viewModel.delete(id: q.id)
                    quoteToDelete = nil
                }
            }
        } message: {
            if let q = quoteToDelete {
                Text("Êtes-vous sûr de vouloir supprimer le devis \(q.number ?? "#\(q.id)") ? Cette action est irréversible.")
            } else {
                Text("Êtes-vous sûr de vouloir supprimer ce devis ? Cette action est irréversible.")
            }
        }
    }
}

private struct QuoteRow: View {
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
                Text("Créé le \(quotesDateFormatter.string(from: quote.createdAt))")
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

private struct QuoteDetailView: View {
    let quote: QuoteSummary

    var body: some View {
        Form {
            Section {
                LabeledContent("Numéro") { Text(quote.number ?? "—") }
                LabeledContent("Statut") { Text(quote.statusLabel) }
                LabeledContent("Créé le") { Text(quotesDateFormatter.string(from: quote.createdAt)) }
            }

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

            Section {
                LabeledContent("HT") { Text(PriceFormatter.format(cents: quote.totals.ht)) }
                LabeledContent("TVA") { Text(PriceFormatter.format(cents: quote.totals.vat)) }
                LabeledContent("TTC") { Text(PriceFormatter.format(cents: quote.totals.ttc)).fontWeight(.semibold) }
            }

            if let conditions = quote.conditions, !conditions.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
                Section {
                    Text(conditions)
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                }
            }
        }
        .navigationTitle(quote.number ?? "Devis")
        .navigationBarTitleDisplayMode(.inline)
    }
}

private let quotesDateFormatter: DateFormatter = {
    let df = DateFormatter()
    df.locale = Locale(identifier: "fr_FR")
    df.dateStyle = .medium
    df.timeStyle = .none
    return df
}()
