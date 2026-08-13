import Foundation

@MainActor
final class CommunicationPreferencesViewModel: ObservableObject {
    @Published var isLoading = false
    @Published var isSaving = false
    @Published var error: String?
    @Published var message: String?
    @Published var selectedPreferences: Set<String> = []
    @Published var choices: [CommunicationPreferenceChoice] = []

    private let service: WorkspaceServing

    init(service: WorkspaceServing) {
        self.service = service
    }

    func load() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            let data = try await service.communicationPreferences()
            choices = data.choices
            selectedPreferences = Set(data.preferences)
        } catch {
            self.error = error.localizedDescription
        }
    }

    func save() async {
        guard !isSaving else { return }
        isSaving = true
        error = nil
        message = nil
        defer { isSaving = false }

        do {
            let data = try await service.updateCommunicationPreferences(preferences: Array(selectedPreferences).sorted())
            choices = data.choices
            selectedPreferences = Set(data.preferences)
            message = "Préférences enregistrées."
        } catch {
            self.error = error.localizedDescription
        }
    }
}
