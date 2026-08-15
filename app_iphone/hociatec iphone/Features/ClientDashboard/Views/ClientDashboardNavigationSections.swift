import SwiftUI

struct ClientDashboardQuickAccessSection: View {
    var body: some View {
        ClientDashboardNavigationSection(
            title: "Accès rapides",
            entries: [
                .init(title: "Commandes", systemImage: "shippingbox", destination: .orders),
                .init(title: "Devis", systemImage: "doc.text", destination: .quotes),
                .init(title: "SAV", systemImage: "wrench.and.screwdriver", destination: .support),
                .init(title: "Favoris", systemImage: "heart", destination: .favorites),
            ]
        )
    }
}

struct ClientDashboardServicesSection: View {
    var body: some View {
        ClientDashboardNavigationSection(
            title: "Services",
            entries: [
                .init(title: "Rendez-vous", systemImage: "calendar", destination: .appointments),
                .init(title: "Formations", systemImage: "graduationcap", destination: .trainings),
                .init(title: "Reprises", systemImage: "arrow.triangle.2.circlepath", destination: .tradeIns),
                .init(title: "Audits", systemImage: "checklist", destination: .audits),
            ]
        )
    }
}

struct ClientDashboardProgramsSection: View {
    var body: some View {
        ClientDashboardNavigationSection(
            title: "Programmes",
            entries: [
                .init(title: "Bons", systemImage: "ticket", destination: .vouchers),
                .init(title: "Programme bêta", systemImage: "flask", destination: .beta),
            ]
        )
    }
}

struct ClientDashboardSettingsSection: View {
    var body: some View {
        ClientDashboardNavigationSection(
            title: "Paramètres",
            entries: [
                .init(title: "Profil", systemImage: "person.text.rectangle", destination: .profile),
                .init(title: "Adresses", systemImage: "mappin.and.ellipse", destination: .addresses),
                .init(title: "Préférences de communication", systemImage: "bell.badge", destination: .communicationPreferences),
                .init(title: "Révoquer les accès", systemImage: "laptopcomputer.and.iphone", destination: .accessSessions),
            ]
        )
    }
}

private struct ClientDashboardNavigationSection: View {
    let title: String
    let entries: [ClientDashboardNavigationEntry]

    var body: some View {
        Section(title) {
            ForEach(entries) { entry in
                NavigationLink {
                    ClientDashboardActionDestinationView(
                        action: ClientDashboardAction(
                            id: entry.id,
                            title: entry.title,
                            detail: "",
                            destination: entry.destination
                        )
                    )
                } label: {
                    Label(entry.title, systemImage: entry.systemImage)
                }
            }
        }
    }
}

private struct ClientDashboardNavigationEntry: Identifiable {
    let title: String
    let systemImage: String
    let destination: ClientDashboardAction.Destination

    var id: String {
        "\(destination)-\(title)"
    }
}
