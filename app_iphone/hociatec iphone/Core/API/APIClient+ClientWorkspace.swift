import Foundation

extension APIClient {
    func communicationPreferences() async throws -> CommunicationPreferencesData {
        try await request(
            path: "api/auth/communication-preferences",
            authorized: true,
            attachCartToken: false
        )
    }

    func updateCommunicationPreferences(preferences: [String]) async throws -> CommunicationPreferencesData {
        try await request(
            path: "api/auth/communication-preferences",
            method: "PUT",
            body: ["preferences": preferences],
            authorized: true,
            attachCartToken: false
        )
    }

    func loyaltyBalance() async throws -> LoyaltyBalance {
        try await request(
            path: "api/loyalty/me",
            authorized: true,
            attachCartToken: false
        )
    }

    func convertLoyalty(points: Int) async throws -> LoyaltyConversionData {
        try await request(
            path: "api/loyalty/me/convert",
            method: "POST",
            body: ["points": points],
            authorized: true,
            attachCartToken: false
        )
    }
}
