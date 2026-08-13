import SwiftUI

@MainActor
struct OrdersView: View {
    private let service: OrderServing
    @StateObject private var viewModel: OrdersViewModel
    @State private var statusFilter: String = "all"
    @State private var searchText: String = ""
    @State private var sortOption: String = "dateDesc"
    
    private var filteredOrders: [OrderSummary] {
        let base: [OrderSummary]
        switch statusFilter {
        case "pending":
            base = viewModel.orders.filter { $0.status.lowercased() == "pending" }
        case "cancelled":
            base = viewModel.orders.filter { $0.status.lowercased().contains("cancel") || $0.status.lowercased().contains("annul") }
        case "completed":
            base = viewModel.orders.filter { !($0.status.lowercased().contains("cancel") || $0.status.lowercased().contains("annul") || $0.status.lowercased() == "pending") }
        default:
            base = viewModel.orders
        }
        let query = searchText.trimmingCharacters(in: .whitespacesAndNewlines).lowercased()
        if query.isEmpty { return base }
        return base.filter { order in
            order.number.lowercased().contains(query) || order.statusLabel.lowercased().contains(query)
        }
    }
    
    private func sortOrders(_ orders: [OrderSummary]) -> [OrderSummary] {
        switch sortOption {
        case "dateAsc":
            return orders.sorted { $0.createdAt < $1.createdAt }
        default:
            return orders.sorted { $0.createdAt > $1.createdAt }
        }
    }
    
    init(service: OrderServing) {
        self.service = service
        _viewModel = StateObject(wrappedValue: OrdersViewModel(service: service))
    }
    
    var body: some View {
        List {
            Section {
                Picker("Filtrer", selection: $statusFilter) {
                    Text("Toutes").tag("all")
                    Text("En attente").tag("pending")
                    Text("Terminées").tag("completed")
                    Text("Annulées").tag("cancelled")
                }
                .pickerStyle(.segmented)
                
                Picker("Tri", selection: $sortOption) {
                    Text("Date ↓").tag("dateDesc")
                    Text("Date ↑").tag("dateAsc")
                }
                .pickerStyle(.segmented)
            }
            
            if let error = viewModel.error {
                Section {
                    Text(error)
                        .foregroundColor(.red)
                }
            }
            
            if filteredOrders.isEmpty {
                Section {
                    Text("Aucune commande disponible.")
                        .foregroundColor(.secondary)
                }
            } else {
                if statusFilter == "all" {
                    let query = searchText.trimmingCharacters(in: .whitespacesAndNewlines).lowercased()
                    let searched = viewModel.orders.filter { q in
                        if query.isEmpty { return true }
                        return q.number.lowercased().contains(query) || q.statusLabel.lowercased().contains(query)
                    }
                    let pending = sortOrders(searched.filter { $0.status.lowercased() == "pending" })
                    let cancelled = sortOrders(searched.filter { $0.status.lowercased().contains("cancel") || $0.status.lowercased().contains("annul") })
                    let completed = sortOrders(searched.filter { !($0.status.lowercased().contains("cancel") || $0.status.lowercased().contains("annul") || $0.status.lowercased() == "pending") })
                    
                    if !pending.isEmpty {
                        Section("En attente") {
                            ForEach(pending, id: \.id) { order in
                                orderRow(order)
                            }
                        }
                    }
                    if !completed.isEmpty {
                        Section("Terminées") {
                            ForEach(completed, id: \.id) { order in
                                orderRow(order)
                            }
                        }
                    }
                    if !cancelled.isEmpty {
                        Section("Annulées") {
                            ForEach(cancelled, id: \.id) { order in
                                orderRow(order)
                            }
                        }
                    }
                } else {
                    Section {
                        ForEach(sortOrders(filteredOrders), id: \.id) { order in
                            orderRow(order)
                        }
                    }
                }
            }
        }
        .navigationTitle("Mes commandes")
        .searchable(text: $searchText, placement: .navigationBarDrawer(displayMode: .automatic), prompt: "Rechercher une commande")
        .task {
            await viewModel.load()
        }
        .refreshable {
            await viewModel.load(force: true)
        }
    }
    
    @ViewBuilder
    private func orderRow(_ order: OrderSummary) -> some View {
        NavigationLink(destination: OrderDetailView(service: service, orderId: order.id)) {
            VStack(alignment: .leading) {
                Text("Commande n°: \(order.number)")
                    .font(.headline)
                Text("\(order.items.count) article\(order.items.count > 1 ? "s" : "")")
                    .font(.caption2)
                    .padding(.horizontal, 8)
                    .padding(.vertical, 4)
                    .background(Color.gray.opacity(0.15))
                    .foregroundColor(.secondary)
                    .clipShape(Capsule())
                Text(order.statusLabel)
                    .font(.caption)
                    .padding(.horizontal, 8)
                    .padding(.vertical, 4)
                    .background(statusColor(for: order.status).opacity(0.15))
                    .foregroundColor(statusColor(for: order.status))
                    .clipShape(Capsule())
                Text("Total: \(PriceFormatter.format(cents: order.totalPriceCents))")
                Text("Créée le: \(dateFormatter.string(from: order.createdAt))")
                    .font(.footnote)
                    .foregroundColor(.secondary)
                Text("Livraison: \(order.shipping.name), \(order.shipping.city)")
                    .font(.footnote)
                    .foregroundColor(.secondary)
                if viewModel.cancellingOrderID == order.id {
                    HStack {
                        ProgressView().scaleEffect(0.8)
                        Text("Annulation en cours...")
                            .font(.caption)
                            .foregroundColor(.secondary)
                    }
                }
            }
        }
    }
}

private let dateFormatter: DateFormatter = {
    let df = DateFormatter()
    df.locale = Locale(identifier: "fr_FR")
    df.dateStyle = .medium
    df.timeStyle = .short
    return df
}()

private func statusColor(for status: String) -> Color {
    let s = status.lowercased()
    if s.contains("cancel") || s.contains("annul") {
        return .red
    } else if s.contains("pending") {
        return .orange
    } else {
        return .green
    }
}
