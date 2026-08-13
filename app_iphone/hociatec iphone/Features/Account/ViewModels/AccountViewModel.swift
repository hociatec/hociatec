import Foundation
import Combine

@MainActor
final class AccountViewModel: ObservableObject {
    static let birthDateFormatter: DateFormatter = {
        let df = DateFormatter()
        df.calendar = Calendar(identifier: .iso8601)
        df.locale = Locale(identifier: "en_US_POSIX")
        df.timeZone = TimeZone(secondsFromGMT: 0)
        df.dateFormat = "yyyy-MM-dd"
        return df
    }()

    @Published var email = ""
    @Published var password = ""
    @Published var isLoading = false
    @Published var error: String?
    @Published var statusMessage: String?
    @Published var profile: UserProfile?

    @Published var firstName: String = ""
    @Published var lastName: String = ""
    @Published var address: String? = nil
    @Published var postalCode: String? = nil
    @Published var city: String? = nil
    @Published var birthDate: String = ""
    @Published var phoneNumber: String = ""
    @Published var gender: String = "autre"
    @Published var roles: [String] = []
    @Published var addresses: [UserAddress] = []
    @Published private(set) var isLoggedIn: Bool

    private let useCases: AccountUseCases
    private let session: SessionStore
    private var cancellables = Set<AnyCancellable>()

    init(useCases: AccountUseCases, session: SessionStore) {
        self.useCases = useCases
        self.session = session
        self.isLoggedIn = session.jwtToken != nil
        self.profile = session.profile
        self.email = session.profile?.email ?? session.loginEmail ?? ""
        if let p = session.profile {
            self.firstName = p.firstName
            self.lastName = p.lastName
            self.address = p.address
            self.postalCode = p.postalCode
            self.city = p.city
            self.birthDate = normalizedBirthDate(p.birthDate)
            self.phoneNumber = p.phoneNumber
            self.gender = normalizedGender(p.gender ?? "autre")
            self.roles = p.roles
            self.addresses = p.addresses ?? []
        } else {
            self.gender = "autre"
            self.addresses = []
        }
        bindSession()
    }

    func loadProfileIfPossible() async {
        guard isLoggedIn else { return }
        await loadProfile()
    }

    func loadProfile() async {
        isLoading = true
        error = nil
        do {
            let profile = try await useCases.loadProfile.execute()
            await applyAuthenticatedState(profile: profile)
        } catch let err {
            self.error = err.localizedDescription
        }
        isLoading = false
    }

    func login() async {
        guard !email.isEmpty, !password.isEmpty else {
            error = "Renseignez vos identifiants."
            return
        }

        isLoading = true
        error = nil
        defer {
            isLoading = false
            password = ""
        }

        do {
            try await useCases.login.execute(email: email, password: password)
            session.storeCredentials(email: email, password: password)
            let profile = try await useCases.loadProfile.execute()
            await applyAuthenticatedState(profile: profile)
        } catch let err {
            self.error = err.localizedDescription
        }
    }

    func updateProfile() async {
        isLoading = true
        error = nil
        do {
            let updated = try await useCases.updateProfile.execute(
                firstName: firstName,
                lastName: lastName,
                email: email,
                address: address,
                postalCode: postalCode,
                city: city,
                birthDate: normalizedBirthDate(birthDate),
                phoneNumber: phoneNumber,
                gender: normalizedGender(gender)
            )
            self.apply(profile: updated)
            session.profile = updated
            session.loginEmail = updated.email
        } catch let err {
            self.error = err.localizedDescription
        }
        isLoading = false
    }

    func deleteAccount() async {
        isLoading = true
        error = nil
        do {
            try await useCases.deleteAccount.execute()
            await logout()
        } catch let err {
            self.error = err.localizedDescription
        }
        isLoading = false
    }

    func register(
        firstName: String,
        lastName: String,
        email: String,
        password: String,
        confirmPassword: String,
        birthDate: Date,
        phoneNumber: String,
        gender: String
    ) async -> Bool {
        isLoading = true
        error = nil
        statusMessage = nil
        let birthISO = AccountViewModel.birthDateFormatter.string(from: birthDate)
        do {
            try await useCases.register.execute(
                email: email,
                password: password,
                confirmPassword: confirmPassword,
                firstName: firstName,
                lastName: lastName,
                birthDate: birthISO,
                phoneNumber: phoneNumber,
                gender: normalizedGender(gender)
            )
            session.loginEmail = email
            statusMessage = "Compte créé. Vérifiez votre e-mail pour activer le compte avant de vous connecter."
            isLoading = false
            return true
        } catch let err {
            self.error = err.localizedDescription
            isLoading = false
            return false
        }
    }

    func refreshProfile() async {
        isLoading = true
        error = nil
        do {
            let profile = try await useCases.loadProfile.execute()
            await applyAuthenticatedState(profile: profile)
        } catch let err {
            self.error = err.localizedDescription
        }
        isLoading = false
    }

    func addAddress(label: String, address: String, postalCode: String, city: String, isDefault: Bool) async {
        isLoading = true
        error = nil
        do {
            try await useCases.createAddress.execute(label: label, address: address, postalCode: postalCode, city: city, isDefault: isDefault)
            await refreshProfile()
        } catch let err {
            self.error = err.localizedDescription
        }
        isLoading = false
    }

    func updateAddress(_ addr: UserAddress) async {
        guard let id = addr.id else { return }
        isLoading = true
        error = nil
        do {
            try await useCases.updateAddress.execute(id: id, label: addr.label, address: addr.address, postalCode: addr.postalCode, city: addr.city, isDefault: addr.isDefault)
            await refreshProfile()
        } catch let err {
            self.error = err.localizedDescription
        }
        isLoading = false
    }

    func deleteAddress(id: Int) async {
        isLoading = true
        error = nil
        do {
            try await useCases.deleteAddress.execute(id: id)
            await refreshProfile()
        } catch let err {
            self.error = err.localizedDescription
        }
        isLoading = false
    }

    func makeDefaultAddress(id: Int) async {
        isLoading = true
        error = nil
        do {
            try await useCases.setDefaultAddress.execute(id: id)
            await refreshProfile()
        } catch let err {
            self.error = err.localizedDescription
        }
        isLoading = false
    }

    func logout() async {
        await useCases.logout.execute()
        applyLoggedOutState()
    }

    private func bindSession() {
        session.$jwtToken
            .receive(on: RunLoop.main)
            .sink { [weak self] token in
                guard let self else { return }
                self.isLoggedIn = token != nil
                if token == nil {
                    self.applyLoggedOutState()
                }
            }
            .store(in: &cancellables)

        session.$profile
            .receive(on: RunLoop.main)
            .sink { [weak self] profile in
                guard let self else { return }
                if let profile {
                    self.apply(profile: profile)
                }
            }
            .store(in: &cancellables)

        session.$loginEmail
            .receive(on: RunLoop.main)
            .sink { [weak self] loginEmail in
                guard let self else { return }
                if !self.isLoggedIn {
                    self.email = loginEmail ?? ""
                }
            }
            .store(in: &cancellables)
    }

    private func applyAuthenticatedState(profile: UserProfile) async {
        apply(profile: profile)
        session.profile = profile
        await loadAddresses()
    }

    private func applyLoggedOutState() {
        profile = nil
        error = nil
        statusMessage = nil
        password = ""
        firstName = ""
        lastName = ""
        address = nil
        postalCode = nil
        city = nil
        birthDate = ""
        phoneNumber = ""
        roles = []
        addresses = []
        gender = "autre"
        email = session.loginEmail ?? email
    }

    private func apply(profile p: UserProfile) {
        self.profile = p
        self.email = p.email
        self.firstName = p.firstName
        self.lastName = p.lastName
        self.address = p.address
        self.postalCode = p.postalCode
        self.city = p.city
        self.birthDate = normalizedBirthDate(p.birthDate)
        self.phoneNumber = p.phoneNumber
        self.gender = normalizedGender(p.gender ?? "autre")
        self.roles = p.roles
        if let addresses = p.addresses {
            self.addresses = addresses
        }
    }

    private func loadAddresses() async {
        guard isLoggedIn else {
            addresses = []
            return
        }

        do {
            let items = try await useCases.loadAddresses.execute()
            addresses = items
        } catch let err {
            self.error = err.localizedDescription
        }
    }

    private func normalizedBirthDate(_ value: String) -> String {
        if let date = AccountViewModel.birthDateFormatter.date(from: value) {
            return AccountViewModel.birthDateFormatter.string(from: date)
        }
        return AccountViewModel.birthDateFormatter.string(from: Date())
    }

    private func normalizedGender(_ value: String) -> String {
        let cleaned = value.trimmingCharacters(in: .whitespacesAndNewlines).lowercased()
        switch cleaned {
        case "homme":
            return "homme"
        case "femme":
            return "femme"
        case "autre":
            return "autre"
        default:
            return "autre"
        }
    }
}
