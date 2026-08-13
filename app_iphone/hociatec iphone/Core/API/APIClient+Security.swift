import Foundation

extension APIClient {
    func captureCartToken(from response: HTTPURLResponse) {
        if let headerToken = response.value(forHTTPHeaderField: "X-Cart-Token"), !headerToken.isEmpty {
            sessionStore.cartToken = headerToken
        }
    }

    func currentCsrfToken() async throws -> String {
        if let csrfToken = sessionStore.csrfToken,
           !csrfToken.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
            return csrfToken
        }

        let data: CsrfTokenData = try await request(
            path: "api/csrf-token",
            authorized: false,
            attachCartToken: false
        )
        sessionStore.csrfToken = data.token
        return data.token
    }

    func requiresCsrf(path: String, method: String) -> Bool {
        guard methodRequiresCsrf(method), path.hasPrefix("api/") else {
            return false
        }

        return !["api/auth/login", "api/auth/register", "api/auth/refresh"].contains(path)
    }

    func methodRequiresCsrf(_ method: String) -> Bool {
        !["GET", "HEAD", "OPTIONS"].contains(method.uppercased())
    }

    func refreshAuthTokenIfPossible() async -> Bool {
        guard let credentials = sessionStore.storedCredentials else {
            return false
        }

        do {
            _ = try await login(email: credentials.email, password: credentials.password)
            return true
        } catch {
            sessionStore.clearSession()
            return false
        }
    }

    func applySecurityHeaders(
        to request: inout URLRequest,
        path: String,
        method: String,
        attachCartToken: Bool
    ) async throws {
        if requiresCsrf(path: path, method: method) {
            let csrfToken = try await currentCsrfToken()
            request.setValue(csrfToken, forHTTPHeaderField: "X-CSRF-Token")
        }

        if attachCartToken, let token = sessionStore.cartToken {
            request.setValue(token, forHTTPHeaderField: "X-Cart-Token")
        }
    }
}
