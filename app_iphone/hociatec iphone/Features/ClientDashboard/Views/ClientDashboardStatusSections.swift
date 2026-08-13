import SwiftUI

struct ClientDashboardStatusSections: View {
    let error: String?
    let partialError: Bool

    var body: some View {
        Group {
            if let error, !error.isEmpty {
                Section {
                    Text(error)
                        .foregroundStyle(.red)
                }
            }

            if partialError {
                Section {
                    Text("Certaines données n’ont pas pu être chargées. Les accès restent disponibles.")
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                }
            }
        }
    }
}
