import Foundation

enum RentalRequestAction: String, Codable {
    case extend
    case endEarly = "end_early"
}

struct RentalRequestState: Decodable {
    let status: String
    let type: String?
    let requestedEndDate: String?
    let createdAt: Date?
}

struct RentalExtensionState: Decodable {
    let orderId: Int?
    let sourceOrderItemId: Int?
    let checkoutSessionId: String?
    let checkoutUrl: String?
    let checkoutStatus: String?
}

struct RentalReturnPlanState: Decodable {
    let status: String
    let mode: String?
    let requestedDate: String?
    let requestedAt: Date?
    let completedAt: Date?
}

struct RentalItem: Decodable, Identifiable {
    var id: Int { orderItemId }

    let orderItemId: Int
    let orderId: Int?
    let orderNumber: String?
    let productName: String
    let productSku: String
    let quantity: Int
    let linePriceCents: Int
    let rentalMonths: Int?
    let startDate: String?
    let endDate: String?
    let timelineStatus: String
    let timelineStatusLabel: String
    let request: RentalRequestState
    let extensionState: RentalExtensionState
    let returnPlan: RentalReturnPlanState

    private enum CodingKeys: String, CodingKey {
        case orderItemId
        case orderId
        case orderNumber
        case productName
        case productSku
        case quantity
        case linePriceCents
        case rentalMonths
        case startDate
        case endDate
        case timelineStatus
        case timelineStatusLabel
        case request
        case extensionState = "extension"
        case returnPlan
    }
}

struct MyRentalsResponse: Decodable {
    let upcoming: [RentalItem]
    let past: [RentalItem]
}

struct RentalData: Decodable {
    let rental: RentalItem
}

struct RentalCheckoutState: Decodable {
    let mode: String
    let orderId: Int?
    let checkoutUrl: String?
    let checkoutSessionId: String?
}

struct RentalChangeData: Decodable {
    let rental: RentalItem
    let checkout: RentalCheckoutState?
}
