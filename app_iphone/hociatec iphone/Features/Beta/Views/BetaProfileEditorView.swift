import SwiftUI

struct BetaProfileEditorView: View {
    @Environment(\.dismiss) private var dismiss
    @ObservedObject var viewModel: BetaProgramViewModel

    var body: some View {
        Form {
            if let error = viewModel.error, !error.isEmpty {
                Section { Text(error).foregroundStyle(.red) }
            }

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
                        let success = await viewModel.saveProfile()
                        if success {
                            dismiss()
                        }
                    }
                }
                .disabled(viewModel.isSubmittingProfile || !viewModel.isProfileComplete)
            }
        }
        .navigationTitle("Profil bêta")
        .task { await viewModel.loadChoicesIfNeeded() }
    }
}
