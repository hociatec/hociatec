import SwiftUI

struct ResetPasswordSubmitSection: View {
    let isSubmitting: Bool
    let isDisabled: Bool
    let onSubmit: () -> Void

    var body: some View {
        Section {
            Button(action: onSubmit) {
                if isSubmitting {
                    ProgressView()
                        .frame(maxWidth: .infinity)
                } else {
                    Text("Enregistrer mon nouveau mot de passe")
                        .fontWeight(.semibold)
                        .frame(maxWidth: .infinity)
                }
            }
            .disabled(isDisabled)
        }
    }
}
