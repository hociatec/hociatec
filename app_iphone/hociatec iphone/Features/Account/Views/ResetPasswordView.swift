import SwiftUI

struct ResetPasswordView: View {
    let service: AccountServing

    @Environment(\.dismiss) private var dismiss
    @State private var token = ""
    @State private var password = ""
    @State private var confirmPassword = ""
    @State private var isSubmitting = false
    @State private var successMessage: String?
    @State private var errorMessage: String?
    @State private var shouldDismissAfterSuccess = false

    private let passwordRule = /^(?=.*[A-Z])(?=.*\d).{8,}$/
    private let allowsTokenEditing: Bool

    init(
        service: AccountServing,
        initialToken: String = "",
        allowsTokenEditing: Bool = true
    ) {
        self.service = service
        self.allowsTokenEditing = allowsTokenEditing
        _token = State(initialValue: initialToken)
    }

    var body: some View {
        Form {
            ResetPasswordFormSection(
                token: $token,
                password: $password,
                confirmPassword: $confirmPassword,
                allowsTokenEditing: allowsTokenEditing
            )
            ResetPasswordSubmitSection(
                isSubmitting: isSubmitting,
                isDisabled: isSubmitDisabled,
                onSubmit: { Task { await submit() } }
            )
        }
        .navigationTitle("Nouveau mot de passe")
        .feedbackDialog(error: $errorMessage, success: $successMessage) {
            if shouldDismissAfterSuccess {
                shouldDismissAfterSuccess = false
                dismiss()
            }
        }
    }

    private var isSubmitDisabled: Bool {
        isSubmitting
            || token.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
            || password.isEmpty
            || confirmPassword.isEmpty
    }

    private func submit() async {
        let trimmedToken = token.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !trimmedToken.isEmpty else { return }
        guard password == confirmPassword else {
            errorMessage = "Les mots de passe doivent être identiques."
            return
        }
        guard password.wholeMatch(of: passwordRule) != nil else {
            errorMessage = "Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre."
            return
        }
        guard !isSubmitting else { return }

        isSubmitting = true
        errorMessage = nil
        successMessage = nil
        defer { isSubmitting = false }

        do {
            try await service.resetPassword(
                token: trimmedToken,
                password: password,
                confirmPassword: confirmPassword
            )
            shouldDismissAfterSuccess = true
            successMessage = "Votre mot de passe a été réinitialisé."
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}
