import SwiftUI

struct ClientDashboardTopActionsSection: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var account: AccountViewModel

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
        }
    }
}

struct ClientDashboardDangerZoneSection: View {
    @Binding var showDeleteConfirmation: Bool

    var body: some View {
        Section("Compte") {
            Button {
                showDeleteConfirmation = true
            } label: {
                Text("Supprimer mon compte")
                    .foregroundStyle(.red)
            }
        }
    }
}
