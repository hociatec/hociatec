import SwiftUI

struct AuditObjectivesSection: View {
    let objectives: String?

    var body: some View {
        if let objectives, !objectives.isEmpty {
            Section("Objectifs") {
                Text(objectives)
            }
        }
    }
}
