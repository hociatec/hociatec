import Foundation

struct AccountService: AccountServing {
    let api: APIClient

    func profile() async throws -> UserProfile { try await api.profile() }
    func restoreAuthenticatedProfileIfPossible() async throws -> UserProfile? { try await api.restoreAuthenticatedProfileIfPossible() }
    func login(email: String, password: String, rememberSession: Bool) async throws -> String {
        try await api.login(email: email, password: password, rememberSession: rememberSession)
    }
    func logout() async { await api.logout() }
    func revokeAllSessions() async throws { try await api.revokeAllSessions() }
    func listAccessSessions() async throws -> [AccountAccessSession] { try await api.listAccessSessions() }
    func revokeAccessSession(id: Int) async throws -> RevokeAccessSessionResponse { try await api.revokeAccessSession(id: id) }

    func updateProfile(
        firstName: String,
        lastName: String,
        email: String,
        address: String?,
        postalCode: String?,
        city: String?,
        birthDate: String,
        phoneNumber: String,
        gender: String
    ) async throws -> UserProfile {
        try await api.updateProfile(
            firstName: firstName,
            lastName: lastName,
            email: email,
            address: address,
            postalCode: postalCode,
            city: city,
            birthDate: birthDate,
            phoneNumber: phoneNumber,
            gender: gender
        )
    }

    func deleteAccount() async throws { try await api.deleteAccount() }

    func register(
        email: String,
        password: String,
        confirmPassword: String,
        firstName: String,
        lastName: String,
        birthDate: String,
        phoneNumber: String,
        gender: String
    ) async throws {
        try await api.register(
            email: email,
            password: password,
            confirmPassword: confirmPassword,
            firstName: firstName,
            lastName: lastName,
            birthDate: birthDate,
            phoneNumber: phoneNumber,
            gender: gender
        )
    }

    func verifyAccount(token: String) async throws { try await api.verifyAccount(token: token) }
    func requestPasswordReset(email: String) async throws { try await api.requestPasswordReset(email: email) }
    func resetPassword(token: String, password: String, confirmPassword: String) async throws { try await api.resetPassword(token: token, password: password, confirmPassword: confirmPassword) }
    func createAddress(type: String, label: String, address: String, addressComplement: String?, postalCode: String, company: String?, companySiren: String?, companyVatNumber: String?, city: String, isDefault: Bool) async throws { try await api.createAddress(type: type, label: label, address: address, addressComplement: addressComplement, postalCode: postalCode, company: company, companySiren: companySiren, companyVatNumber: companyVatNumber, city: city, isDefault: isDefault) }
    func updateAddress(id: Int, type: String, label: String, address: String, addressComplement: String?, postalCode: String, company: String?, companySiren: String?, companyVatNumber: String?, city: String, isDefault: Bool) async throws { try await api.updateAddress(id: id, type: type, label: label, address: address, addressComplement: addressComplement, postalCode: postalCode, company: company, companySiren: companySiren, companyVatNumber: companyVatNumber, city: city, isDefault: isDefault) }
    func deleteAddress(id: Int) async throws { try await api.deleteAddress(id: id) }
    func setDefaultAddress(id: Int) async throws { try await api.setDefaultAddress(id: id) }
    func listAddresses() async throws -> [UserAddress] { try await api.listAddresses() }
}
