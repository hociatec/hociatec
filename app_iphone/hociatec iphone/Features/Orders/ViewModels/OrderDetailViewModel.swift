import Combine
import SwiftUI

@MainActor
final class OrderDetailViewModel: ObservableObject {
    @Published var order: OrderSummary?
    @Published var isLoading = false
    @Published var error: String?
    @Published var statusMessage: String?

    private let service: OrderServing
    private let orderId: Int

    init(service: OrderServing, orderId: Int) {
        self.service = service
        self.orderId = orderId
    }

    func load() async {
        isLoading = true
        error = nil
        statusMessage = nil
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
        statusMessage = nil
        defer { isLoading = false }

        do {
            self.order = try await service.cancelOrder(id: order.id)
            statusMessage = "Commande annulée."
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
