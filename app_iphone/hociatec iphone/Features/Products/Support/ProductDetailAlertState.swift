import Foundation

struct ProductDetailAlertState {
    var showStockAlert = false
    var stockAlertMessage = ""
    var showAddAlert = false
    var addedProductName = ""

    mutating func presentStock(message: String) {
        stockAlertMessage = message
        showStockAlert = true
    }

    mutating func presentAddConfirmation(productName: String) {
        addedProductName = productName
        showAddAlert = true
    }
}
