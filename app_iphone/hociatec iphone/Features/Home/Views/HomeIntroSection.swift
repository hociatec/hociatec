import SwiftUI

struct HomeIntroSection: View {
    var body: some View {
        Section {
            VStack(alignment: .leading, spacing: 10) {
                Text("Hociatec accompagne vos besoins en matériel informatique, services numériques, formation et suivi client.")
                    .font(.body)
                Text("Retrouvez nos nouveautés, nos offres et un parcours mobile aligné avec nos services.")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
        }
    }
}
