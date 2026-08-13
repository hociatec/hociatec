import Foundation

struct LoadAccountAddressesUseCase {
    let repository: AccountRepository

    func execute() async throws -> [UserAddress] {
        try await repository.listAddresses()
    }
}

struct CreateAccountAddressUseCase {
    let repository: AccountRepository

    func execute(type: String, label: String, address: String, addressComplement: String?, postalCode: String, company: String?, companySiren: String?, companyVatNumber: String?, city: String, isDefault: Bool) async throws {
        try await repository.createAddress(
            type: type,
            label: label,
            address: address,
            addressComplement: addressComplement,
            postalCode: postalCode,
            company: company,
            companySiren: companySiren,
            companyVatNumber: companyVatNumber,
            city: city,
            isDefault: isDefault
        )
    }
}

struct UpdateAccountAddressUseCase {
    let repository: AccountRepository

    func execute(id: Int, type: String, label: String, address: String, addressComplement: String?, postalCode: String, company: String?, companySiren: String?, companyVatNumber: String?, city: String, isDefault: Bool) async throws {
        try await repository.updateAddress(
            id: id,
            type: type,
            label: label,
            address: address,
            addressComplement: addressComplement,
            postalCode: postalCode,
            company: company,
            companySiren: companySiren,
            companyVatNumber: companyVatNumber,
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
