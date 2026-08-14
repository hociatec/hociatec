import Foundation

extension AccountViewModel {
    func loadAddresses(reportErrors: Bool = true) async {
        guard isLoggedIn else {
            addresses = []
            return
        }
        addressesRequestID += 1
        let requestID = addressesRequestID

        do {
            let items = try await useCases.loadAddresses.execute()
            guard requestID == addressesRequestID else { return }
            addresses = items
        } catch let err {
            guard requestID == addressesRequestID else { return }
            if shouldIgnore(error: err) { return }
            if reportErrors {
                error = err.localizedDescription
            }
        }
    }

    @discardableResult
    func addAddress(type: String, label: String, address: String, addressComplement: String?, postalCode: String, company: String?, companySiren: String?, companyVatNumber: String?, city: String, isDefault: Bool, reportErrors: Bool = true) async -> String? {
        await performAddressMutation(reportErrors: reportErrors) {
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

    @discardableResult
    func updateAddress(_ addr: UserAddress, reportErrors: Bool = true) async -> String? {
        guard let id = addr.id else { return "Adresse invalide." }

        return await performAddressMutation(reportErrors: reportErrors) {
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

    @discardableResult
    func deleteAddress(id: Int, reportErrors: Bool = true) async -> String? {
        await performAddressMutation(reportErrors: reportErrors) {
            try await useCases.deleteAddress.execute(id: id)
        }
    }

    @discardableResult
    func makeDefaultAddress(id: Int, reportErrors: Bool = true) async -> String? {
        await performAddressMutation(reportErrors: reportErrors) {
            try await useCases.setDefaultAddress.execute(id: id)
        }
    }

    private func performAddressMutation(
        reportErrors: Bool,
        _ operation: () async throws -> Void
    ) async -> String? {
        addressMutationRequestID += 1
        let requestID = addressMutationRequestID
        isLoading = true
        error = nil

        do {
            try await operation()
            guard requestID == addressMutationRequestID else { return nil }
            await refreshProfile()
            return nil
        } catch let err {
            guard requestID == addressMutationRequestID else { return nil }
            if shouldIgnore(error: err) { return nil }
            if reportErrors {
                error = err.localizedDescription
            }
            isLoading = false
            return err.localizedDescription
        }
    }
}
