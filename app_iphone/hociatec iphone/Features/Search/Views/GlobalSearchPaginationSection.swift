import SwiftUI

struct GlobalSearchPaginationSection: View {
    @ObservedObject var viewModel: GlobalSearchViewModel
    let filter: GlobalSearchFilter

    var body: some View {
        if viewModel.totalPages(for: filter) > 1 {
            HStack {
                Button("Précédent") {
                    Task { await viewModel.goToPreviousPage(for: filter) }
                }
                .disabled(viewModel.currentPage(for: filter) <= 1 || viewModel.isLoading)

                Spacer()

                Text("\(viewModel.currentPage(for: filter))/\(viewModel.totalPages(for: filter))")
                    .font(.footnote)
                    .foregroundStyle(.secondary)

                Spacer()

                Button("Suivant") {
                    Task { await viewModel.goToNextPage(for: filter) }
                }
                .disabled(viewModel.currentPage(for: filter) >= viewModel.totalPages(for: filter) || viewModel.isLoading)
            }
        }
    }
}
