import Foundation

extension BetaProgramViewModel {
    func saveProfile() async -> Bool {
        isSubmittingProfile = true
        error = nil
        statusMessage = nil
        defer { isSubmittingProfile = false }

        do {
            let saved = try await service.updateMyBetaProfile(payload: profilePayload)
            profile = saved
            syncProfileFields(with: saved)
            statusMessage = "Profil bêta enregistré."
            return true
        } catch {
            self.error = error.localizedDescription
            return false
        }
    }

    func deleteProfile() async {
        error = nil
        statusMessage = nil
        do {
            try await service.deleteMyBetaProfile()
            profile = nil
            syncProfileFields(with: nil)
            statusMessage = "Profil bêta supprimé."
        } catch {
            self.error = error.localizedDescription
        }
    }

    private var profilePayload: [String: Any] {
        [
            "motivation": motivation,
            "testingExperience": testingExperience,
            "bugDescriptionAbility": bugDescriptionAbility,
            "technicalKnowledge": technicalKnowledge,
            "availability": availability,
            "accessibilityNeed": accessibilityNeed,
            "assistiveTools": assistiveTools,
            "devices": devices,
            "browsers": browsers,
            "testingTypes": testingTypes,
            "betaConsent": betaConsent
        ]
    }

    func syncProfileFields(with profile: BetaProfile?) {
        motivation = profile?.motivation ?? ""
        testingExperience = profile?.testingExperience ?? []
        bugDescriptionAbility = profile?.bugDescriptionAbility ?? []
        technicalKnowledge = profile?.technicalKnowledge ?? []
        availability = profile?.availability ?? []
        accessibilityNeed = profile?.accessibilityNeed ?? "none"
        assistiveTools = profile?.assistiveTools ?? []
        devices = profile?.devices ?? []
        browsers = profile?.browsers ?? []
        testingTypes = profile?.testingTypes ?? []
        betaConsent = profile?.betaConsent ?? true
    }
}
