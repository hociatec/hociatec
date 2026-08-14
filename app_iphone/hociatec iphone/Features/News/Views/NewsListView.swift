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
            Section {
                VStack(alignment: .leading, spacing: 10) {
                    Text("Actualités")
                        .font(.title3.weight(.bold))
                    Text("Suivez les annonces, les évolutions de service et les informations utiles autour de l’accompagnement numérique Hociatec.")
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                }
                .padding(.vertical, 4)
            }
            NewsListSearchSection(viewModel: viewModel, onSearch: performSearch)
            NewsListResultsSection(service: service, viewModel: viewModel)
            NewsListPaginationSection(
                viewModel: viewModel,
                onPrevious: {
                    viewModel.previousPage()
                    Task { await viewModel.load(force: true) }
                },
                onNext: {
                    viewModel.nextPage()
                    Task { await viewModel.load(force: true) }
                }
            )
        }
        .navigationTitle("Actualités")
        .task { await viewModel.load() }
        .feedbackDialog(error: $viewModel.error)
    }

    private func performSearch() {
        viewModel.applySearch()
        Task { await viewModel.load(force: true) }
    }
}
