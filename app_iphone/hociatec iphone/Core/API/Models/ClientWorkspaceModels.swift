import Foundation

struct CommunicationPreferenceChoice: Decodable, Hashable {
    let value: String
    let label: String
    let description: String
}

struct CommunicationPreferencesData: Decodable {
    let preferences: [String]
    let choices: [CommunicationPreferenceChoice]
}

struct LoyaltyBalance: Decodable {
    let points: Int
    let euroCents: Int
    let pointsPerEuroEarned: Int
    let pointsPerEuroConverted: Int
}

struct LoyaltyBalancePayload: Decodable {
    let loyalty: LoyaltyBalance
}

struct VoucherSummary: Decodable {
    let id: Int
    let code: String
    let name: String
    let discountType: String
    let discountValue: Int
    let description: String?
}

struct LoyaltyConversionData: Decodable {
    let loyalty: LoyaltyBalance
    let voucher: VoucherSummary
}
