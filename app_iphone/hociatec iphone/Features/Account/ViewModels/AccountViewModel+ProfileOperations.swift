import Foundation

extension AccountViewModel {
    func loadProfileIfPossible() async {
        guard isLoggedIn else { return }
        await loadProfile()
    }

    func loadProfile() async {
        profileRequestID += 1
        let requestID = profileRequestID
        isLoading = true
        error = nil
        do {
            let profile = try await useCases.loadProfile.execute()
            guard requestID == profileRequestID else { return }
            await applyAuthenticatedState(profile: profile, requestID: requestID)
        } catch let err {
            guard requestID == profileRequestID else { return }
            if shouldIgnore(error: err) { return }
            self.error = err.localizedDescription
            self.globalDialog = .error(err.localizedDescription)
        }
        if requestID == profileRequestID {
            isLoading = false
        }
    }

    func updateProfile() async {
        profileRequestID += 1
        let requestID = profileRequestID
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
            guard requestID == profileRequestID else { return }
            apply(profile: updated)
            session.profile = updated
            session.loginEmail = updated.email
            globalDialog = .success("Profil mis à jour.")
        } catch let err {
            guard requestID == profileRequestID else { return }
            if shouldIgnore(error: err) { return }
            self.error = err.localizedDescription
            self.globalDialog = .error(err.localizedDescription)
        }
        if requestID == profileRequestID {
            isLoading = false
        }
    }

    func refreshProfile() async {
        profileRequestID += 1
        let requestID = profileRequestID
        isLoading = true
        error = nil
        do {
            let profile = try await useCases.loadProfile.execute()
            guard requestID == profileRequestID else { return }
            await applyAuthenticatedState(profile: profile, requestID: requestID)
        } catch let err {
            guard requestID == profileRequestID else { return }
            if shouldIgnore(error: err) { return }
            self.error = err.localizedDescription
            self.globalDialog = .error(err.localizedDescription)
        }
        if requestID == profileRequestID {
            isLoading = false
        }
    }

    func deleteAccount() async {
        profileRequestID += 1
        let requestID = profileRequestID
        isLoading = true
        error = nil
        do {
            try await useCases.deleteAccount.execute()
            guard requestID == profileRequestID else { return }
            await logout()
        } catch let err {
            guard requestID == profileRequestID else { return }
            if shouldIgnore(error: err) { return }
            self.error = err.localizedDescription
            self.globalDialog = .error(err.localizedDescription)
        }
        if requestID == profileRequestID {
            isLoading = false
        }
    }
}
