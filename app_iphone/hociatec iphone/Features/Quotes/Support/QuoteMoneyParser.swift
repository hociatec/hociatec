import Foundation

enum QuoteMoneyParser {
    static func string(fromCents cents: Int?) -> String {
        guard let cents else { return "" }
        let value = Double(cents) / 100.0
        if value == floor(value) {
            return String(Int(value))
        }
        return String(format: "%.2f", value).replacingOccurrences(of: ".", with: ",")
    }

    static func cents(from input: String) -> Int? {
        let cleaned = input
            .replacingOccurrences(of: "€", with: "")
            .replacingOccurrences(of: " ", with: "")
            .trimmingCharacters(in: .whitespacesAndNewlines)
            .replacingOccurrences(of: ",", with: ".")
        guard !cleaned.isEmpty else { return nil }
        guard let value = Double(cleaned), value >= 0 else { return nil }
        return Int((value * 100).rounded())
    }
}
