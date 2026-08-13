import Foundation

protocol AccountServing {
    func profile() async throws -> UserProfile
    func login(email: String, password: String) async throws -> String
    func logout() async
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
    func verifyAccount(token: String) async throws
    func requestPasswordReset(email: String) async throws
    func resetPassword(token: String, password: String, confirmPassword: String) async throws
    func createAddress(type: String, label: String, address: String, addressComplement: String?, postalCode: String, company: String?, companySiren: String?, companyVatNumber: String?, city: String, isDefault: Bool) async throws
    func updateAddress(id: Int, type: String, label: String, address: String, addressComplement: String?, postalCode: String, company: String?, companySiren: String?, companyVatNumber: String?, city: String, isDefault: Bool) async throws
    func deleteAddress(id: Int) async throws
    func setDefaultAddress(id: Int) async throws
    func listAddresses() async throws -> [UserAddress]
}

protocol WorkspaceServing {
    func communicationPreferences() async throws -> CommunicationPreferencesData
    func updateCommunicationPreferences(preferences: [String]) async throws -> CommunicationPreferencesData
    func loyaltyBalance() async throws -> LoyaltyBalance
    func convertLoyalty(points: Int) async throws -> LoyaltyConversionData
}
