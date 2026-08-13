import Foundation

struct CartScreenState {
    var showingClearConfirm = false
    var itemPendingRemoval: CartItem?
    var pendingCheckoutSessionId: String?
    var checkoutStatusMessage: String?
    var isCheckingCheckoutStatus = false

    var isShowingRemovalAlert: Bool {
        itemPendingRemoval != nil
    }
}
