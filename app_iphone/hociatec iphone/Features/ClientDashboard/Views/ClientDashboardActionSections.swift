import SwiftUI

struct ClientDashboardActionsSection: View {
    let isLoading: Bool
    let actions: [ClientDashboardAction]

    var body: some View {
        Section("À faire maintenant") {
            if isLoading && actions.isEmpty {
                ProgressView("Chargement...")
            } else if actions.isEmpty {
                VStack(alignment: .leading, spacing: 8) {
                    Text("Rien d'urgent à traiter.")
                        .fontWeight(.semibold)
                    Text("Vos prochaines commandes, rendez-vous, devis ou formations apparaîtront ici dès qu'une action sera utile.")
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                }
                .padding(.vertical, 4)
            } else {
                ForEach(actions) { action in
                    VStack(alignment: .leading, spacing: 8) {
                        ClientDashboardActionRow(action: action)
                        NavigationLink {
                            ClientDashboardActionDestinationView(action: action)
                        } label: {
                            Label("Ouvrir l’action", systemImage: "arrow.right.circle")
                                .font(.footnote.weight(.semibold))
                        }
                        .buttonStyle(.borderless)
                    }
                }
            }
        }
    }
}
