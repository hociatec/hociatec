import SwiftUI

struct TradeInSubmitSection: View {
    let isSubmitting: Bool
    let onSubmit: () async -> Void

    var body: some View {
        Section {
            Button {
                Task {
                    await onSubmit()
                }
            } label: {
                if isSubmitting {
                    ProgressView()
                        .frame(maxWidth: .infinity)
                } else {
                    Text("Envoyer la reprise")
                        .fontWeight(.semibold)
                        .frame(maxWidth: .infinity)
                }
            }
            .disabled(isSubmitting)
        }
    }
}
