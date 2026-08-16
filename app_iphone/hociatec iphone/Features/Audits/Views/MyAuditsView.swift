import SwiftUI

struct MyAuditsView: View {
    @StateObject private var viewModel: AuditsViewModel

    init(service: AuditServing) {
        _viewModel = StateObject(wrappedValue: AuditsViewModel(service: service))
    }

    var body: some View {
        List {
            AuditListSection(viewModel: viewModel)
        }
        .navigationTitle("Mes audits")
        .sheet(item: $viewModel.sharedFile) { file in
            ActivityView(activityItems: [file.url])
        }
        .task { await viewModel.load() }
        .refreshable { await viewModel.load(force: true) }
        .feedbackDialog(error: $viewModel.error, success: $viewModel.successMessage)
    }
}

private struct AuditListSection: View {
    @ObservedObject var viewModel: AuditsViewModel

    var body: some View {
        Section {
            if viewModel.isLoading && viewModel.items.isEmpty {
                ProgressView("Chargement...")
            } else if viewModel.items.isEmpty {
                Text("Aucun audit trouvé.")
                    .foregroundStyle(.secondary)
            } else {
                ForEach(viewModel.items) { audit in
                    VStack(alignment: .leading, spacing: 8) {
                        AuditListRow(audit: audit)
                        NavigationLink {
                            AuditDetailView(viewModel: viewModel, auditId: audit.id)
                        } label: {
                            Label("Voir l’audit", systemImage: "arrow.right.circle")
                                .font(.footnote.weight(.semibold))
                        }
                        .buttonStyle(.borderless)
                    }
                }

                if viewModel.isLoading {
                    InlineLoadingStatus(message: "Actualisation des audits…")
                }
            }
        }
    }
}

private struct AuditListRow: View {
    let audit: AuditListItem

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            Text("\(audit.number) — \(audit.typeLabel)")
                .fontWeight(.semibold)
            Text(audit.url)
                .font(.footnote)
                .foregroundStyle(.secondary)
            Text(audit.statusLabel)
                .font(.footnote)
                .foregroundStyle(.secondary)
        }
        .padding(.vertical, 4)
    }
}
