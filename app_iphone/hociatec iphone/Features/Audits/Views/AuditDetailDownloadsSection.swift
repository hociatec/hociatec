import SwiftUI

struct AuditDownloadsSection: View {
    @ObservedObject var viewModel: AuditsViewModel
    let audit: AuditDetail

    var body: some View {
        Section {
            Button("Télécharger le rapport") {
                Task { await viewModel.shareAuditReport(id: audit.id, number: audit.number) }
            }

            Button("Télécharger la synthèse") {
                Task { await viewModel.shareAuditSummary(id: audit.id, number: audit.number) }
            }
        }
    }
}
