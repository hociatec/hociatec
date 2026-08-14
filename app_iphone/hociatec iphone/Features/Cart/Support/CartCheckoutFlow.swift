import Foundation

struct CartCheckoutFlow {
    enum Resolution {
        case completed(OrderSummary)
        case failed(title: String, message: String)
        case pending(title: String, message: String)
        case unresolved
    }

    let orderService: OrderServing

    func resolve(sessionId: String) async throws -> Resolution {
        let status = try await orderService.checkoutSessionStatus(stripeSessionId: sessionId)

        switch status.status {
        case "paid":
            guard let order = try await resolveOrder(from: status) else {
                return .unresolved
            }
            return .completed(order)
        case "failed":
            return .failed(
                title: "Paiement échoué",
                message: "Le paiement a échoué. Vérifiez votre moyen de paiement puis réessayez."
            )
        case "expired":
            return .failed(
                title: "Paiement expiré",
                message: "Le paiement a expiré. Vous pouvez relancer la validation depuis le panier."
            )
        default:
            return .unresolved
        }
    }

    func waitForCompletion(
        sessionId: String,
        attempts: Int = 20,
        pauseNanoseconds: UInt64 = 2_000_000_000
    ) async -> Resolution {
        for attempt in 1...attempts {
            do {
                let resolution = try await resolve(sessionId: sessionId)
                if case .unresolved = resolution {
                    if attempt < attempts {
                        try? await Task.sleep(nanoseconds: pauseNanoseconds)
                        continue
                    }
                    return .pending(
                        title: "Paiement en attente",
                        message: "Le paiement n'est pas encore finalisé. Revenez dans quelques secondes ou relancez la validation si vous avez interrompu le paiement."
                    )
                }
                return resolution
            } catch {
                if attempt >= attempts {
                    return .pending(
                        title: "Paiement en attente",
                        message: "Le paiement n'est pas encore finalisé. Revenez dans quelques secondes ou relancez la validation si vous avez interrompu le paiement."
                    )
                }
                try? await Task.sleep(nanoseconds: pauseNanoseconds)
            }
        }

        return .pending(
            title: "Paiement en attente",
            message: "Le paiement n'est pas encore finalisé. Revenez dans quelques secondes ou relancez la validation si vous avez interrompu le paiement."
        )
    }

    func cancelPendingCheckout(sessionId: String?) async {
        guard let sessionId else { return }
        _ = try? await orderService.cancelCheckoutSession(stripeSessionId: sessionId)
    }

    private func resolveOrder(from status: CheckoutSessionStatusData) async throws -> OrderSummary? {
        if let existingOrder = status.order {
            return existingOrder
        }

        if let orderId = status.orderId {
            return try await orderService.order(id: orderId)
        }

        return nil
    }
}
