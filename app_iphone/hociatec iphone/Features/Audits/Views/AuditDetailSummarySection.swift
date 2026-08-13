import SwiftUI

struct AuditSummarySection: View {
    let audit: AuditDetail

    var body: some View {
        Section {
            LabeledContent("Numéro") { Text(audit.number) }
            LabeledContent("Type") { Text(audit.typeLabel) }
            LabeledContent("Statut") { Text(audit.statusLabel) }
            LabeledContent("URL") { Text(audit.url) }
        }
    }
}
