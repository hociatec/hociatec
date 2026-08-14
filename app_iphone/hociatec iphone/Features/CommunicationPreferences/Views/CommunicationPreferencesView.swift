import SwiftUI

struct CommunicationPreferencesView: View {
    @StateObject private var viewModel: CommunicationPreferencesViewModel

    init(service: WorkspaceServing) {
        _viewModel = StateObject(wrappedValue: CommunicationPreferencesViewModel(service: service))
    }

    var body: some View {
        List {
            Section {
                Text("Choisissez les moyens utilisés par Hociatec pour vos suivis importants.")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }

            Section("Moyens de communication") {
                if viewModel.isLoading && viewModel.choices.isEmpty {
                    ProgressView("Chargement...")
                } else {
                    ForEach(viewModel.choices, id: \.value) { choice in
                        Toggle(
                            isOn: Binding(
                                get: { viewModel.selectedPreferences.contains(choice.value) },
                                set: { enabled in
                                    if enabled {
                                        viewModel.selectedPreferences.insert(choice.value)
                                    } else {
                                        viewModel.selectedPreferences.remove(choice.value)
                                    }
                                }
                            )
                        ) {
                            VStack(alignment: .leading, spacing: 4) {
                                Text(choice.label)
                                    .fontWeight(.semibold)
                                Text(choice.description)
                                    .font(.footnote)
                                    .foregroundStyle(.secondary)
                            }
                            .padding(.vertical, 2)
                        }
                    }
                }
            }

            Section {
                Button {
                    Task { await viewModel.save() }
                } label: {
                    if viewModel.isSaving {
                        ProgressView()
                    } else {
                        Text("Enregistrer mes préférences")
                    }
                }
                .disabled(viewModel.isLoading || viewModel.isSaving || viewModel.selectedPreferences.isEmpty)
            }
        }
        .navigationTitle("Préférences")
        .task { await viewModel.load() }
        .refreshable { await viewModel.load() }
        .feedbackDialog(error: $viewModel.error, success: $viewModel.message)
    }
}
