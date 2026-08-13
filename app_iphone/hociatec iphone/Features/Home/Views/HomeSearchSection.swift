import SwiftUI

struct HomeSearchSection: View {
    @EnvironmentObject private var container: AppContainer

    var body: some View {
        Section {
            NavigationLink {
                GlobalSearchView(services: container.services)
            } label: {
                Label("Recherche globale", systemImage: "magnifyingglass")
                    .fontWeight(.semibold)
            }
        }
    }
}
