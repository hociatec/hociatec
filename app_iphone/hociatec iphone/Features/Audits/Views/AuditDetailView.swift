import SwiftUI

struct AuditDetailView: View {
    @ObservedObject var viewModel: AuditsViewModel
    let auditId: Int

    var body: some View {
        List {
            if let audit = viewModel.selectedAudit, audit.id == auditId {
                AuditSummarySection(audit: audit)
                AuditDownloadsSection(viewModel: viewModel, audit: audit)

                if let objectives = audit.objectives, !objectives.isEmpty {
                    Section("Objectifs") {
                        Text(objectives)
                    }
                }

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

private struct AuditSummarySection: View {
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

private struct AuditDownloadsSection: View {
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

private struct AuditChecklistSection: View {
    let items: [AuditChecklistItem]

    var body: some View {
        Section("Checklist") {
            ForEach(items.sorted(by: { $0.position < $1.position })) { item in
                AuditChecklistRow(item: item)
            }
        }
    }
}

private struct AuditChecklistRow: View {
    let item: AuditChecklistItem

    var body: some View {
        VStack(alignment: .leading, spacing: 4) {
            Text(item.label)
                .fontWeight(.semibold)
            Text(item.category)
                .font(.caption)
                .foregroundStyle(.secondary)

            if let level = item.level, !level.isEmpty {
                Text(level)
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }

            if let isCompliant = item.isCompliant {
                Text(isCompliant ? "Conforme" : "Non conforme")
                    .font(.footnote)
            } else {
                Text("À évaluer")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }

            if let comment = item.comment, !comment.isEmpty {
                Text(comment)
                    .font(.footnote)
            }
        }
        .padding(.vertical, 4)
    }
}

private struct AuditHistorySection: View {
    let events: [AuditEvent]

    var body: some View {
        if !events.isEmpty {
            Section("Historique") {
                ForEach(events) { event in
                    VStack(alignment: .leading, spacing: 4) {
                        Text(event.message ?? event.type)
                        Text(DateFormatters.frDateTime.string(from: event.createdAt))
                            .font(.caption)
                            .foregroundStyle(.secondary)
                    }
                    .padding(.vertical, 2)
                }
            }
        }
    }
}
