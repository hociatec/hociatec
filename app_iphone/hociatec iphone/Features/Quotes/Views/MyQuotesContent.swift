import SwiftUI

struct MyQuotesContent: View {
    @ObservedObject var viewModel: MyQuotesViewModel
    @Binding var quoteToDelete: QuoteSummary?

    var body: some View {
        Group {
            if viewModel.quotes.isEmpty {
                Section {
                    Text(viewModel.isLoading ? "Chargement..." : "Aucun devis disponible.")
                        .foregroundStyle(.secondary)
                }
            } else {
                Section {
                    ForEach(viewModel.quotes) { quote in
                        VStack(alignment: .leading, spacing: 8) {
                            QuoteRow(quote: quote)
                            NavigationLink {
                                QuoteDetailView(viewModel: viewModel, quote: quote)
                            } label: {
                                Label("Voir le devis", systemImage: "arrow.right.circle")
                                    .font(.footnote.weight(.semibold))
                            }
                            .buttonStyle(.borderless)
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
    }
}
