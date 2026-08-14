import Foundation

struct WorkspaceService: WorkspaceServing {
    let api: APIClient

    func accountNotifications() async throws -> [AccountNotificationItem] {
        try await api.accountNotifications()
    }

    func accountNotificationsReadState() async throws -> AccountNotificationsReadState {
        try await api.accountNotificationsReadState()
    }

    func markAccountNotificationsSeen(keys: [String]) async throws -> AccountNotificationsReadState {
        try await api.markAccountNotificationsSeen(keys: keys)
    }

    func dismissAccountNotification(key: String) async throws -> AccountNotificationsReadState {
        try await api.dismissAccountNotification(key: key)
    }

    func dismissAccountNotifications(keys: [String]) async throws -> AccountNotificationsReadState {
        try await api.dismissAccountNotifications(keys: keys)
    }

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
