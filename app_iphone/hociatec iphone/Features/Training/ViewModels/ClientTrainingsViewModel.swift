import Foundation
import Combine

@MainActor
final class ClientTrainingsViewModel: ObservableObject {
    @Published var items: [TrainingEnrollment] = []
    @Published var isLoading = false
    @Published var error: String?

    private let service: TrainingServing
    private var hasLoadedOnce = false

    init(service: TrainingServing) {
        self.service = service
    }

    func load(force: Bool = false) async {
        if (isLoading || hasLoadedOnce) && !force { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            items = try await service.myEnrollments(page: 1, perPage: 20).items
            hasLoadedOnce = true
        } catch {
            self.error = error.localizedDescription
        }
    }
}
