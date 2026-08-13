import Foundation
import Combine

@MainActor
final class TrainingsCatalogViewModel: ObservableObject {
    @Published var trainings: [Training] = []
    @Published var categories: [TrainingCategory] = []
    @Published var selectedCategorySlug = ""
    @Published var page = 1
    @Published var totalPages = 1
    @Published var searchText = ""
    @Published var appliedSearch = ""
    @Published var isLoading = false
    @Published var error: String?

    private let service: TrainingServing

    init(service: TrainingServing) {
        self.service = service
    }

    func applySearch() {
        appliedSearch = searchText.trimmingCharacters(in: .whitespacesAndNewlines)
        page = 1
    }

    func previousPage() { guard page > 1 else { return }; page -= 1 }
    func nextPage() { guard page < totalPages else { return }; page += 1 }

    func loadCategoriesIfNeeded() async {
        guard categories.isEmpty else { return }
        do {
            categories = try await service.trainingCategories().filter(\.isActive)
        } catch {
            if self.error == nil {
                self.error = error.localizedDescription
            }
        }
    }

    func load() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            let data = try await service.trainings(page: page, perPage: 8, query: appliedSearch.isEmpty ? nil : appliedSearch, category: selectedCategorySlug.isEmpty ? nil : selectedCategorySlug)
            trainings = data.items
            totalPages = max(1, data.meta.totalPages)
        } catch {
            self.error = error.localizedDescription
        }
    }
}

@MainActor
final class TrainingDetailViewModel: ObservableObject {
    @Published var training: Training?
    @Published var sessions: [TrainingSession] = []
    @Published var isLoading = false
    @Published var error: String?
    @Published var statusMessage: String?
    @Published var submittingSessionId: Int?

    private let service: TrainingServing
    private let slug: String

    init(service: TrainingServing, slug: String) {
        self.service = service
        self.slug = slug
    }

    func load() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        statusMessage = nil
        defer { isLoading = false }

        do {
            let data = try await service.training(slug: slug)
            training = data.training
            sessions = data.sessions
        } catch {
            self.error = error.localizedDescription
        }
    }

    func enroll(sessionId: Int, startsAt: Date) async -> TrainingEnrollmentCheckoutResult? {
        guard submittingSessionId == nil else { return nil }

        submittingSessionId = sessionId
        error = nil
        statusMessage = nil
        defer { submittingSessionId = nil }

        do {
            let result = try await service.enroll(sessionId: sessionId, startsAt: startsAt)
            statusMessage = result.checkoutURL == nil
                ? "Votre inscription a bien été enregistrée."
                : "Poursuivez votre inscription pour finaliser le paiement."
            return result
        } catch {
            self.error = error.localizedDescription
            return nil
        }
    }
}
