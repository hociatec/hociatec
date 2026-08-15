import SwiftUI

struct LoginView: View {
    @ObservedObject var account: AccountViewModel
    let authService: AccountServing
    @Environment(\.dismiss) private var dismiss
    @FocusState private var focusedField: Field?

    private enum Field {
        case email
        case password
    }

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 24) {
                VStack(alignment: .leading, spacing: 8) {
                    Text("Connexion")
                        .font(.title2.bold())
                    Text("Saisissez vos identifiants dans une interface plus stable pendant l'edition.")
                        .font(.subheadline)
                        .foregroundStyle(.secondary)
                }
                .frame(maxWidth: .infinity, alignment: .leading)

                VStack(alignment: .leading, spacing: 16) {
                    TextField("Email", text: $account.email)
                        .textInputAutocapitalization(.never)
                        .keyboardType(.emailAddress)
                        .textContentType(.username)
                        .textFieldStyle(.roundedBorder)
                        .autocorrectionDisabled()
                        .focused($focusedField, equals: .email)
                        .submitLabel(.next)
                        .onSubmit {
                            focusedField = .password
                        }

                    SecureField("Mot de passe", text: $account.password)
                        .textContentType(.password)
                        .textFieldStyle(.roundedBorder)
                        .focused($focusedField, equals: .password)
                        .submitLabel(.go)
                        .onSubmit {
                            guard !account.isLoading, !isSubmitDisabled else { return }
                            focusedField = nil
                            Task { await account.login() }
                        }

                    Toggle("Rester connecté", isOn: $account.rememberSession)
                }
                .padding(20)
                .background(Color(.secondarySystemBackground))
                .clipShape(RoundedRectangle(cornerRadius: 20, style: .continuous))

                VStack(spacing: 12) {
                    Button {
                        focusedField = nil
                        Task { await account.login() }
                    } label: {
                        Group {
                            if account.isLoading {
                                ProgressView()
                                    .frame(maxWidth: .infinity, alignment: .center)
                            } else {
                                Text("Se connecter")
                                    .fontWeight(.semibold)
                                    .frame(maxWidth: .infinity, alignment: .center)
                            }
                        }
                        .padding(.vertical, 14)
                    }
                    .buttonStyle(.borderedProminent)
                    .disabled(account.isLoading || isSubmitDisabled)

                    NavigationLink {
                        ForgotPasswordView(service: authService, initialEmail: account.email)
                    } label: {
                        Text("Mot de passe oublié ?")
                            .font(.footnote)
                    }
                }
            }
            .padding(20)
        }
        .navigationTitle("Connexion")
        .navigationBarTitleDisplayMode(.inline)
        .scrollDismissesKeyboard(.interactively)
        .onChangeCompat(account.hasAuthenticatedSession) { hasAuthenticatedSession in
            guard hasAuthenticatedSession else { return }
            dismiss()
        }
        .feedbackDialog(error: $account.error)
    }

    private var isSubmitDisabled: Bool {
        account.email.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
            || account.password.isEmpty
    }
}
