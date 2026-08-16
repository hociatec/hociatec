import SwiftUI

struct ClientDashboardView: View {
    @StateObject private var viewModel: ClientDashboardViewModel
    @EnvironmentObject private var account: AccountViewModel
    @State private var confirmationDialog: FeedbackDialogState?

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
                showDeleteConfirmation: Binding(
                    get: { confirmationDialog != nil },
                    set: { newValue in
                        if newValue {
                            confirmationDialog = FeedbackDialogState(
                                title: "Supprimer mon compte ?",
                                message: "Cette action est irréversible.",
                                primaryButton: .cancel("Annuler"),
                                secondaryButton: .destructive("Supprimer mon compte") {
                                    Task { await account.deleteAccount() }
                                }
                            )
                        } else {
                            confirmationDialog = nil
                        }
                    }
                )
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
                confirmationDialog = nil
            }
        }
        .refreshable { await viewModel.load(force: true) }
        .feedbackDialog($confirmationDialog)
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
