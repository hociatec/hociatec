import SwiftUI

struct NewsListSearchSection: View {
    @ObservedObject var viewModel: NewsListViewModel
    let onSearch: () -> Void

    var body: some View {
        Section {
            TextField("Rechercher une actualité", text: $viewModel.searchText)
                .textInputAutocapitalization(.never)
                .submitLabel(.search)
                .onSubmit(onSearch)
            Button("Rechercher", action: onSearch)
                .disabled(viewModel.searchText.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty && viewModel.appliedSearch.isEmpty)
        }
    }
}
