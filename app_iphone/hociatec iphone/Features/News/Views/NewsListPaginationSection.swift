import SwiftUI

struct NewsListPaginationSection: View {
    @ObservedObject var viewModel: NewsListViewModel
    let onPrevious: () -> Void
    let onNext: () -> Void

    var body: some View {
        if viewModel.totalPages > 1 {
            Section {
                HStack {
                    Button("Précédent", action: onPrevious)
                        .disabled(viewModel.page <= 1 || viewModel.isLoading)
                    Spacer()
                    Text("\(viewModel.page)/\(viewModel.totalPages)")
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                    Spacer()
                    Button("Suivant", action: onNext)
                        .disabled(viewModel.page >= viewModel.totalPages || viewModel.isLoading)
                }
            }
        }
    }
}
