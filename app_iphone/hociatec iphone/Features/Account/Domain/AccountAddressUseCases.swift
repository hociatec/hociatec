import Foundation

struct LoadAccountAddressesUseCase {
    let repository: AccountRepository

    func execute() async throws -> [UserAddress] {
        try await repository.listAddresses()
    }
}

struct CreateAccountAddressUseCase {
    let repository: AccountRepository

    func execute(label: String, address: String, postalCode: String, city: String, isDefault: Bool) async throws {
        try await repository.createAddress(
            label: label,
            address: address,
            postalCode: postalCode,
            city: city,
            isDefault: isDefault
        )
    }
}

struct UpdateAccountAddressUseCase {
    let repository: AccountRepository

    func execute(id: Int, label: String, address: String, postalCode: String, city: String, isDefault: Bool) async throws {
        try await repository.updateAddress(
            id: id,
            label: label,
            address: address,
            postalCode: postalCode,
            city: city,
            isDefault: isDefault
        )
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
