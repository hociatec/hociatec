import SwiftUI

struct ClientDashboardHeroSection: View {
    let firstName: String?

    var body: some View {
        Section {
            VStack(alignment: .leading, spacing: 10) {
                Text(firstName ?? "Bonjour")
                    .font(.caption.weight(.semibold))
                    .foregroundStyle(.secondary)
                Text("Votre espace en un coup d'oeil")
                    .font(.title2.weight(.bold))
                Text("Suivez vos dossiers actifs, vos avantages et vos prochaines actions depuis une seule page.")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
            .padding(.vertical, 8)
        }
    }
}

struct ClientDashboardStatusSections: View {
    let error: String?
    let partialError: Bool

    var body: some View {
        Group {
            if let error, !error.isEmpty {
                Section {
                    Text(error)
                        .foregroundStyle(.red)
                }
            }

            if partialError {
                Section {
                    Text("Certaines données n’ont pas pu être chargées. Les accès restent disponibles.")
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                }
            }
        }
    }
}

struct ClientDashboardActionsSection: View {
    let isLoading: Bool
    let actions: [ClientDashboardAction]

    var body: some View {
        Section("Actions prioritaires") {
            if isLoading && actions.isEmpty {
                ProgressView("Chargement...")
            } else if actions.isEmpty {
                Text("Aucune action prioritaire pour le moment.")
                    .foregroundStyle(.secondary)
            } else {
                ForEach(actions) { action in
                    NavigationLink {
                        ClientDashboardActionDestinationView(action: action)
                    } label: {
                        ClientDashboardActionRow(action: action)
                    }
                }
            }
        }
    }
}

struct ClientDashboardLoyaltySection: View {
    @ObservedObject var viewModel: ClientDashboardViewModel

    var body: some View {
        Section("Fidélité") {
            LabeledContent("Points disponibles") {
                Text("\(viewModel.loyalty.points) pts")
                    .fontWeight(.semibold)
            }
            LabeledContent("Valeur estimée") {
                Text(PriceFormatter.format(cents: viewModel.loyalty.euroCents))
                    .fontWeight(.semibold)
            }
            LabeledContent("Conversion minimale") {
                Text("\(viewModel.loyalty.pointsPerEuroConverted) pts / 1 €")
            }

            TextField("Points à convertir", text: $viewModel.convertPoints)
                .keyboardType(.numberPad)

            Button {
                Task { await viewModel.convertLoyalty() }
            } label: {
                Label("Créer un bon de réduction", systemImage: "ticket")
            }
            .disabled(!viewModel.canConvert)

            if let message = viewModel.conversionMessage, !message.isEmpty {
                Text(message)
                    .font(.footnote)
                    .foregroundStyle(.green)
            }
        }
    }
}

struct ClientDashboardAccountSection: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var account: AccountViewModel

    var body: some View {
        Section("Mon compte") {
            NavigationLink {
                ProfileView(account: account)
            } label: {
                Label("Mon profil", systemImage: "person.text.rectangle")
            }

            NavigationLink {
                CommunicationPreferencesView(service: container.services.workspace)
            } label: {
                Label("Préférences de communication", systemImage: "bell.badge")
            }

            NavigationLink {
                AddressesManagerView(account: account)
            } label: {
                Label("Mes adresses", systemImage: "mappin.and.ellipse")
            }

            NavigationLink {
                BetaProgramView(service: container.services.beta)
            } label: {
                Label("Mon espace bêta", systemImage: "flask")
            }
        }
    }
}

struct ClientDashboardInformationSection: View {
    @EnvironmentObject private var container: AppContainer

    var body: some View {
        Section("Informations") {
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
    }
}

struct ClientDashboardSecuritySection: View {
    @EnvironmentObject private var account: AccountViewModel
    @Binding var showDeleteConfirmation: Bool

    var body: some View {
        Section("Sécurité") {
            Button("Se déconnecter", role: .destructive) {
                Task { await account.logout() }
            }

            Button(role: .destructive) {
                showDeleteConfirmation = true
            } label: {
                Text("Supprimer mon compte")
            }
        }
    }
}
