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
                }

                Section("Mes historiques") {
                    NavigationLink {
                        MyAppointmentsView(service: container.services.appointments)
                    } label: {
                        Label("Mes rendez-vous", systemImage: "calendar")
                    }
                    NavigationLink {
                        OrdersView(service: container.services.orders)
                    } label: {
                        Label("Mes commandes", systemImage: "bag")
                    }
                    NavigationLink {
                        MyQuotesListView(viewModel: container.makeMyQuotesViewModel())
                    } label: {
                        Label("Mes devis", systemImage: "doc.text")
                    }
                    NavigationLink {
                        FavoritesScreen(service: container.services.favorites)
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
                        ContactView(service: container.services.contact)
                    } label: {
                        Label("Contact", systemImage: "questionmark.circle")
                    }

                    NavigationLink {
                        AboutHubView(services: container.services)
                    } label: {
                        Label("À propos", systemImage: "info.circle")
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
                    NavigationLink {
                        LoginView(account: account, authService: container.services.account)
                    } label: {
                        Label("Connexion", systemImage: "person.crop.circle.badge.checkmark")
                            .fontWeight(.semibold)
                    }

                    NavigationLink {
                        RegisterView(account: account)
                    } label: {
                        Label("Inscription", systemImage: "person.crop.circle.badge.plus")
                            .fontWeight(.semibold)
                    }
                }

                Section {
                    NavigationLink {
                        AboutHubView(services: container.services)
                    } label: {
                        Label("À propos", systemImage: "info.circle")
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
