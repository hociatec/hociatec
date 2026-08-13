import SwiftUI

struct HomeIntroSection: View {
    @EnvironmentObject private var container: AppContainer

    var body: some View {
        Section {
            VStack(alignment: .leading, spacing: 10) {
                Text("Hociatec accompagne vos besoins en matériel informatique, services numériques, formation et suivi client.")
                    .font(.body)
                NavigationLink {
                    ContactView(service: container.services.contact)
                } label: {
                    Text("Contact")
                        .fontWeight(.semibold)
                }
                .buttonStyle(.borderedProminent)
                Text("Retrouvez nos nouveautés, nos offres et un parcours mobile aligné avec nos services.")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
        }
    }
}
