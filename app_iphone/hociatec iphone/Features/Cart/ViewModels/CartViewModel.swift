import Foundation
import Combine

@MainActor
final class CartViewModel: ObservableObject {
    @Published var cart: Cart?
    @Published var isLoading = false
    @Published var error: String?
    @Published var statusMessage: String?
    @Published var globalDialog: FeedbackDialogState?

    let service: CartServing

    init(service: CartServing) {
        self.service = service
    }
}
