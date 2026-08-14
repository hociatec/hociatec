import Foundation

extension AccountViewModel {
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
            session.storeCredentials(email: email, rememberSession: rememberSession)
            let profile = try await useCases.loadProfile.execute()
            await applyAuthenticatedState(profile: profile)
        } catch let err {
            if shouldIgnore(error: err) { return }
            self.error = err.localizedDescription
        }
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
            if shouldIgnore(error: err) {
                isLoading = false
                return false
            }
            self.error = err.localizedDescription
            isLoading = false
            return false
        }
    }

    func logout() async {
        await useCases.logout.execute()
        applyLoggedOutState()
    }
}
