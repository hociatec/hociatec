import Foundation

struct AddressListData: Decodable {
    let items: [UserAddress]
}

struct UserAddress: Codable, Identifiable, Equatable {
    let id: Int?
    var type: String
    var label: String
    var address: String
    var addressComplement: String?
    var postalCode: String
    var city: String
    var company: String?
    var companySiren: String?
    var companyVatNumber: String?
    var isDefault: Bool

    private enum CodingKeys: String, CodingKey {
        case id
        case type
        case label = "name"
        case address
        case addressComplement
        case postalCode
        case city
        case company
        case companySiren
        case companyVatNumber
        case isDefault
    }

    var typeLabel: String {
        type == "professional" ? "Professionnelle" : "Personnelle"
    }

    var formattedLines: [String] {
        var lines: [String] = []

        if type == "professional", let company, !company.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
            lines.append(company)
        }

        lines.append(address)

        if let addressComplement, !addressComplement.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
            lines.append(addressComplement)
        }

        lines.append("\(postalCode) \(city)")

        if type == "professional", let companySiren, !companySiren.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
            lines.append("SIREN : \(companySiren)")
        }

        if type == "professional", let companyVatNumber, !companyVatNumber.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
            lines.append("TVA : \(companyVatNumber)")
        }

        return lines
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
