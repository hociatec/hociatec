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
                        NavigationLink {
                            QuoteDetailView(viewModel: viewModel, quote: quote)
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
    }
}
