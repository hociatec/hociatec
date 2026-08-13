import Foundation

struct TrainingService: TrainingServing {
    let api: APIClient

    func trainingCategories() async throws -> [TrainingCategory] { try await api.trainingCategories() }
    func trainings(page: Int, perPage: Int, query: String?, category: String?) async throws -> TrainingListData {
        try await api.trainings(page: page, perPage: perPage, query: query, category: category)
    }
    func training(slug: String) async throws -> TrainingDetailData { try await api.training(slug: slug) }
}
