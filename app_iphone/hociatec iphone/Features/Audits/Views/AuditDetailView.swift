import SwiftUI

struct AuditDetailView: View {
    @ObservedObject var viewModel: AuditsViewModel
    let auditId: Int

    var body: some View {
        List {
            if let audit = viewModel.selectedAudit, audit.id == auditId {
                AuditSummarySection(audit: audit)
                AuditDownloadsSection(viewModel: viewModel, audit: audit)
                AuditObjectivesSection(objectives: audit.objectives)
                AuditChecklistSection(items: audit.items)
                AuditHistorySection(events: audit.events)
            } else if viewModel.isLoading {
                ProgressView("Chargement...")
            } else {
                Text("Audit introuvable.")
                    .foregroundStyle(.secondary)
            }
        }
        .navigationTitle("Détail audit")
        .task { await viewModel.loadDetail(id: auditId) }
    }
}
