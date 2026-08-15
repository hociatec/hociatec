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
}

struct MyRentalsResponse: Decodable {
    let upcoming: [RentalItem]
    let past: [RentalItem]
}

struct RentalData: Decodable {
    let rental: RentalItem
}
