import Foundation

extension APIClient {
    func accountNotifications() async throws -> [AccountNotificationItem] {
        struct NotificationsPayload: Decodable {
            let items: [AccountNotificationItem]
        }

        let payload: NotificationsPayload = try await request(
            path: "api/account-notifications/me",
            authorized: true,
            attachCartToken: false
        )
        return payload.items
    }

    func accountNotificationsReadState() async throws -> AccountNotificationsReadState {
        struct ReadStatePayload: Decodable {
            let readState: AccountNotificationsReadState
        }

        let payload: ReadStatePayload = try await request(
            path: "api/account-notifications/me/read-state",
            authorized: true,
            attachCartToken: false
        )
        return payload.readState
    }

    func markAccountNotificationsSeen(keys: [String]) async throws -> AccountNotificationsReadState {
        struct ReadStatePayload: Decodable {
            let readState: AccountNotificationsReadState
        }

        let payload: ReadStatePayload = try await request(
            path: "api/account-notifications/me/read-state",
            method: "PATCH",
            body: ["seenKeys": keys],
            authorized: true,
            attachCartToken: false
        )
        return payload.readState
    }

    func dismissAccountNotification(key: String) async throws -> AccountNotificationsReadState {
        struct ReadStatePayload: Decodable {
            let readState: AccountNotificationsReadState
        }

        let payload: ReadStatePayload = try await request(
            path: "api/account-notifications/me/read-state",
            method: "PATCH",
            body: ["dismissedKey": key],
            authorized: true,
            attachCartToken: false
        )
        return payload.readState
    }

    func dismissAccountNotifications(keys: [String]) async throws -> AccountNotificationsReadState {
        struct ReadStatePayload: Decodable {
            let readState: AccountNotificationsReadState
        }

        let payload: ReadStatePayload = try await request(
            path: "api/account-notifications/me/read-state",
            method: "PATCH",
            body: ["dismissedKeys": keys],
            authorized: true,
            attachCartToken: false
        )
        return payload.readState
    }

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
        let payload: LoyaltyBalancePayload = try await request(
            path: "api/loyalty/me",
            authorized: true,
            attachCartToken: false
        )
        return payload.loyalty
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
