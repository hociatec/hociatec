import Foundation
import Combine

@MainActor
final class CartViewModel: ObservableObject {
    @Published var cart: Cart?
    @Published var isLoading = false
    @Published var error: String?
    @Published var statusMessage: String?

    let service: CartServing
    let feedbackCenter: AppFeedbackCenter

    init(service: CartServing, feedbackCenter: AppFeedbackCenter) {
        self.service = service
        self.feedbackCenter = feedbackCenter
    }
}
