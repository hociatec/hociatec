import SwiftUI

struct AccountScreen: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var account: AccountViewModel
    @State private var showDeleteConfirmation = false

    var body: some View {
        Form {
            if let error = account.error, !error.isEmpty {
                Section { Text(error).foregroundStyle(.red) }
            }

            if account.isLoggedIn {
                Section {
                    if let profile = account.profile {
                        VStack(alignment: .leading, spacing: 6) {
                            Text("\(profile.firstName) \(profile.lastName)")
                                .font(.headline)
                            Text(profile.email)
                                .foregroundStyle(.secondary)
                        }
                    } else if account.isLoading {
                        ProgressView("Chargement du profil...")
                    } else {
                        Text("Profil indisponible.")
                            .foregroundStyle(.secondary)
                    }

                    NavigationLink {
                        ProfileView(account: account)
                    } label: {
                        Label("Mon profil", systemImage: "person.text.rectangle")
                    }
                    NavigationLink {
                        AddressesManagerView(account: account)
                    } label: {
                        Label("Adresses", systemImage: "house")
                    }
                }

                Section("Mes historiques") {
                    NavigationLink {
                        MyAppointmentsView(api: container.api)
                    } label: {
                        Label("Mes rendez-vous", systemImage: "calendar")
                    }
                    NavigationLink {
                        OrdersView(api: container.api)
                    } label: {
                        Label("Mes commandes", systemImage: "bag")
                    }
                    NavigationLink {
                        MyQuotesListView(api: container.api)
                    } label: {
                        Label("Mes devis", systemImage: "doc.text")
                    }
                    NavigationLink {
                        FavoritesScreen(api: container.api)
                    } label: {
                        Label("Mes favoris", systemImage: "heart")
                    }
                    NavigationLink {
                        PendingReviewsView()
                    } label: {
                        Label("Avis à donner", systemImage: "star.bubble")
                    }
                }

                Section {
                    NavigationLink {
                        ContactView(api: container.api)
                    } label: {
                        Label("Contact", systemImage: "questionmark.circle")
                    }
                }

                Section("Sécurité") {
                    Button("Se déconnecter", role: .destructive) {
                        Task { await account.logout() }
                    }
                    Button(role: .destructive) {
                        showDeleteConfirmation = true
                    } label: {
                        Text("Supprimer mon compte")
                    }
                    .confirmationDialog("Êtes-vous sûr de vouloir supprimer votre compte ?", isPresented: $showDeleteConfirmation, titleVisibility: .visible) {
                        Button("Supprimer mon compte", role: .destructive) {
                            Task { await account.deleteAccount() }
                        }
                        Button("Annuler", role: .cancel) {}
                    }
                }
            } else {
                Section {
                    TextField("Email", text: $account.email)
                        .textInputAutocapitalization(.never)
                        .keyboardType(.emailAddress)
                        .textContentType(.username)
                    SecureField("Mot de passe", text: $account.password)
                        .textContentType(.password)
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

                    NavigationLink {
                        ForgotPasswordView(api: container.api, initialEmail: account.email)
                    } label: {
                        Text("Mot de passe oublié ?")
                            .font(.footnote)
                    }
                }

                Section {
                    NavigationLink {
                        RegisterView(account: account)
                    } label: {
                        HStack {
                            Text("Pas encore de compte ?")
                                .foregroundStyle(.secondary)
                            Spacer()
                            Text("Créer un compte")
                                .fontWeight(.semibold)
                        }
                    }
                }
            }
        }
        .navigationTitle("Compte")
        .task { await account.loadProfileIfPossible() }
        .refreshable {
            if account.isLoggedIn {
                await account.refreshProfile()
            }
        }
    }
}

private struct ForgotPasswordView: View {
    let api: APIClient
    let initialEmail: String

    @State private var email: String
    @State private var isSubmitting = false
    @State private var successMessage: String?
    @State private var errorMessage: String?

    init(api: APIClient, initialEmail: String) {
        self.api = api
        self.initialEmail = initialEmail
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
                    ResetPasswordView(api: api)
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
            try await api.requestPasswordReset(email: trimmedEmail)
            successMessage = "Si un compte existe, un e-mail vient d’être envoyé."
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}

private struct ResetPasswordView: View {
    let api: APIClient

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
                .disabled(isSubmitting || token.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty || password.isEmpty || confirmPassword.isEmpty)
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
            try await api.resetPassword(token: trimmedToken, password: password, confirmPassword: confirmPassword)
            successMessage = "Votre mot de passe a été réinitialisé."
            try? await Task.sleep(nanoseconds: 1_000_000_000)
            dismiss()
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}
