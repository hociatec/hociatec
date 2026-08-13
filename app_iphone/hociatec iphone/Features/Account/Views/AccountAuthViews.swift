import SwiftUI

struct LoginView: View {
    @ObservedObject var account: AccountViewModel
    let authService: AccountServing

    var body: some View {
        Form {
            if let error = account.error, !error.isEmpty {
                Section {
                    Text(error).foregroundStyle(.red)
                }
            }

            Section {
                TextField("Email", text: $account.email)
                    .textInputAutocapitalization(.never)
                    .keyboardType(.emailAddress)
                    .textContentType(.username)
                SecureField("Mot de passe", text: $account.password)
                    .textContentType(.password)
            }

            Section {
                Button {
                    Task { await account.login() }
                } label: {
                    if account.isLoading {
                        ProgressView()
                            .frame(maxWidth: .infinity, alignment: .center)
                    } else {
                        Text("Se connecter")
                            .fontWeight(.semibold)
                            .frame(maxWidth: .infinity, alignment: .center)
                    }
                }
                .disabled(account.isLoading)
            }

            Section {
                NavigationLink {
                    ForgotPasswordView(service: authService, initialEmail: account.email)
                } label: {
                    Text("Mot de passe oublié ?")
                        .font(.footnote)
                }
            }
        }
        .navigationTitle("Connexion")
    }
}

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

            if let successMessage {
                Section {
                    Text(successMessage)
                        .foregroundStyle(.green)
                }
            }

            if let errorMessage {
                Section {
                    Text(errorMessage)
                        .foregroundStyle(.red)
                }
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

struct ResetPasswordView: View {
    let service: AccountServing

    @Environment(\.dismiss) private var dismiss
    @State private var token = ""
    @State private var password = ""
    @State private var confirmPassword = ""
    @State private var isSubmitting = false
    @State private var successMessage: String?
    @State private var errorMessage: String?

    private let passwordRule = /^(?=.*[A-Z])(?=.*\d).{8,}$/

    var body: some View {
        Form {
            Section {
                TextField("Token", text: $token)
                    .textInputAutocapitalization(.never)
                    .autocorrectionDisabled()
                SecureField("Nouveau mot de passe", text: $password)
                SecureField("Confirmation", text: $confirmPassword)
                Text("Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }

            if let successMessage {
                Section {
                    Text(successMessage)
                        .foregroundStyle(.green)
                }
            }

            if let errorMessage {
                Section {
                    Text(errorMessage)
                        .foregroundStyle(.red)
                }
            }

            Section {
                Button {
                    Task { await submit() }
                } label: {
                    if isSubmitting {
                        ProgressView()
                            .frame(maxWidth: .infinity)
                    } else {
                        Text("Enregistrer mon nouveau mot de passe")
                            .fontWeight(.semibold)
                            .frame(maxWidth: .infinity)
                    }
                }
                .disabled(
                    isSubmitting
                    || token.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
                    || password.isEmpty
                    || confirmPassword.isEmpty
                )
            }
        }
        .navigationTitle("Nouveau mot de passe")
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
            successMessage = "Votre mot de passe a été réinitialisé."
            try? await Task.sleep(nanoseconds: 1_000_000_000)
            dismiss()
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}
