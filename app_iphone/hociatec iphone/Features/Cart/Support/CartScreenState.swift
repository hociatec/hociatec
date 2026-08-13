import Foundation

struct CartScreenState {
    var showingClearConfirm = false
    var itemPendingRemoval: CartItem?

    var isShowingRemovalAlert: Bool {
        itemPendingRemoval != nil
    }
}
