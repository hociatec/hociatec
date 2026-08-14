import Foundation

extension AccountViewModel {
    func login() async {
        guard !email.isEmpty, !password.isEmpty else {
            error = "Renseignez vos identifiants."
            return
        }

        profileRequestID += 1
        let requestID = profileRequestID
        isLoading = true
        error = nil

        do {
            try await useCases.login.execute(email: email, password: password)
            session.storeCredentials(email: email, rememberSession: rememberSession)
            let profile = try await useCases.loadProfile.execute()
            guard requestID == profileRequestID else { return }
            await applyAuthenticatedState(profile: profile, requestID: requestID)
        } catch let err {
            guard requestID == profileRequestID else { return }
            if shouldIgnore(error: err) { return }
            self.error = err.localizedDescription
        }
        if requestID == profileRequestID {
            isLoading = false
            password = ""
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
        profileRequestID += 1
        let requestID = profileRequestID
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
            guard requestID == profileRequestID else { return false }
            session.loginEmail = email
            statusMessage = "Compte créé. Vérifiez votre e-mail pour activer le compte avant de vous connecter."
            isLoading = false
            return true
        } catch let err {
            guard requestID == profileRequestID else { return false }
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
        profileRequestID += 1
        addressesRequestID += 1
        await useCases.logout.execute()
        applyLoggedOutState()
    }
}
