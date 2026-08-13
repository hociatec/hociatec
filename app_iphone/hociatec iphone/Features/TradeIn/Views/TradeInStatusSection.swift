import SwiftUI

struct TradeInStatusSection: View {
    let error: String?
    let successMessage: String?

    var body: some View {
        if let error, !error.isEmpty {
            Section {
                Text(error)
                    .foregroundStyle(.red)
            }
        }

        if let successMessage, !successMessage.isEmpty {
            Section {
                Label(successMessage, systemImage: "checkmark.seal.fill")
                    .foregroundStyle(.green)
            }
        }
    }
}
