import Foundation

extension AccountViewModel {
    func loadAddresses() async {
        guard isLoggedIn else {
            addresses = []
            return
        }

        do {
            let items = try await useCases.loadAddresses.execute()
            addresses = items
        } catch let err {
            error = err.localizedDescription
        }
    }

    func addAddress(type: String, label: String, address: String, addressComplement: String?, postalCode: String, company: String?, companySiren: String?, companyVatNumber: String?, city: String, isDefault: Bool) async {
        await performAddressMutation {
            try await useCases.createAddress.execute(
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

    func updateAddress(_ addr: UserAddress) async {
        guard let id = addr.id else { return }

        await performAddressMutation {
            try await useCases.updateAddress.execute(
                id: id,
                type: addr.type,
                label: addr.label,
                address: addr.address,
                addressComplement: addr.addressComplement,
                postalCode: addr.postalCode,
                company: addr.company,
                companySiren: addr.companySiren,
                companyVatNumber: addr.companyVatNumber,
                city: addr.city,
                isDefault: addr.isDefault
            )
        }
    }

    func deleteAddress(id: Int) async {
        await performAddressMutation {
            try await useCases.deleteAddress.execute(id: id)
        }
    }

    func makeDefaultAddress(id: Int) async {
        await performAddressMutation {
            try await useCases.setDefaultAddress.execute(id: id)
        }
    }

    private func performAddressMutation(_ operation: () async throws -> Void) async {
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            try await operation()
            await refreshProfile()
        } catch let err {
            error = err.localizedDescription
        }
    }
}
