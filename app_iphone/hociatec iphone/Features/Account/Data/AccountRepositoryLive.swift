import Foundation

struct AccountRepositoryLive: AccountRepository {
    let service: AccountServing

    func login(email: String, password: String) async throws {
        _ = try await service.login(email: email, password: password)
    }

    func logout() async {
        await service.logout()
    }

    func fetchProfile() async throws -> UserProfile {
        try await service.profile()
    }

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
        try await service.updateProfile(
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

    func deleteAccount() async throws {
        try await service.deleteAccount()
    }

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
        try await service.register(
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

    func listAddresses() async throws -> [UserAddress] {
        try await service.listAddresses()
    }

    func createAddress(type: String, label: String, address: String, addressComplement: String?, postalCode: String, company: String?, companySiren: String?, companyVatNumber: String?, city: String, isDefault: Bool) async throws {
        try await service.createAddress(type: type, label: label, address: address, addressComplement: addressComplement, postalCode: postalCode, company: company, companySiren: companySiren, companyVatNumber: companyVatNumber, city: city, isDefault: isDefault)
    }

    func updateAddress(id: Int, type: String, label: String, address: String, addressComplement: String?, postalCode: String, company: String?, companySiren: String?, companyVatNumber: String?, city: String, isDefault: Bool) async throws {
        try await service.updateAddress(id: id, type: type, label: label, address: address, addressComplement: addressComplement, postalCode: postalCode, company: company, companySiren: companySiren, companyVatNumber: companyVatNumber, city: city, isDefault: isDefault)
    }

    func deleteAddress(id: Int) async throws {
        try await service.deleteAddress(id: id)
    }

    func setDefaultAddress(id: Int) async throws {
        try await service.setDefaultAddress(id: id)
    }
}
