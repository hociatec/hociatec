import SwiftUI

struct MyAuditsView: View {
    @StateObject private var viewModel: AuditsViewModel

    init(service: AuditServing) {
        _viewModel = StateObject(wrappedValue: AuditsViewModel(service: service))
    }

    var body: some View {
        List {
            if let error = viewModel.error, !error.isEmpty {
                Section { Text(error).foregroundStyle(.red) }
            }

            if let message = viewModel.successMessage, !message.isEmpty {
                Section { Text(message).foregroundStyle(.green) }
            }

            Section {
                NavigationLink {
                    RequestAuditView(viewModel: viewModel)
                } label: {
                    Label("Demander un audit", systemImage: "checklist")
                }
            }

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
                }
            }
        }
        .navigationTitle("Mes audits")
        .sheet(item: $viewModel.sharedFile) { file in
            ActivityView(activityItems: [file.url])
        }
        .task { await viewModel.load() }
        .refreshable { await viewModel.load(force: true) }
    }
}

private struct RequestAuditView: View {
    @Environment(\.dismiss) private var dismiss
    @ObservedObject var viewModel: AuditsViewModel
    @State private var selectedType = "accessibility"
    @State private var url = ""
    @State private var objectives = ""

    var body: some View {
        Form {
            Section {
                Picker("Type d'audit", selection: $selectedType) {
                    ForEach(viewModel.metadata?.types ?? [], id: \.value) { type in
                        Text(type.label).tag(type.value)
                    }
                }
                TextField("URL ou accès", text: $url)
                    .textInputAutocapitalization(.never)
                    .keyboardType(.URL)
                TextEditor(text: $objectives)
                    .frame(minHeight: 140)
            }

            Section {
                Button("Envoyer la demande") {
                    Task {
                        let success = await viewModel.createAudit(type: selectedType, url: url, objectives: objectives)
                        if success { dismiss() }
                    }
                }
                .disabled(viewModel.isSubmitting || url.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
            }
        }
        .navigationTitle("Demander un audit")
        .task {
            await viewModel.load()
            if let firstType = viewModel.metadata?.types.first?.value {
                selectedType = firstType
            }
        }
    }
}

private struct AuditDetailView: View {
    @ObservedObject var viewModel: AuditsViewModel
    let auditId: Int

    var body: some View {
        List {
            if let audit = viewModel.selectedAudit, audit.id == auditId {
                Section {
                    LabeledContent("Numéro") { Text(audit.number) }
                    LabeledContent("Type") { Text(audit.typeLabel) }
                    LabeledContent("Statut") { Text(audit.statusLabel) }
                    LabeledContent("URL") { Text(audit.url) }
                }

                Section {
                    Button("Télécharger le rapport") {
                        Task { await viewModel.shareAuditReport(id: audit.id, number: audit.number) }
                    }
                    Button("Télécharger la synthèse") {
                        Task { await viewModel.shareAuditSummary(id: audit.id, number: audit.number) }
                    }
                }

                if let objectives = audit.objectives, !objectives.isEmpty {
                    Section("Objectifs") {
                        Text(objectives)
                    }
                }

                Section("Checklist") {
                    ForEach(audit.items.sorted(by: { $0.position < $1.position })) { item in
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

                if !audit.events.isEmpty {
                    Section("Historique") {
                        ForEach(audit.events) { event in
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
