import Foundation

extension AccountViewModel {
    func loadProfileIfPossible() async {
        guard isLoggedIn else { return }
        await loadProfile()
    }

    func loadProfile() async {
        isLoading = true
        error = nil
        defer { isLoading = false }
        do {
            let profile = try await useCases.loadProfile.execute()
            await applyAuthenticatedState(profile: profile)
        } catch let err {
            if shouldIgnore(error: err) { return }
            self.error = err.localizedDescription
        }
    }

    func updateProfile() async {
        isLoading = true
        error = nil
        defer { isLoading = false }
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
            apply(profile: updated)
            session.profile = updated
            session.loginEmail = updated.email
        } catch let err {
            if shouldIgnore(error: err) { return }
            self.error = err.localizedDescription
        }
    }

    func refreshProfile() async {
        isLoading = true
        error = nil
        defer { isLoading = false }
        do {
            let profile = try await useCases.loadProfile.execute()
            await applyAuthenticatedState(profile: profile)
        } catch let err {
            if shouldIgnore(error: err) { return }
            self.error = err.localizedDescription
        }
    }

    func deleteAccount() async {
        isLoading = true
        error = nil
        defer { isLoading = false }
        do {
            try await useCases.deleteAccount.execute()
            await logout()
        } catch let err {
            if shouldIgnore(error: err) { return }
            self.error = err.localizedDescription
        }
    }
}
