import SwiftUI

struct AccountScreen: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var account: AccountViewModel

    var body: some View {
        Group {
            if account.isLoggedIn {
                ClientDashboardView(services: container.services)
                    .id("account-logged-in-\(account.profile?.id ?? 0)")
            } else {
                Form {
                    if let error = account.error, !error.isEmpty {
                        Section { Text(error).foregroundStyle(.red) }
                    }

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

                        NavigationLink {
                            BetaProgramView(service: container.services.beta)
                        } label: {
                            Label("Programme bêta", systemImage: "flask")
                        }
                    }
                }
                .id("account-logged-out")
            }
        }
        .task { await account.loadProfileIfPossible() }
        .refreshable {
            if account.isLoggedIn {
                await account.refreshProfile()
            }
        }
    }
}
