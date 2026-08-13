import SwiftUI

struct ClientDashboardView: View {
    @StateObject private var viewModel: ClientDashboardViewModel
    @EnvironmentObject private var account: AccountViewModel
    @State private var showDeleteConfirmation = false

    private var loadKey: Int? {
        account.profile?.id
    }

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
        .task(id: loadKey) {
            guard account.isLoggedIn else { return }
            viewModel.resetVisibleState()
            await viewModel.load(force: true)
        }
        .refreshable { await viewModel.load(force: true) }
        .confirmationDialog("Êtes-vous sûr de vouloir supprimer votre compte ?", isPresented: $showDeleteConfirmation, titleVisibility: .visible) {
            Button("Supprimer mon compte", role: .destructive) {
                Task { await account.deleteAccount() }
            }
        }
    }
}
