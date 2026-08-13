import SwiftUI

struct OrdersFilterSection: View {
    @Binding var statusFilter: OrderListFilter
    @Binding var sortOption: OrderListSortOption

    var body: some View {
        Section {
            Picker("Filtrer", selection: $statusFilter) {
                ForEach(OrderListFilter.allCases) { filter in
                    Text(filter.label).tag(filter)
                }
            }
            .pickerStyle(.segmented)

            Picker("Tri", selection: $sortOption) {
                ForEach(OrderListSortOption.allCases) { option in
                    Text(option.label).tag(option)
                }
            }
            .pickerStyle(.segmented)
        }
    }
}
