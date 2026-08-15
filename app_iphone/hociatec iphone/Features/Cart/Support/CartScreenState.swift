import Foundation

struct CartScreenState {
    var showingClearConfirm = false
    var itemPendingRemoval: CartItem?
    var pendingCheckoutSessionId: String?
    var isCheckingCheckoutStatus = false
    var checkoutDialog: FeedbackDialogState?

    var isShowingRemovalAlert: Bool {
        itemPendingRemoval != nil
    }
}
