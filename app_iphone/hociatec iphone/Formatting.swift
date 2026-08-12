import Foundation

enum PriceFormatter {
    static func format(cents: Int) -> String {
        let formatter = NumberFormatter()
        formatter.numberStyle = .currency
        formatter.locale = Locale(identifier: "fr_FR")
        formatter.maximumFractionDigits = 2
        formatter.minimumFractionDigits = 2

        let value = Decimal(cents) / 100
        return formatter.string(from: value as NSDecimalNumber) ?? "\(value) €"
    }
}

extension SellingType {
    var label: String {
        switch self {
        case .sale: return "Vente"
        case .rental: return "Location"
        case .unknown: return "N/A"
        }
    }
}


enum DateFormatters {
    static let frTime: DateFormatter = {
        let formatter = DateFormatter()
        formatter.locale = Locale(identifier: "fr_FR")
        formatter.timeStyle = .short
        formatter.dateStyle = .none
        return formatter
    }()

    static let frDay: DateFormatter = {
        let formatter = DateFormatter()
        formatter.locale = Locale(identifier: "fr_FR")
        formatter.dateFormat = "dd/MM/yyyy"
        return formatter
    }()
}
