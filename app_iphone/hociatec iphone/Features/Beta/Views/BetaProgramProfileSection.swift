import SwiftUI

struct BetaProgramProfileSection: View {
    @ObservedObject var viewModel: BetaProgramViewModel

    var body: some View {
        Section("Profil bêta") {
            if let profile = viewModel.profile {
                Text(viewModel.statusLabel(for: profile.status))
                    .font(.headline)
                Text(profile.motivation ?? "Motivation non renseignée")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            } else {
                Text("Aucun profil bêta enregistré pour le moment.")
                    .foregroundStyle(.secondary)
            }

            NavigationLink {
                BetaProfileEditorView(viewModel: viewModel)
            } label: {
                Label(
                    viewModel.profile == nil ? "Créer mon profil bêta" : "Modifier mon profil bêta",
                    systemImage: "person.text.rectangle"
                )
            }

            if viewModel.profile != nil {
                Button("Supprimer mon profil bêta", role: .destructive) {
                    Task { await viewModel.deleteProfile() }
                }
            }
        }
    }
}
