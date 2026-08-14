import SwiftUI

struct RequestAuditView: View {
    @ObservedObject var viewModel: AuditsViewModel
    @State private var selectedType = "accessibility"
    @State private var url = ""
    @State private var objectives = ""

    var body: some View {
        Form {
            AuditRequestHeroSection()

            Section {
                Picker("Type d'audit", selection: $selectedType) {
                    ForEach(auditTypes, id: \.value) { type in
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
                        _ = await viewModel.createAudit(type: selectedType, url: url, objectives: objectives)
                    }
                }
                .disabled(viewModel.isSubmitting || url.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
            }
        }
        .navigationTitle("Demander un audit")
        .task {
            await viewModel.loadMetadata()
            if let firstType = auditTypes.first?.value {
                selectedType = firstType
            }
        }
        .feedbackDialog(error: $viewModel.error, success: $viewModel.successMessage)
    }

    private var auditTypes: [AuditOption] {
        viewModel.metadata?.types ?? []
    }
}

private struct AuditRequestHeroSection: View {
    var body: some View {
        Section {
            VStack(alignment: .leading, spacing: 10) {
                Text("Décrivez le périmètre à analyser et les objectifs attendus. Hociatec vous recontacte avec un cadrage adapté.")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
            .padding(.vertical, 4)
        }
    }
}
