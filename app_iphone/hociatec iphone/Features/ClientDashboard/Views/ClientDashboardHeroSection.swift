import SwiftUI

struct ClientDashboardHeroSection: View {
    let firstName: String?

    var body: some View {
        Section {
            VStack(alignment: .leading, spacing: 10) {
                Text("\(firstName ?? "Client"), votre espace en un coup d'œil")
                    .font(.title2.weight(.bold))
                Text("Suivez vos dossiers actifs, vos avantages et vos prochaines actions depuis une seule page.")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
            .padding(.vertical, 8)
        }
    }
}
