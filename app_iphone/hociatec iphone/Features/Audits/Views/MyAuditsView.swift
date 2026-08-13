import SwiftUI

struct MyAuditsView: View {
    @StateObject private var viewModel: AuditsViewModel

    init(service: AuditServing) {
        _viewModel = StateObject(wrappedValue: AuditsViewModel(service: service))
    }

    var body: some View {
        List {
            AuditStatusSection(error: viewModel.error, successMessage: viewModel.successMessage)
            AuditRequestEntrySection(viewModel: viewModel)
            AuditListSection(viewModel: viewModel)
        }
        .navigationTitle("Mes audits")
        .sheet(item: $viewModel.sharedFile) { file in
            ActivityView(activityItems: [file.url])
        }
        .task { await viewModel.load() }
        .refreshable { await viewModel.load(force: true) }
    }
}

private struct AuditStatusSection: View {
    let error: String?
    let successMessage: String?

    var body: some View {
        if let error, !error.isEmpty {
            Section {
                Text(error)
                    .foregroundStyle(.red)
            }
        }

        if let successMessage, !successMessage.isEmpty {
            Section {
                Text(successMessage)
                    .foregroundStyle(.green)
            }
        }
    }
}

private struct AuditRequestEntrySection: View {
    @ObservedObject var viewModel: AuditsViewModel

    var body: some View {
        Section {
            NavigationLink {
                RequestAuditView(viewModel: viewModel)
            } label: {
                Label("Demander un audit", systemImage: "checklist")
            }
        }
    }
}

private struct AuditListSection: View {
    @ObservedObject var viewModel: AuditsViewModel

    var body: some View {
        Section("Mes audits") {
            if viewModel.isLoading && viewModel.items.isEmpty {
                ProgressView("Chargement...")
            } else if viewModel.items.isEmpty {
                Text("Aucun audit trouvé.")
                    .foregroundStyle(.secondary)
            } else {
                ForEach(viewModel.items) { audit in
                    NavigationLink {
                        AuditDetailView(viewModel: viewModel, auditId: audit.id)
                    } label: {
                        AuditListRow(audit: audit)
                    }
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
