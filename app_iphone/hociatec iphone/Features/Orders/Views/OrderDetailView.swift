import SwiftUI

@MainActor
struct OrderDetailView: View {
    @StateObject private var viewModel: OrderDetailViewModel
    @State private var showCancelAlert = false

    init(service: OrderServing, orderId: Int) {
        _viewModel = StateObject(wrappedValue: OrderDetailViewModel(service: service, orderId: orderId))
    }

    var body: some View {
        Form {
            if viewModel.isLoading {
                OrderDetailLoadingSection()
            }

            if let order = viewModel.order {
                OrderDetailSummarySection(order: order)
                OrderDetailShippingSection(order: order)
                OrderDetailItemsSection(order: order)
                OrderDetailTotalSection(order: order)

                if order.status.lowercased() == "pending" {
                    OrderDetailCancelSection(
                        isDisabled: viewModel.isLoading,
                        onCancel: { showCancelAlert = true }
                    )
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
        .feedbackDialog(error: $viewModel.error, success: $viewModel.statusMessage)
    }
}
