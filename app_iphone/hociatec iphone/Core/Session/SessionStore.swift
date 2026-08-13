import Foundation
import Combine

/// Stocke les jetons de session (JWT + panier) et le profil utilisateur.
final class SessionStore: ObservableObject {
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
        didSet { persist(value: loginPassword, forKey: Keys.loginPassword) }
    }

    @Published var rememberSession: Bool {
        didSet { UserDefaults.standard.set(rememberSession, forKey: Keys.rememberSession) }
    }

    private enum Keys {
        static let jwt = "hociatec.jwt"
        static let cart = "hociatec.cartToken"
        static let profile = "hociatec.profile"
        static let loginEmail = "hociatec.loginEmail"
        static let loginPassword = "hociatec.loginPassword"
        static let rememberSession = "hociatec.rememberSession"
    }

    init() {
        jwtToken = UserDefaults.standard.string(forKey: Keys.jwt)
        cartToken = UserDefaults.standard.string(forKey: Keys.cart)
        loginEmail = UserDefaults.standard.string(forKey: Keys.loginEmail)
        loginPassword = UserDefaults.standard.string(forKey: Keys.loginPassword)
        rememberSession = UserDefaults.standard.bool(forKey: Keys.rememberSession)

        if let data = UserDefaults.standard.data(forKey: Keys.profile) {
            profile = try? JSONDecoder().decode(UserProfile.self, from: data)
        }
    }

    func clearSession() {
        jwtToken = nil
        csrfToken = nil
        profile = nil
        loginPassword = nil
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

    private func persist(value: String?, forKey key: String) {
        if let value {
            UserDefaults.standard.set(value, forKey: key)
        } else {
            UserDefaults.standard.removeObject(forKey: key)
        }
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
}
