import SwiftUI

struct BetaProgramIntroSection: View {
    var body: some View {
        Section {
            Text("Mon espace bêta")
                .font(.title3.weight(.bold))
            Text("Gérez votre profil bêta, consultez les campagnes ouvertes et suivez vos signalements.")
                .foregroundStyle(.secondary)
        }
    }
}
