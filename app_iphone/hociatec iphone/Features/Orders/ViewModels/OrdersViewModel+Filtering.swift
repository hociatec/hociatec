import Foundation

extension OrdersViewModel {
    func filteredOrders(
        using filter: OrderListFilter,
        searchText: String,
        sort: OrderListSortOption
    ) -> [OrderSummary] {
        let query = searchText.trimmingCharacters(in: .whitespacesAndNewlines).lowercased()
        let filtered = orders.filter { order in
            filter.matches(order) && matchesSearch(order, query: query)
        }
        return sort.sort(filtered)
    }

    func groupedOrders(
        searchText: String,
        sort: OrderListSortOption
    ) -> [(title: String, items: [OrderSummary])] {
        let query = searchText.trimmingCharacters(in: .whitespacesAndNewlines).lowercased()

        let groups: [(String, OrderListFilter)] = [
            ("En attente", .pending),
            ("Terminées", .completed),
            ("Annulées", .cancelled)
        ]

        return groups.compactMap { title, filter in
            let items = sort.sort(
                orders.filter { order in
                    filter.matches(order) && matchesSearch(order, query: query)
                }
            )
            return items.isEmpty ? nil : (title: title, items: items)
        }
    }

    private func matchesSearch(_ order: OrderSummary, query: String) -> Bool {
        guard !query.isEmpty else { return true }
        return order.number.lowercased().contains(query)
            || order.statusLabel.lowercased().contains(query)
    }
}
