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

    static let frDateTime: DateFormatter = {
        let formatter = DateFormatter()
        formatter.locale = Locale(identifier: "fr_FR")
        formatter.dateStyle = .medium
        formatter.timeStyle = .short
        return formatter
    }()

    static let apiDay: DateFormatter = {
        let formatter = DateFormatter()
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.calendar = Calendar(identifier: .gregorian)
        // API day values are calendar dates without time; keep the user's local day
        // when encoding/decoding to avoid off-by-one shifts on iPhone time zones.
        formatter.timeZone = .autoupdatingCurrent
        formatter.dateFormat = "yyyy-MM-dd"
        return formatter
    }()
}

enum DatePresentation {
    static func parseAPIDay(_ value: String?) -> Date? {
        guard let value, !value.isEmpty else { return nil }
        return DateFormatters.apiDay.date(from: value)
    }

    static func formatAPIDay(_ value: String?) -> String {
        guard let date = parseAPIDay(value) else { return value ?? "-" }
        return DateFormatters.frDay.string(from: date)
    }

    static func encodeAPIDay(_ date: Date) -> String {
        DateFormatters.apiDay.string(from: date)
    }
}
