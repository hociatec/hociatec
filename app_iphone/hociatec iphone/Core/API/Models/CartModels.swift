import Foundation

struct CartItem: Decodable, Identifiable {
    let id: Int
    let product: Product
    let sellingType: SellingType
    let quantity: Int
    let linePriceCents: Int
    let rentalMonths: Int?
    let rentalStartDate: String?
    let rentalEndDate: String?

    var identityKey: String {
        "\(product.id)-\(sellingType.rawValue)-\(rentalMonths ?? 0)-\(rentalStartDate ?? "")"
    }

    func matches(productId: Int, sellingType: SellingType, rentalMonths: Int?, rentalStartDate: String?) -> Bool {
        product.id == productId
            && self.sellingType == sellingType
            && self.rentalMonths == rentalMonths
            && self.rentalStartDate == rentalStartDate
    }
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
