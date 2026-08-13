import Foundation

extension String {
    var trimmedForTradeInSubmission: String {
        trimmingCharacters(in: .whitespacesAndNewlines)
    }

    var nonEmptyTradeInValue: String? {
        isEmpty ? nil : self
    }
}
