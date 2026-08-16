import SwiftUI

@MainActor
struct OrderDetailView: View {
    @StateObject private var viewModel: OrderDetailViewModel
    @State private var confirmationDialog: FeedbackDialogState?

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
                        onCancel: {
                            confirmationDialog = FeedbackDialogState(
                                title: "Annuler cette commande ?",
                                message: "Cette action est irréversible. La commande sera annulée si elle est encore en attente.",
                                primaryButton: .cancel("Retour"),
                                secondaryButton: .destructive("Confirmer l'annulation") {
                                    Task { await viewModel.cancel() }
                                }
                            )
                        }
                    )
                }
            }
        }
        .navigationTitle(viewModel.order?.number.isEmpty == false ? (viewModel.order?.number ?? "Commande") : "Commande")
        .task { await viewModel.load() }
        .feedbackDialog($confirmationDialog)
        .feedbackDialog(error: $viewModel.error, success: $viewModel.statusMessage)
    }
}
