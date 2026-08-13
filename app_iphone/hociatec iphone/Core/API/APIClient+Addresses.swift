import Foundation

extension APIClient {
    func createAddress(
        label: String,
        address: String,
        postalCode: String,
        city: String,
        isDefault: Bool
    ) async throws {
        let body: [String: Any] = [
            "name": label,
            "address": address,
            "postalCode": postalCode,
            "city": city,
            "isDefault": isDefault
        ]
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
        label: String,
        address: String,
        postalCode: String,
        city: String,
        isDefault: Bool
    ) async throws {
        let body: [String: Any] = [
            "name": label,
            "address": address,
            "postalCode": postalCode,
            "city": city
        ]
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
