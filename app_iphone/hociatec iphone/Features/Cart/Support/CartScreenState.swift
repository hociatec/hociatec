import Foundation

struct CartCheckoutDialog: Identifiable {
    let id = UUID()
    let title: String
    let message: String
}

struct CartScreenState {
    var showingClearConfirm = false
    var itemPendingRemoval: CartItem?
    var pendingCheckoutSessionId: String?
    var isCheckingCheckoutStatus = false
    var checkoutDialog: CartCheckoutDialog?

    var isShowingRemovalAlert: Bool {
        itemPendingRemoval != nil
    }
}
