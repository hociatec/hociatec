import SwiftUI

struct ClientDashboardView: View {
    @StateObject private var viewModel: ClientDashboardViewModel
    @EnvironmentObject private var account: AccountViewModel
    @State private var showDeleteConfirmation = false

    init(services: AppServices) {
        _viewModel = StateObject(
            wrappedValue: ClientDashboardViewModel(
                quoteService: services.quotes,
                appointmentService: services.appointments,
                orderService: services.orders,
                trainingService: services.training,
                workspaceService: services.workspace
            )
        )
    }

    var body: some View {
        List {
            ClientDashboardScreenContent(
                viewModel: viewModel,
                showDeleteConfirmation: $showDeleteConfirmation
            )
        }
        .navigationTitle("Mon espace")
        .task(id: account.isLoggedIn) {
            guard account.isLoggedIn else { return }
            await viewModel.load()
        }
        .onChangeCompat(account.isLoggedIn) { isLoggedIn in
            viewModel.resetVisibleState()
            if !isLoggedIn {
                viewModel.hasLoadedOnce = false
                showDeleteConfirmation = false
            }
        }
        .refreshable { await viewModel.load(force: true) }
        .alert("Supprimer mon compte ?", isPresented: $showDeleteConfirmation) {
            Button("Annuler", role: .cancel) {}
            Button("Supprimer mon compte", role: .destructive) {
                Task { await account.deleteAccount() }
            }
        } message: {
            Text("Cette action est irréversible.")
        }
        .feedbackDialog(
            error: Binding(
                get: {
                    if let message = viewModel.error, !message.isEmpty {
                        return message
                    }
                    return viewModel.partialError
                        ? "Certaines données n’ont pas pu être chargées. Les accès restent disponibles."
                        : nil
                },
                set: { _ in
                    viewModel.error = nil
                    viewModel.partialError = false
                }
            ),
            success: $viewModel.conversionMessage
        )
    }
}
