import SwiftUI

@MainActor
struct MyQuotesListView: View {
    @StateObject private var viewModel: MyQuotesViewModel
    @State private var quoteToDelete: QuoteSummary? = nil

    init(viewModel: MyQuotesViewModel) {
        _viewModel = StateObject(wrappedValue: viewModel)
    }

    var body: some View {
        List {
            MyQuotesContent(
                viewModel: viewModel,
                quoteToDelete: $quoteToDelete
            )
        }
        .navigationTitle("Mes devis")
        .sheet(item: $viewModel.sharedFile) { file in
            ActivityView(activityItems: [file.url])
        }
        .task { await viewModel.load() }
        .refreshable { await viewModel.load(force: true) }
        .overlay(alignment: .bottom) {
            if viewModel.isLoading && !viewModel.quotes.isEmpty {
                InlineLoadingStatus(message: "Actualisation des devis…")
                    .padding(.horizontal, 16)
                    .padding(.bottom, 8)
                    .background(.thinMaterial, in: Capsule())
                    .padding(.bottom, 8)
            }
        }
        .feedbackDialog(error: $viewModel.error, success: $viewModel.successMessage)
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
