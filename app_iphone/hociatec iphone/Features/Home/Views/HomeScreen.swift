import SwiftUI

struct HomeScreen: View {
    @StateObject private var home: HomeViewModel

    init(services: AppServices) {
        _home = StateObject(wrappedValue: HomeViewModel(
            productsService: services.products,
            serviceCatalogService: services.serviceCatalog,
            newsService: services.news
        ))
    }

    var body: some View {
        List {
            HomeIntroSection()
            HomeSearchSection()
            HomeServicesSection(home: home)
            HomeFeaturedProductsSection(home: home)
            HomeNewsSection(home: home)
        }
        .navigationTitle("Accueil")
        .task { await home.load() }
    }
}
