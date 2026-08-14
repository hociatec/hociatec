import Foundation
import Combine

/// Stocke les jetons de session (JWT + panier) et le profil utilisateur.
final class SessionStore: ObservableObject {
    private static let passwordKeychainService = "fr.hociatec.session"
    private static let passwordKeychainAccount = "loginPassword"

    enum CheckoutCallback: Equatable {
        case success(String)
        case cancelled
    }

    @Published var jwtToken: String? {
        didSet { persist(value: jwtToken, forKey: Keys.jwt) }
    }

    @Published var csrfToken: String?

    @Published var cartToken: String? {
        didSet { persist(value: cartToken, forKey: Keys.cart) }
    }

    @Published var profile: UserProfile? {
        didSet { persistProfile(profile) }
    }
    
    @Published var loginEmail: String? {
        didSet { persist(value: loginEmail, forKey: Keys.loginEmail) }
    }

    @Published var loginPassword: String? {
        didSet { persistPassword(loginPassword) }
    }

    @Published var rememberSession: Bool {
        didSet { UserDefaults.standard.set(rememberSession, forKey: Keys.rememberSession) }
    }

    @Published var pendingCheckoutSessionId: String? {
        didSet { persist(value: pendingCheckoutSessionId, forKey: Keys.pendingCheckoutSessionId) }
    }

    @Published var checkoutCallback: CheckoutCallback?

    private enum Keys {
        static let jwt = "hociatec.jwt"
        static let cart = "hociatec.cartToken"
        static let profile = "hociatec.profile"
        static let loginEmail = "hociatec.loginEmail"
        static let loginPassword = "hociatec.loginPassword"
        static let rememberSession = "hociatec.rememberSession"
        static let pendingCheckoutSessionId = "hociatec.pendingCheckoutSessionId"
    }

    init() {
        jwtToken = UserDefaults.standard.string(forKey: Keys.jwt)
        cartToken = UserDefaults.standard.string(forKey: Keys.cart)
        loginEmail = UserDefaults.standard.string(forKey: Keys.loginEmail)
        loginPassword = KeychainStorage.loadString(
            service: Self.passwordKeychainService,
            account: Self.passwordKeychainAccount
        )
        rememberSession = UserDefaults.standard.bool(forKey: Keys.rememberSession)
        pendingCheckoutSessionId = UserDefaults.standard.string(forKey: Keys.pendingCheckoutSessionId)

        if let data = UserDefaults.standard.data(forKey: Keys.profile) {
            profile = try? JSONDecoder().decode(UserProfile.self, from: data)
        }

        migrateLegacyPasswordIfNeeded()
    }

    func clearSession() {
        jwtToken = nil
        csrfToken = nil
        profile = nil
        loginPassword = nil
        pendingCheckoutSessionId = nil
        checkoutCallback = nil
        clearAuthCookies()
    }
    
    func storeCredentials(email: String, password: String, rememberSession: Bool) {
        loginEmail = email
        self.rememberSession = rememberSession
        loginPassword = rememberSession ? password : nil
    }
    
    var storedCredentials: (email: String, password: String)? {
        guard let email = loginEmail, let password = loginPassword else { return nil }
        return (email, password)
    }

    func handleIncomingURL(_ url: URL) {
        guard url.scheme?.caseInsensitiveCompare("hociatec") == .orderedSame else { return }
        let host = url.host?.lowercased()
        let pathComponents = url.pathComponents.filter { $0 != "/" }

        let action: String?
        if host == "checkout" {
            action = pathComponents.first?.lowercased()
        } else if pathComponents.first?.lowercased() == "checkout" {
            action = pathComponents.dropFirst().first?.lowercased()
        } else {
            action = nil
        }

        guard let action else { return }

        switch action {
        case "success":
            guard
                let components = URLComponents(url: url, resolvingAgainstBaseURL: false),
                let sessionId = components.queryItems?.first(where: { $0.name == "session_id" })?.value,
                !sessionId.isEmpty
            else {
                return
            }
            pendingCheckoutSessionId = sessionId
            checkoutCallback = .success(sessionId)
        case "cancel":
            checkoutCallback = .cancelled
        default:
            return
        }
    }

    func consumeCheckoutCallback() -> CheckoutCallback? {
        let callback = checkoutCallback
        checkoutCallback = nil
        return callback
    }

    private func persist(value: String?, forKey key: String) {
        if let value {
            UserDefaults.standard.set(value, forKey: key)
        } else {
            UserDefaults.standard.removeObject(forKey: key)
        }
    }

    private func persistPassword(_ password: String?) {
        if let password, !password.isEmpty {
            _ = KeychainStorage.saveString(
                password,
                service: Self.passwordKeychainService,
                account: Self.passwordKeychainAccount
            )
        } else {
            _ = KeychainStorage.delete(
                service: Self.passwordKeychainService,
                account: Self.passwordKeychainAccount
            )
        }

        UserDefaults.standard.removeObject(forKey: Keys.loginPassword)
    }

    private func persistProfile(_ profile: UserProfile?) {
        if let profile, let data = try? JSONEncoder().encode(profile) {
            UserDefaults.standard.set(data, forKey: Keys.profile)
        } else {
            UserDefaults.standard.removeObject(forKey: Keys.profile)
        }
    }

    private func clearAuthCookies() {
        let storage = HTTPCookieStorage.shared
        storage.cookies?
            .filter { ["hociatec_access", "hociatec_refresh", "hociatec_csrf"].contains($0.name) }
            .forEach { storage.deleteCookie($0) }
    }

    private func migrateLegacyPasswordIfNeeded() {
        guard
            loginPassword == nil,
            let legacyPassword = UserDefaults.standard.string(forKey: Keys.loginPassword),
            !legacyPassword.isEmpty
        else {
            UserDefaults.standard.removeObject(forKey: Keys.loginPassword)
            return
        }

        loginPassword = legacyPassword
    }
}
