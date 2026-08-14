import SwiftUI

struct ForgotPasswordView: View {
    let service: AccountServing

    @State private var email: String
    @State private var isSubmitting = false
    @State private var successMessage: String?
    @State private var errorMessage: String?

    init(service: AccountServing, initialEmail: String) {
        self.service = service
        _email = State(initialValue: initialEmail)
    }

    var body: some View {
        Form {
            Section {
                Text("Saisissez l’adresse e-mail liée à votre compte. Si elle existe, un lien vous sera envoyé.")
                    .foregroundStyle(.secondary)
                TextField("Email", text: $email)
                    .keyboardType(.emailAddress)
                    .textInputAutocapitalization(.never)
                    .autocorrectionDisabled()
            }

            Section {
                Button {
                    Task { await submit() }
                } label: {
                    if isSubmitting {
                        ProgressView()
                            .frame(maxWidth: .infinity)
                    } else {
                        Text("Envoyer le lien de réinitialisation")
                            .fontWeight(.semibold)
                            .frame(maxWidth: .infinity)
                    }
                }
                .disabled(isSubmitting || email.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
            }

            Section {
                NavigationLink {
                    ResetPasswordView(service: service)
                } label: {
                    Text("J’ai déjà un token")
                }
            }
        }
        .navigationTitle("Mot de passe oublié")
        .feedbackDialog(error: $errorMessage, success: $successMessage)
    }

    private func submit() async {
        let trimmedEmail = email.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !trimmedEmail.isEmpty else { return }
        guard !isSubmitting else { return }

        isSubmitting = true
        errorMessage = nil
        successMessage = nil
        defer { isSubmitting = false }

        do {
            try await service.requestPasswordReset(email: trimmedEmail)
            successMessage = "Si un compte existe, un e-mail vient d’être envoyé."
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}
