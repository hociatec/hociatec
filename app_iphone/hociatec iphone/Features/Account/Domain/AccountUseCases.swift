import Foundation

struct AccountUseCases {
    let login: LoginUseCase
    let logout: LogoutUseCase
    let loadProfile: LoadAccountProfileUseCase
    let updateProfile: UpdateAccountProfileUseCase
    let deleteAccount: DeleteAccountUseCase
    let register: RegisterAccountUseCase
    let loadAddresses: LoadAccountAddressesUseCase
    let createAddress: CreateAccountAddressUseCase
    let updateAddress: UpdateAccountAddressUseCase
    let deleteAddress: DeleteAccountAddressUseCase
    let setDefaultAddress: SetDefaultAccountAddressUseCase
}

struct LoginUseCase {
    let repository: AccountRepository
    func execute(email: String, password: String) async throws {
        try await repository.login(email: email, password: password)
    }
}

struct LogoutUseCase {
    let repository: AccountRepository
    func execute() async {
        await repository.logout()
    }
}

struct LoadAccountProfileUseCase {
    let repository: AccountRepository
    func execute() async throws -> UserProfile {
        try await repository.fetchProfile()
    }
}

struct UpdateAccountProfileUseCase {
    let repository: AccountRepository
    func execute(
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
        try await repository.updateProfile(
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
}

struct DeleteAccountUseCase {
    let repository: AccountRepository
    func execute() async throws {
        try await repository.deleteAccount()
    }
}

struct RegisterAccountUseCase {
    let repository: AccountRepository
    func execute(
        email: String,
        password: String,
        confirmPassword: String,
        firstName: String,
        lastName: String,
        birthDate: String,
        phoneNumber: String,
        gender: String
    ) async throws {
        try await repository.register(
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
}

struct LoadAccountAddressesUseCase {
    let repository: AccountRepository
    func execute() async throws -> [UserAddress] {
        try await repository.listAddresses()
    }
}

struct CreateAccountAddressUseCase {
    let repository: AccountRepository
    func execute(label: String, address: String, postalCode: String, city: String, isDefault: Bool) async throws {
        try await repository.createAddress(label: label, address: address, postalCode: postalCode, city: city, isDefault: isDefault)
    }
}

struct UpdateAccountAddressUseCase {
    let repository: AccountRepository
    func execute(id: Int, label: String, address: String, postalCode: String, city: String, isDefault: Bool) async throws {
        try await repository.updateAddress(id: id, label: label, address: address, postalCode: postalCode, city: city, isDefault: isDefault)
    }
}

struct DeleteAccountAddressUseCase {
    let repository: AccountRepository
    func execute(id: Int) async throws {
        try await repository.deleteAddress(id: id)
    }
}

struct SetDefaultAccountAddressUseCase {
    let repository: AccountRepository
    func execute(id: Int) async throws {
        try await repository.setDefaultAddress(id: id)
    }
}
