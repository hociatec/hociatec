import SwiftUI

struct GlobalSearchView: View {
    @StateObject private var viewModel: GlobalSearchViewModel

    init(services: AppServices) {
        _viewModel = StateObject(
            wrappedValue: GlobalSearchViewModel(
                productsService: services.products,
                servicesService: services.serviceCatalog,
                trainingService: services.training,
                newsService: services.news
            )
        )
    }

    var body: some View {
        List {
            GlobalSearchIntroSection()
            GlobalSearchControlsSection(viewModel: viewModel)
            GlobalSearchStatusSections(viewModel: viewModel)

            if viewModel.query.isEmpty {
                GlobalSearchEmptyQuerySection()
            } else if viewModel.isLoading {
                GlobalSearchLoadingSection()
            } else {
                GlobalSearchResultsView(viewModel: viewModel)
            }
        }
        .navigationTitle("Recherche")
        .onChangeCompat(viewModel.selectedFilter) { _ in
            guard !viewModel.query.isEmpty else { return }
            Task { await viewModel.search() }
        }
    }
}
