import SwiftUI

struct BetaProgramPublicContent: View {
    @EnvironmentObject private var account: AccountViewModel

    var body: some View {
        Group {
            Section {
                Text("Participez à l’amélioration de Hociatec")
                    .font(.title3.weight(.bold))
                Text("Rejoignez notre communauté de bêta-testeurs, testez les nouvelles fonctionnalités et contribuez à rendre les parcours plus fiables.")
                    .foregroundStyle(.secondary)
            }

            Section("Étapes") {
                BetaStepRow(number: "1", title: "Créez votre espace", text: "Votre compte vous donne accès au programme.")
                BetaStepRow(number: "2", title: "Complétez votre profil", text: "Décrivez vos usages et vos préférences de test.")
                BetaStepRow(number: "3", title: "Partagez vos retours", text: "Suivez vos signalements et les réponses associées.")
            }

            Section {
                NavigationLink {
                    RegisterView(account: account)
                } label: {
                    Label("Rejoindre le programme bêta", systemImage: "flask")
                        .fontWeight(.semibold)
                }
            }
        }
    }
}
