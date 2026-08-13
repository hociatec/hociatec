import SwiftUI

struct ClientDashboardTopActionsSection: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var account: AccountViewModel

    @Binding var showDeleteConfirmation: Bool

    var body: some View {
        Section {
            Button("Se déconnecter") {
                Task { await account.logout() }
            }

            NavigationLink {
                AboutHubView(services: container.services)
            } label: {
                Text("À propos")
            }

            Button("Supprimer mon compte", role: .destructive) {
                showDeleteConfirmation = true
            }
        }
    }
}
