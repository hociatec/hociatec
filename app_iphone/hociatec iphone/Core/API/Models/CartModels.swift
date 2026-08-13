import Foundation

struct CartItem: Decodable, Identifiable {
    let id: Int
    let product: Product
    let quantity: Int
    let linePriceCents: Int
    let rentalMonths: Int?
}

struct Cart: Decodable {
    let token: String
    let items: [CartItem]
    let totalQuantity: Int
    let totalPriceCents: Int
    let updatedAt: Date?
}

struct CartData: Decodable {
    let cart: Cart
}
