import SwiftUI

struct OrdersContentSections: View {
    let service: OrderServing
    @ObservedObject var viewModel: OrdersViewModel
    let statusFilter: OrderListFilter
    let searchText: String
    let sortOption: OrderListSortOption

    var body: some View {
        if hasNoResults {
            Section {
                Text("Aucune commande disponible.")
                    .foregroundColor(.secondary)
            }
        } else if statusFilter == .all {
            ForEach(groupedOrders, id: \.title) { group in
                Section(group.title) {
                    ForEach(group.items, id: \.id) { order in
                        orderRow(order)
                    }
                }
            }
        } else {
            Section {
                ForEach(filteredOrders, id: \.id) { order in
                    orderRow(order)
                }
            }
        }
    }

    private var filteredOrders: [OrderSummary] {
        viewModel.filteredOrders(using: statusFilter, searchText: searchText, sort: sortOption)
    }

    private var groupedOrders: [(title: String, items: [OrderSummary])] {
        viewModel.groupedOrders(searchText: searchText, sort: sortOption)
    }

    private var hasNoResults: Bool {
        statusFilter == .all ? groupedOrders.isEmpty : filteredOrders.isEmpty
    }

    @ViewBuilder
    private func orderRow(_ order: OrderSummary) -> some View {
        NavigationLink(destination: OrderDetailView(service: service, orderId: order.id)) {
            OrderRow(
                order: order,
                isCancelling: viewModel.cancellingOrderID == order.id
            )
        }
    }
}
