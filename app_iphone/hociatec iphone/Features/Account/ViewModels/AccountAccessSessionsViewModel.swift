import Foundation
import Combine

@MainActor
final class AccountAccessSessionsViewModel: ObservableObject {
    @Published private(set) var sessions: [AccountAccessSession] = []
    @Published var isLoading = false
    @Published var error: String?
    @Published var successMessage: String?
    @Published var revokingSessionID: Int?

    private let service: AccountServing
    private var hasLoadedOnce = false

    init(service: AccountServing) {
        self.service = service
    }

    var count: Int {
        sessions.count
    }

    func clear() {
        sessions = []
        hasLoadedOnce = false
    }

    func load(force: Bool = false) async {
        if (isLoading || hasLoadedOnce) && !force { return }

        isLoading = true
        error = nil

        do {
            sessions = try await service.listAccessSessions()
            hasLoadedOnce = true
        } catch {
            self.error = error.localizedDescription
        }

        isLoading = false
    }

    func revoke(session: AccountAccessSession) async {
        guard revokingSessionID == nil else { return }

        revokingSessionID = session.id
        error = nil
        successMessage = nil

        do {
            let response = try await service.revokeAccessSession(id: session.id)
            if !response.revokedCurrentSession {
                sessions.removeAll { $0.id == session.id }
                successMessage = "Accès révoqué."
            }
        } catch {
            self.error = error.localizedDescription
        }

        revokingSessionID = nil
    }
}
