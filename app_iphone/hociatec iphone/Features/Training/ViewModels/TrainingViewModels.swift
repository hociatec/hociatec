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
    private var categoriesRequestID = 0
    private var loadRequestID = 0

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
        categoriesRequestID += 1
        let requestID = categoriesRequestID
        do {
            let loadedCategories = try await service.trainingCategories().filter(\.isActive)
            guard requestID == categoriesRequestID else { return }
            categories = loadedCategories
        } catch {
            guard requestID == categoriesRequestID else { return }
            if self.error == nil {
                self.error = error.localizedDescription
            }
        }
    }

    func load(force: Bool = false) async {
        if isLoading && !force { return }
        loadRequestID += 1
        let requestID = loadRequestID
        isLoading = true
        error = nil
        let requestedPage = page
        let requestedSearch = appliedSearch.isEmpty ? nil : appliedSearch
        let requestedCategory = selectedCategorySlug.isEmpty ? nil : selectedCategorySlug

        do {
            let data = try await service.trainings(page: requestedPage, perPage: 8, query: requestedSearch, category: requestedCategory)
            guard requestID == loadRequestID else { return }
            trainings = data.items
            totalPages = max(1, data.meta.totalPages)
        } catch {
            guard requestID == loadRequestID else { return }
            self.error = error.localizedDescription
        }

        if requestID == loadRequestID {
            isLoading = false
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
    private var loadRequestID = 0

    init(service: TrainingServing, slug: String) {
        self.service = service
        self.slug = slug
    }

    func load(force: Bool = false) async {
        if isLoading && !force { return }
        loadRequestID += 1
        let requestID = loadRequestID
        isLoading = true
        error = nil
        statusMessage = nil

        do {
            let data = try await service.training(slug: slug)
            guard requestID == loadRequestID else { return }
            training = data.training
            sessions = data.sessions
        } catch {
            guard requestID == loadRequestID else { return }
            self.error = error.localizedDescription
        }

        if requestID == loadRequestID {
            isLoading = false
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
