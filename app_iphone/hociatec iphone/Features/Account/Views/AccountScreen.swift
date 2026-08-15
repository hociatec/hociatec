import SwiftUI

struct AccountScreen: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var account: AccountViewModel
    @State private var didAttemptSessionRecovery = false

    var body: some View {
        Group {
            if account.isLoggedIn {
                ClientDashboardView(services: container.services)
            } else {
                Form {
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
                            ContactView(service: container.services.contact)
                        } label: {
                            Label("Contact", systemImage: "envelope")
                        }

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
            }
        }
        .task {
            guard !didAttemptSessionRecovery else { return }
            didAttemptSessionRecovery = true
            await account.loadProfileIfPossible()
        }
        .refreshable {
            if account.isLoggedIn {
                await account.refreshProfile()
            }
        }
    }
}
