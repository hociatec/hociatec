import SwiftUI

struct ClientDashboardQuickAccessSection: View {
    var body: some View {
        Section("Accès rapides") {
            DashboardOrdersLink()
            DashboardQuotesLink()
            DashboardSupportLink()
            DashboardFavoritesLink()
        }
    }
}

struct ClientDashboardServicesSection: View {
    var body: some View {
        Section("Services") {
            DashboardAppointmentsLink()
            DashboardTrainingsLink()
            DashboardTradeInsLink()
            DashboardAuditsLink()
        }
    }
}

struct ClientDashboardProgramsSection: View {
    var body: some View {
        Section("Programmes") {
            DashboardVouchersLink()
            DashboardBetaLink()
        }
    }
}

struct ClientDashboardSettingsSection: View {
    var body: some View {
        Section("Paramètres") {
            DashboardProfileLink()
            DashboardAddressesLink()
            DashboardCommunicationPreferencesLink()
        }
    }
}

private struct DashboardOrdersLink: View {
    @EnvironmentObject private var container: AppContainer

    var body: some View {
        NavigationLink {
            OrdersView(service: container.services.orders)
        } label: {
            Label("Commandes", systemImage: "shippingbox")
        }
    }
}

private struct DashboardQuotesLink: View {
    @EnvironmentObject private var container: AppContainer

    var body: some View {
        NavigationLink {
            MyQuotesListView(viewModel: container.makeMyQuotesViewModel())
        } label: {
            Label("Devis", systemImage: "doc.text")
        }
    }
}

private struct DashboardSupportLink: View {
    @EnvironmentObject private var container: AppContainer

    var body: some View {
        NavigationLink {
            MySupportRequestsView(service: container.services.support)
        } label: {
            Label("SAV", systemImage: "wrench.and.screwdriver")
        }
    }
}

private struct DashboardFavoritesLink: View {
    @EnvironmentObject private var container: AppContainer

    var body: some View {
        NavigationLink {
            FavoritesScreen(service: container.services.favorites)
        } label: {
            Label("Favoris", systemImage: "heart")
        }
    }
}

private struct DashboardAppointmentsLink: View {
    @EnvironmentObject private var container: AppContainer

    var body: some View {
        NavigationLink {
            MyAppointmentsView(service: container.services.appointments)
        } label: {
            Label("Rendez-vous", systemImage: "calendar")
        }
    }
}

private struct DashboardTrainingsLink: View {
    @EnvironmentObject private var container: AppContainer

    var body: some View {
        NavigationLink {
            ClientTrainingsView(service: container.services.training)
        } label: {
            Label("Formations", systemImage: "graduationcap")
        }
    }
}

private struct DashboardTradeInsLink: View {
    @EnvironmentObject private var container: AppContainer

    var body: some View {
        NavigationLink {
            MyTradeInsView(service: container.services.tradeIn)
        } label: {
            Label("Reprises", systemImage: "arrow.triangle.2.circlepath")
        }
    }
}

private struct DashboardAuditsLink: View {
    @EnvironmentObject private var container: AppContainer

    var body: some View {
        NavigationLink {
            MyAuditsView(service: container.services.audits)
        } label: {
            Label("Audits", systemImage: "checklist")
        }
    }
}

private struct DashboardVouchersLink: View {
    @EnvironmentObject private var container: AppContainer

    var body: some View {
        NavigationLink {
            MyVouchersView(service: container.services.vouchers)
        } label: {
            Label("Bons", systemImage: "ticket")
        }
    }
}

private struct DashboardBetaLink: View {
    @EnvironmentObject private var container: AppContainer

    var body: some View {
        NavigationLink {
            BetaProgramView(service: container.services.beta)
        } label: {
            Label("Programme bêta", systemImage: "flask")
        }
    }
}

private struct DashboardProfileLink: View {
    @EnvironmentObject private var account: AccountViewModel

    var body: some View {
        NavigationLink {
            ProfileView(account: account)
        } label: {
            Label("Profil", systemImage: "person.text.rectangle")
        }
    }
}

private struct DashboardAddressesLink: View {
    @EnvironmentObject private var account: AccountViewModel

    var body: some View {
        NavigationLink {
            AddressesManagerView(account: account)
        } label: {
            Label("Adresses", systemImage: "mappin.and.ellipse")
        }
    }
}

private struct DashboardCommunicationPreferencesLink: View {
    @EnvironmentObject private var container: AppContainer

    var body: some View {
        NavigationLink {
            CommunicationPreferencesView(service: container.services.workspace)
        } label: {
            Label("Préférences de communication", systemImage: "bell.badge")
        }
    }
}
