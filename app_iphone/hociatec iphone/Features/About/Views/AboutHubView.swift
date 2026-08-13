import SwiftUI

struct AboutHubView: View {
    let services: AppServices

    var body: some View {
        List {
            NavigationLink {
                ContactView(service: services.contact)
            } label: {
                Label("Contact", systemImage: "envelope")
            }

            NavigationLink {
                AboutStoryView()
            } label: {
                Label("Notre histoire", systemImage: "building.2")
            }

            NavigationLink {
                OpeningHoursView()
            } label: {
                Label("Horaires d'ouverture", systemImage: "clock")
            }

            NavigationLink {
                SocialLinksView()
            } label: {
                Label("Réseaux sociaux", systemImage: "network")
            }

            NavigationLink {
                LegalMenuView()
            } label: {
                Label("Informations légales", systemImage: "doc.text")
            }
        }
        .navigationTitle("À propos")
    }
}
