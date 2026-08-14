import SwiftUI

@MainActor
struct OrdersView: View {
    private let service: OrderServing
    @StateObject private var viewModel: OrdersViewModel
    @State private var statusFilter: OrderListFilter = .all
    @State private var searchText: String = ""
    @State private var sortOption: OrderListSortOption = .dateDesc
    
    init(service: OrderServing) {
        self.service = service
        _viewModel = StateObject(wrappedValue: OrdersViewModel(service: service))
    }
    
    var body: some View {
        List {
            OrdersFilterSection(
                statusFilter: $statusFilter,
                sortOption: $sortOption
            )
            OrdersContentSections(
                service: service,
                viewModel: viewModel,
                statusFilter: statusFilter,
                searchText: searchText,
                sortOption: sortOption
            )
        }
        .navigationTitle("Mes commandes")
        .searchable(text: $searchText, placement: .navigationBarDrawer(displayMode: .automatic), prompt: "Rechercher une commande")
        .task {
            await viewModel.load()
        }
        .refreshable {
            await viewModel.load(force: true)
        }
        .feedbackDialog(error: $viewModel.error)
    }
}
