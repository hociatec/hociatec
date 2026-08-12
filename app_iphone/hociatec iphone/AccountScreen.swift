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
