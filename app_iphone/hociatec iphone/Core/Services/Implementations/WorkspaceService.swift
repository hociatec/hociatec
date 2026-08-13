import Foundation

struct WorkspaceService: WorkspaceServing {
    let api: APIClient

    func communicationPreferences() async throws -> CommunicationPreferencesData {
        try await api.communicationPreferences()
    }

    func updateCommunicationPreferences(preferences: [String]) async throws -> CommunicationPreferencesData {
        try await api.updateCommunicationPreferences(preferences: preferences)
    }

    func loyaltyBalance() async throws -> LoyaltyBalance {
        try await api.loyaltyBalance()
    }

    func convertLoyalty(points: Int) async throws -> LoyaltyConversionData {
        try await api.convertLoyalty(points: points)
    }
}
