import SwiftUI

struct BetaProfileEditorView: View {
    @Environment(\.dismiss) private var dismiss
    @ObservedObject var viewModel: BetaProgramViewModel
    @State private var localError: String?
    @State private var localSuccess: String?
    @State private var shouldDismissAfterSuccess = false

    var body: some View {
        Form {
            Section {
                TextEditor(text: $viewModel.motivation)
                    .frame(minHeight: 140)
            } header: {
                Text("Motivation")
            }

            BetaOptionsSection(headerTitle: "Disponibilités", options: viewModel.choices["availability"] ?? [], selection: $viewModel.availability)
            BetaOptionsSection(headerTitle: "Expérience des tests", options: viewModel.choices["testingExperience"] ?? [], selection: $viewModel.testingExperience)
            BetaOptionsSection(headerTitle: "Capacité à décrire un bug", options: viewModel.choices["bugDescriptionAbility"] ?? [], selection: $viewModel.bugDescriptionAbility)
            BetaOptionsSection(headerTitle: "Connaissances techniques", options: viewModel.choices["technicalKnowledge"] ?? [], selection: $viewModel.technicalKnowledge)
            BetaOptionsSection(headerTitle: "Outils utilisés", options: viewModel.choices["assistiveTools"] ?? [], selection: $viewModel.assistiveTools)
            BetaOptionsSection(headerTitle: "Matériel", options: viewModel.choices["devices"] ?? [], selection: $viewModel.devices)
            BetaOptionsSection(headerTitle: "Navigateurs", options: viewModel.choices["browsers"] ?? [], selection: $viewModel.browsers)
            BetaOptionsSection(headerTitle: "Types de tests souhaités", options: viewModel.choices["testingTypes"] ?? [], selection: $viewModel.testingTypes)

            Section {
                TextField("Besoin d’accessibilité", text: $viewModel.accessibilityNeed)
                Toggle("J’accepte de participer au programme bêta", isOn: $viewModel.betaConsent)
            }

            Section {
                Button("Enregistrer mon profil bêta") {
                    Task {
                        localError = nil
                        localSuccess = nil
                        let success = await viewModel.saveProfile()
                        if success {
                            shouldDismissAfterSuccess = true
                            localSuccess = viewModel.statusMessage ?? "Profil bêta enregistré."
                            viewModel.statusMessage = nil
                        } else if let message = viewModel.error, !message.isEmpty {
                            localError = message
                            viewModel.error = nil
                        }
                    }
                }
                .disabled(viewModel.isSubmittingProfile || !viewModel.isProfileComplete)
            }
        }
        .navigationTitle("Profil bêta")
        .task { await viewModel.loadChoicesIfNeeded() }
        .feedbackDialog(error: $localError, success: $localSuccess) {
            if shouldDismissAfterSuccess {
                shouldDismissAfterSuccess = false
                dismiss()
            }
        }
    }
}
