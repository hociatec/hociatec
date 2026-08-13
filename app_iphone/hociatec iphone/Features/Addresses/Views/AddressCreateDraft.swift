import Foundation

struct AddressCreateDraft {
    var label = ""
    var address = ""
    var postalCode = ""
    var city = ""
    var isDefault = false

    var isValid: Bool {
        !label.isEmpty && !address.isEmpty && !postalCode.isEmpty && !city.isEmpty
    }

    mutating func reset() {
        label = ""
        address = ""
        postalCode = ""
        city = ""
        isDefault = false
    }
}
