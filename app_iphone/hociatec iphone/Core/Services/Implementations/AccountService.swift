import Foundation

struct AccountService: AccountServing {
    let api: APIClient

    func profile() async throws -> UserProfile { try await api.profile() }
    func login(email: String, password: String) async throws -> String { try await api.login(email: email, password: password) }
    func logout() async { await api.logout() }

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

    func requestPasswordReset(email: String) async throws { try await api.requestPasswordReset(email: email) }
    func resetPassword(token: String, password: String, confirmPassword: String) async throws { try await api.resetPassword(token: token, password: password, confirmPassword: confirmPassword) }
    func createAddress(label: String, address: String, postalCode: String, city: String, isDefault: Bool) async throws { try await api.createAddress(label: label, address: address, postalCode: postalCode, city: city, isDefault: isDefault) }
    func updateAddress(id: Int, label: String, address: String, postalCode: String, city: String, isDefault: Bool) async throws { try await api.updateAddress(id: id, label: label, address: address, postalCode: postalCode, city: city, isDefault: isDefault) }
    func deleteAddress(id: Int) async throws { try await api.deleteAddress(id: id) }
    func setDefaultAddress(id: Int) async throws { try await api.setDefaultAddress(id: id) }
    func listAddresses() async throws -> [UserAddress] { try await api.listAddresses() }
}
