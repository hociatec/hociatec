import SwiftUI

@MainActor
struct MyQuotesListView: View {
    @StateObject private var viewModel: MyQuotesViewModel
    @State private var quoteToDelete: QuoteSummary? = nil
    @State private var confirmationDialog: FeedbackDialogState?

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
        .feedbackDialog($confirmationDialog)
        .onChangeCompat(quoteToDelete?.id) { _ in
            guard let q = quoteToDelete else {
                confirmationDialog = nil
                return
            }
            confirmationDialog = FeedbackDialogState(
                title: "Supprimer ce devis ?",
                message: "Êtes-vous sûr de vouloir supprimer le devis \(q.number ?? "#\(q.id)") ? Cette action est irréversible.",
                primaryButton: .cancel("Annuler") {
                    quoteToDelete = nil
                },
                secondaryButton: .destructive("Supprimer le devis") {
                    let id = q.id
                    Task {
                        await viewModel.delete(id: id)
                        quoteToDelete = nil
                    }
                }
            )
        }
    }
}
