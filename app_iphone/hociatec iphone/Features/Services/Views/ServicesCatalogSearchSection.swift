import SwiftUI

struct ServicesCatalogSearchSection: View {
    @ObservedObject var viewModel: ServicesCatalogViewModel
    let onSearch: () -> Void

    var body: some View {
        Section {
            TextField("Rechercher un service", text: $viewModel.searchText)
            Button("Rechercher", action: onSearch)
        }
    }
}
