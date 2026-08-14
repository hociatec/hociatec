import SwiftUI

struct TrainingsCatalogView: View {
    let service: TrainingServing
    @StateObject private var viewModel: TrainingsCatalogViewModel

    init(api: TrainingServing, initialSearch: String = "") {
        self.service = api
        let viewModel = TrainingsCatalogViewModel(service: api)
        viewModel.searchText = initialSearch
        viewModel.appliedSearch = initialSearch.trimmingCharacters(in: .whitespacesAndNewlines)
        _viewModel = StateObject(wrappedValue: viewModel)
    }

    var body: some View {
        List {
            TrainingsCatalogFiltersSection(viewModel: viewModel)
            TrainingsCatalogResultsSection(viewModel: viewModel, service: service)
            TrainingsCatalogPaginationSection(viewModel: viewModel)
        }
        .navigationTitle("Formations")
        .task {
            await viewModel.loadCategoriesIfNeeded()
            await viewModel.load()
        }
        .onChangeCompat(viewModel.selectedCategorySlug) { _ in
            viewModel.page = 1
            Task { await viewModel.load() }
        }
        .feedbackDialog(error: $viewModel.error)
    }
}
