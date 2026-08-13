import Foundation

struct TradeInMetadata: Decodable {
    let categories: [TradeInOption]
    let conditions: [TradeInOption]
}

struct TradeInOption: Decodable, Identifiable, Hashable {
    let value: String
    let label: String

    var id: String { value }
}

struct TradeInSummary: Decodable, Identifiable {
    let id: Int
    let reference: String
    let status: String
    let statusLabel: String
    let category: String
    let categoryLabel: String
    let productName: String
    let purchasePriceCents: Int
    let purchaseYear: Int
    let brand: String?
    let model: String?
    let conditionGrade: String
    let conditionLabel: String
    let functional: Bool
    let hasAccessories: Bool
    let hasProofOfPurchase: Bool
    let description: String
    let estimatedMinCents: Int?
    let estimatedMaxCents: Int?
    let createdAt: Date
}

struct TradeInRequestPayload {
    let firstName: String
    let lastName: String
    let email: String
    let phone: String
    let category: String
    let productName: String
    let purchasePriceCents: Int
    let purchaseYear: Int
    let brand: String?
    let model: String?
    let serialNumber: String?
    let conditionGrade: String
    let functional: Bool
    let hasAccessories: Bool
    let hasProofOfPurchase: Bool
    let description: String
    let catalogProductId: Int?
    let consent: Bool
}
