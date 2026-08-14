import SwiftUI

struct HomeScreen: View {
    @EnvironmentObject private var account: AccountViewModel
    @StateObject private var home: HomeViewModel
    private let workspaceService: WorkspaceServing

    init(services: AppServices) {
        self.workspaceService = services.workspace
        _home = StateObject(wrappedValue: HomeViewModel(
            productsService: services.products,
            serviceCatalogService: services.serviceCatalog,
            newsService: services.news
        ))
    }

    var body: some View {
        List {
            HomeNotificationsShortcutSection(workspaceService: workspaceService)
            HomeSearchSection()
            HomeIntroSection()
            HomeServicesSection(home: home)
            HomeFeaturedProductsSection(home: home)
            HomeNewsSection(home: home)
        }
        .navigationTitle("Accueil")
        .task { await home.load() }
        .refreshable {
            await home.load(force: true)
        }
        .feedbackDialog(error: $home.error)
    }
}
