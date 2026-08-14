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
    private var loadRequestID = 0

    init(service: OrderServing, orderId: Int) {
        self.service = service
        self.orderId = orderId
    }

    func load() async {
        loadRequestID += 1
        let requestID = loadRequestID
        isLoading = true
        error = nil
        statusMessage = nil

        do {
            let loadedOrder = try await service.order(id: orderId)
            guard requestID == loadRequestID else { return }
            order = loadedOrder
        } catch {
            guard requestID == loadRequestID else { return }
            self.error = error.localizedDescription
            order = nil
        }

        if requestID == loadRequestID {
            isLoading = false
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
