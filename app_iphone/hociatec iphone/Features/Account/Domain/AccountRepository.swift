import Foundation

protocol AccountRepository {
    func login(email: String, password: String, rememberSession: Bool) async throws
    func logout() async
    func revokeAllSessions() async throws
    func fetchProfile() async throws -> UserProfile
    func restoreProfileIfPossible() async throws -> UserProfile?
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
    ) async throws -> UserProfile
    func deleteAccount() async throws
    func register(
        email: String,
        password: String,
        confirmPassword: String,
        firstName: String,
        lastName: String,
        birthDate: String,
        phoneNumber: String,
        gender: String
    ) async throws
    func listAddresses() async throws -> [UserAddress]
    func createAddress(type: String, label: String, address: String, addressComplement: String?, postalCode: String, company: String?, companySiren: String?, companyVatNumber: String?, city: String, isDefault: Bool) async throws
    func updateAddress(id: Int, type: String, label: String, address: String, addressComplement: String?, postalCode: String, company: String?, companySiren: String?, companyVatNumber: String?, city: String, isDefault: Bool) async throws
    func deleteAddress(id: Int) async throws
    func setDefaultAddress(id: Int) async throws
}
