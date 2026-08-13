import Foundation

struct AddressListData: Decodable {
    let items: [UserAddress]
}

struct UserAddress: Codable, Identifiable, Equatable {
    let id: Int?
    var label: String
    var address: String
    var postalCode: String
    var city: String
    var isDefault: Bool

    private enum CodingKeys: String, CodingKey {
        case id
        case label = "name"
        case address
        case postalCode
        case city
        case isDefault
    }
}

struct LoginResponse: Decodable {
    let token: String
}

struct AuthSessionData: Decodable {
    let authenticated: Bool
    let id: Int?
    let email: String?
    let firstName: String?
    let lastName: String?
    let roles: [String]?
    let address: String?
    let postalCode: String?
    let city: String?
    let birthDate: String?
    let phoneNumber: String?
    let gender: String?

    var profile: UserProfile? {
        guard authenticated,
              let id,
              let email,
              let firstName,
              let lastName,
              let roles,
              let birthDate,
              let phoneNumber
        else {
            return nil
        }

        return UserProfile(
            id: id,
            email: email,
            firstName: firstName,
            lastName: lastName,
            roles: roles,
            address: address,
            postalCode: postalCode,
            city: city,
            birthDate: birthDate,
            phoneNumber: phoneNumber,
            gender: gender,
            addresses: nil
        )
    }
}

struct CsrfTokenData: Decodable {
    let token: String
}

struct UserProfile: Codable, Identifiable {
    let id: Int
    let email: String
    let firstName: String
    let lastName: String
    let roles: [String]
    let address: String?
    let postalCode: String?
    let city: String?
    let birthDate: String
    let phoneNumber: String
    let gender: String?
    let addresses: [UserAddress]?

    var fullName: String {
        "\(firstName) \(lastName)"
    }
}
