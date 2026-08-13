import SwiftUI

struct ResetPasswordSuccessSection: View {
    let message: String?

    var body: some View {
        if let message {
            Section {
                Text(message)
                    .foregroundStyle(.green)
            }
        }
    }
}

struct ResetPasswordErrorSection: View {
    let message: String?

    var body: some View {
        if let message {
            Section {
                Text(message)
                    .foregroundStyle(.red)
            }
        }
    }
}
