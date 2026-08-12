import SwiftUI

@MainActor
struct OrderDetailView: View {
    let api: APIClient
    let orderId: Int

    @State private var order: OrderSummary? = nil
    @State private var isLoading = false
    @State private var error: String? = nil
    @State private var showCancelAlert = false

    var body: some View {
        Form {
            if let error = error {
                Section { Text(error).foregroundColor(.red) }
            }

            if isLoading {
                Section { HStack { Spacer(); ProgressView(); Spacer() } }
            }

            if let order = order {
                Section("Informations") {
                    LabeledContent("Numéro") { Text(order.number) }
                    LabeledContent("Statut") { Text(order.statusLabel) }
                    LabeledContent("Créée le") { Text(dateFormatter.string(from: order.createdAt)) }
                }

                Section("Livraison") {
                    LabeledContent("Nom") { Text(order.shipping.name) }
                    LabeledContent("Adresse") { Text(order.shipping.address) }
                    LabeledContent("Code postal") { Text(order.shipping.postalCode) }
                    LabeledContent("Ville") { Text(order.shipping.city) }
                }

                Section("Articles") {
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

                Section("Total") {
                    Text(PriceFormatter.format(cents: order.totalPriceCents)).fontWeight(.bold)
                }

                if order.status.lowercased() == "pending" {
                    Section("Actions") {
                        Button(role: .destructive) {
                            showCancelAlert = true
                        } label: {
                            Text("Annuler la commande").frame(maxWidth: .infinity)
                        }
                        .disabled(isLoading)
                    }
                }
            }
        }
        .navigationTitle("Commande #\(order?.number ?? "")")
        .task { await load() }
        .alert("Annuler cette commande ?", isPresented: $showCancelAlert) {
            Button("Retour", role: .cancel) { showCancelAlert = false }
            Button("Confirmer l’annulation", role: .destructive) {
                Task {
                    if let current = order { await cancel(current) }
                }
            }
        } message: {
            Text("Cette action est irréversible. La commande sera annulée si elle est encore en attente.")
        }
    }

    private func load() async {
        isLoading = true
        error = nil
        defer { isLoading = false }
        do {
            order = try await api.order(id: orderId)
        } catch {
            self.error = error.localizedDescription
            order = nil
        }
    }

    private func cancel(_ order: OrderSummary) async {
        isLoading = true
        error = nil
        defer { isLoading = false }
        do {
            let updated = try await api.cancelOrder(id: order.id)
            self.order = updated
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
