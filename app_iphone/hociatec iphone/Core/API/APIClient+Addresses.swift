import Foundation

extension APIClient {
    func createAddress(
        type: String,
        label: String,
        address: String,
        addressComplement: String?,
        postalCode: String,
        company: String?,
        companySiren: String?,
        companyVatNumber: String?,
        city: String,
        isDefault: Bool
    ) async throws {
        var body: [String: Any] = [
            "type": type,
            "name": label,
            "address": address,
            "postalCode": postalCode,
            "city": city,
            "isDefault": isDefault
        ]
        if let addressComplement, !addressComplement.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
            body["addressComplement"] = addressComplement
        }
        body["company"] = type == "professional" && company?.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty == false ? company : NSNull()
        body["companySiren"] = type == "professional" && companySiren?.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty == false ? companySiren : NSNull()
        body["companyVatNumber"] = type == "professional" && companyVatNumber?.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty == false ? companyVatNumber : NSNull()
        try await send(
            path: "api/addresses",
            method: "POST",
            body: body,
            authorized: true,
            attachCartToken: false
        )
    }

    func updateAddress(
        id: Int,
        type: String,
        label: String,
        address: String,
        addressComplement: String?,
        postalCode: String,
        company: String?,
        companySiren: String?,
        companyVatNumber: String?,
        city: String,
        isDefault: Bool
    ) async throws {
        var body: [String: Any] = [
            "type": type,
            "name": label,
            "address": address,
            "postalCode": postalCode,
            "city": city
        ]
        body["addressComplement"] = addressComplement?.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty == false
            ? addressComplement
            : NSNull()
        body["company"] = type == "professional" && company?.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty == false ? company : NSNull()
        body["companySiren"] = type == "professional" && companySiren?.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty == false ? companySiren : NSNull()
        body["companyVatNumber"] = type == "professional" && companyVatNumber?.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty == false ? companyVatNumber : NSNull()
        try await send(
            path: "api/addresses/\(id)",
            method: "PUT",
            body: body,
            authorized: true,
            attachCartToken: false
        )
        if isDefault {
            try await setDefaultAddress(id: id)
        }
    }

    func deleteAddress(id: Int) async throws {
        try await send(
            path: "api/addresses/\(id)",
            method: "DELETE",
            authorized: true,
            attachCartToken: false
        )
    }

    func setDefaultAddress(id: Int) async throws {
        try await send(
            path: "api/addresses/\(id)/default",
            method: "PUT",
            authorized: true,
            attachCartToken: false
        )
    }

    func listAddresses() async throws -> [UserAddress] {
        let data: AddressListData = try await request(
            path: "api/addresses/me",
            authorized: true,
            attachCartToken: false
        )
        return data.items
    }
}
