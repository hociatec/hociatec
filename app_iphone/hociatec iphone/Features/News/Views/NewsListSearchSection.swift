import SwiftUI

struct NewsListSearchSection: View {
    @ObservedObject var viewModel: NewsListViewModel
    let onSearch: () -> Void

    var body: some View {
        Section {
            TextField("Rechercher une actualité", text: $viewModel.searchText)
            Button("Rechercher", action: onSearch)
        }
    }
}
