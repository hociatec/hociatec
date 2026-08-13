import SwiftUI

struct NewsListView: View {
    let service: NewsServing
    @StateObject private var viewModel: NewsListViewModel

    init(api: NewsServing, initialSearch: String = "") {
        self.service = api
        let viewModel = NewsListViewModel(service: api)
        viewModel.searchText = initialSearch
        viewModel.appliedSearch = initialSearch.trimmingCharacters(in: .whitespacesAndNewlines)
        _viewModel = StateObject(wrappedValue: viewModel)
    }

    var body: some View {
        List {
            NewsListSearchSection(viewModel: viewModel, onSearch: performSearch)
            NewsListResultsSection(service: service, viewModel: viewModel)
            NewsListPaginationSection(
                viewModel: viewModel,
                onPrevious: {
                    viewModel.previousPage()
                    Task { await viewModel.load() }
                },
                onNext: {
                    viewModel.nextPage()
                    Task { await viewModel.load() }
                }
            )
        }
        .navigationTitle("Actualités")
        .task { await viewModel.load() }
    }

    private func performSearch() {
        viewModel.applySearch()
        Task { await viewModel.load() }
    }
}
