import SwiftUI

struct BetaReportComposerView: View {
    @Environment(\.dismiss) private var dismiss
    @ObservedObject var viewModel: BetaProgramViewModel

    var body: some View {
        Form {
            TextField("Titre", text: $viewModel.reportTitle)
            TextEditor(text: $viewModel.reportDescription)
                .frame(minHeight: 120)
            TextField("Comportement attendu", text: $viewModel.reportExpectedBehavior)
            TextField("Comportement observé", text: $viewModel.reportActualBehavior)
            TextField("URL concernée", text: $viewModel.reportPageURL)
                .textInputAutocapitalization(.never)
            Picker("Priorité", selection: $viewModel.reportSeverity) {
                ForEach(viewModel.severities, id: \.self) { severity in
                    Text(viewModel.severityLabel(for: severity)).tag(severity)
                }
            }
            Picker("Campagne", selection: $viewModel.selectedCampaignID) {
                Text("Aucune").tag("")
                ForEach(viewModel.campaigns) { campaign in
                    Text(campaign.name).tag(String(campaign.id))
                }
            }

            Section {
                Button("Envoyer le signalement") {
                    Task {
                        let success = await viewModel.submitReport()
                        if success {
                            dismiss()
                        }
                    }
                }
                .disabled(
                    viewModel.isSubmittingReport
                        || viewModel.reportTitle.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
                        || viewModel.reportDescription.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
                )
            }
        }
        .navigationTitle("Signalement bêta")
    }
}
