import SwiftUI
import Combine

@MainActor
struct OrderDetailView: View {
    @StateObject private var viewModel: OrderDetailViewModel
    @State private var showCancelAlert = false

    init(service: OrderServing, orderId: Int) {
        _viewModel = StateObject(wrappedValue: OrderDetailViewModel(service: service, orderId: orderId))
    }

    var body: some View {
        Form {
            if let error = viewModel.error {
                Section { Text(error).foregroundColor(.red) }
            }

            if viewModel.isLoading {
                Section { HStack { Spacer(); ProgressView(); Spacer() } }
            }

            if let order = viewModel.order {
                Section {
                    LabeledContent("Numéro") { Text(order.number) }
                    LabeledContent("Statut") { Text(order.statusLabel) }
                    LabeledContent("Créée le") { Text(dateFormatter.string(from: order.createdAt)) }
                }

                Section {
                    LabeledContent("Nom") { Text(order.shipping.name) }
                    LabeledContent("Adresse") { Text(order.shipping.address) }
                    LabeledContent("Code postal") { Text(order.shipping.postalCode) }
                    LabeledContent("Ville") { Text(order.shipping.city) }
                }

                Section {
                    ForEach(order.items) { item in
                        VStack(alignment: .leading, spacing: 6) {
                            Text(item.productName).fontWeight(.semibold)
                            Text("SKU: \(item.productSku)").font(.caption).foregroundStyle(.secondary)
                            HStack {
                                Text("Qté: \(item.quantity)")
                                Spacer()
                                Text(PriceFormatter.format(cents: item.unitPriceCents))
                            }
                            HStack {
                                Text("Total ligne")
                                Spacer()
                                Text(PriceFormatter.format(cents: item.linePriceCents)).fontWeight(.semibold)
                            }
                        }
                        .padding(.vertical, 4)
                    }
                }

                Section {
                    Text(PriceFormatter.format(cents: order.totalPriceCents)).fontWeight(.bold)
                }

                if order.status.lowercased() == "pending" {
                    Section {
                        Button(role: .destructive) {
                            showCancelAlert = true
                        } label: {
                            Text("Annuler la commande").frame(maxWidth: .infinity)
                        }
                        .disabled(viewModel.isLoading)
                    }
                }
            }
        }
        .navigationTitle(viewModel.order?.number.isEmpty == false ? (viewModel.order?.number ?? "Commande") : "Commande")
        .task { await viewModel.load() }
        .alert("Annuler cette commande ?", isPresented: $showCancelAlert) {
            Button("Retour", role: .cancel) { showCancelAlert = false }
            Button("Confirmer l’annulation", role: .destructive) {
                Task {
                    await viewModel.cancel()
                }
            }
        } message: {
            Text("Cette action est irréversible. La commande sera annulée si elle est encore en attente.")
        }
    }
}

@MainActor
private final class OrderDetailViewModel: ObservableObject {
    @Published var order: OrderSummary?
    @Published var isLoading = false
    @Published var error: String?

    private let service: OrderServing
    private let orderId: Int

    init(service: OrderServing, orderId: Int) {
        self.service = service
        self.orderId = orderId
    }

    func load() async {
        isLoading = true
        error = nil
        defer { isLoading = false }
        do {
            order = try await service.order(id: orderId)
        } catch {
            self.error = error.localizedDescription
            order = nil
        }
    }

    func cancel() async {
        guard let order else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }
        do {
            self.order = try await service.cancelOrder(id: order.id)
#if canImport(UIKit)
            UINotificationFeedbackGenerator().notificationOccurred(.success)
#endif
        } catch {
            self.error = error.localizedDescription
#if canImport(UIKit)
            UINotificationFeedbackGenerator().notificationOccurred(.error)
#endif
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
