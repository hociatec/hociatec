import SwiftUI

struct LoginView: View {
    @ObservedObject var account: AccountViewModel
    let authService: AccountServing
    @Environment(\.dismiss) private var dismiss

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
                Toggle("Rester connecté", isOn: $account.rememberSession)
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
                .disabled(account.isLoading || isSubmitDisabled)
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
        .onChangeCompat(account.isLoggedIn) { isLoggedIn in
            guard isLoggedIn else { return }
            dismiss()
        }
    }

    private var isSubmitDisabled: Bool {
        account.email.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
            || account.password.isEmpty
    }
}
