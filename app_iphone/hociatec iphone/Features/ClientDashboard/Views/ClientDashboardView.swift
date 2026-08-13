import SwiftUI

struct ClientDashboardView: View {
    @StateObject private var viewModel: ClientDashboardViewModel
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
        .task { await viewModel.load() }
        .refreshable { await viewModel.load(force: true) }
        .confirmationDialog("Êtes-vous sûr de vouloir supprimer votre compte ?", isPresented: $showDeleteConfirmation, titleVisibility: .visible) {
            Button("Supprimer mon compte", role: .destructive) {
                Task { await account.deleteAccount() }
            }
            Button("Annuler", role: .cancel) {}
        }
    }
}
