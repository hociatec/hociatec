import Foundation
import Combine

extension AccountViewModel {
    func bindSession() {
        session.$jwtToken
            .receive(on: RunLoop.main)
            .sink { [weak self] token in
                guard let self else { return }
                isLoggedIn = token != nil
                if token == nil {
                    applyLoggedOutState()
                }
            }
            .store(in: &cancellables)

        session.$profile
            .receive(on: RunLoop.main)
            .sink { [weak self] profile in
                guard let self else { return }
                if let profile {
                    apply(profile: profile)
                }
            }
            .store(in: &cancellables)

        session.$loginEmail
            .receive(on: RunLoop.main)
            .sink { [weak self] loginEmail in
                guard let self else { return }
                if !isLoggedIn {
                    email = loginEmail ?? ""
                }
            }
            .store(in: &cancellables)
    }

    func applyAuthenticatedState(profile: UserProfile) async {
        isLoggedIn = true
        error = nil
        apply(profile: profile)
        session.profile = profile
        await loadAddresses(reportErrors: false)
    }

    func applyLoggedOutState() {
        isLoggedIn = false
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

    func apply(profile p: UserProfile) {
        profile = p
        email = p.email
        firstName = p.firstName
        lastName = p.lastName
        address = p.address
        postalCode = p.postalCode
        city = p.city
        birthDate = normalizedBirthDate(p.birthDate)
        phoneNumber = p.phoneNumber
        gender = normalizedGender(p.gender ?? "autre")
        roles = p.roles
        if let addresses = p.addresses {
            self.addresses = addresses
        }
    }
}
