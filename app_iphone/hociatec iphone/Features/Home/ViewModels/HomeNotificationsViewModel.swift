import Foundation
import Combine

@MainActor
final class HomeNotificationsViewModel: ObservableObject {
    @Published var unreadCount = 0
    @Published var isLoading = false
    @Published var isOpen = false
    @Published var notifications: [AccountNotificationItem] = []
    @Published var seenKeys: Set<String> = []
    @Published var dismissedKeys: Set<String> = []
    @Published var loadError: String?

    private let workspaceService: WorkspaceServing

    init(workspaceService: WorkspaceServing) {
        self.workspaceService = workspaceService
    }

    func loadIfNeeded(isLoggedIn: Bool, force: Bool = false) async {
        guard isLoggedIn else {
            unreadCount = 0
            isLoading = false
            loadError = nil
            return
        }

        if isLoading && !force {
            return
        }

        isLoading = true
        loadError = nil
        defer { isLoading = false }

        do {
            async let notificationsTask = workspaceService.accountNotifications()
            async let readStateTask = workspaceService.accountNotificationsReadState()

            let notifications = try await notificationsTask
            let readState = try await readStateTask
            self.notifications = notifications
            self.dismissedKeys = Set(readState.dismissedKeys)
            self.seenKeys = Set(readState.seenKeys)
            recalculateUnreadCount()
        } catch {
            loadError = "Impossible de charger les notifications pour le moment."
        }
    }

    func toggleOpen(isLoggedIn: Bool) async {
        if !isOpen {
            await loadIfNeeded(isLoggedIn: isLoggedIn, force: true)
            isOpen = true
            await markVisibleNotificationsAsSeen()
            return
        }

        isOpen = false
    }

    func setOpen(_ open: Bool, isLoggedIn: Bool) async {
        guard open != isOpen else { return }

        if open {
            await loadIfNeeded(isLoggedIn: isLoggedIn, force: true)
            isOpen = true
            await markVisibleNotificationsAsSeen()
            return
        }

        isOpen = false
    }

    func dismissAll() async {
        let keys = visibleNotifications.map(\.key)
        guard !keys.isEmpty else { return }

        let previousDismissed = dismissedKeys
        let previousSeen = seenKeys
        dismissedKeys.formUnion(keys)
        seenKeys.formUnion(keys)
        recalculateUnreadCount()

        do {
            let readState = try await workspaceService.dismissAccountNotifications(keys: keys)
            dismissedKeys = Set(readState.dismissedKeys)
            seenKeys = Set(readState.seenKeys)
            recalculateUnreadCount()
        } catch {
            dismissedKeys = previousDismissed
            seenKeys = previousSeen
            recalculateUnreadCount()
            loadError = "Impossible de supprimer les notifications pour le moment."
        }
    }

    func dismiss(_ notification: AccountNotificationItem) async {
        let key = notification.key
        let previousDismissed = dismissedKeys
        let previousSeen = seenKeys
        dismissedKeys.insert(key)
        seenKeys.insert(key)
        recalculateUnreadCount()

        do {
            let readState = try await workspaceService.dismissAccountNotification(key: key)
            dismissedKeys = Set(readState.dismissedKeys)
            seenKeys = Set(readState.seenKeys)
            recalculateUnreadCount()
        } catch {
            dismissedKeys = previousDismissed
            seenKeys = previousSeen
            recalculateUnreadCount()
            loadError = "Impossible de mettre à jour cette notification pour le moment."
        }
    }

    var visibleNotifications: [AccountNotificationItem] {
        notifications.filter { !dismissedKeys.contains($0.key) }
    }

    func isUnread(_ notification: AccountNotificationItem) -> Bool {
        !seenKeys.contains(notification.key)
    }

    private func markVisibleNotificationsAsSeen() async {
        let keys = visibleNotifications
            .filter { !seenKeys.contains($0.key) }
            .map(\.key)

        guard !keys.isEmpty else { return }

        seenKeys.formUnion(keys)
        recalculateUnreadCount()

        do {
            let readState = try await workspaceService.markAccountNotificationsSeen(keys: keys)
            seenKeys = Set(readState.seenKeys)
            dismissedKeys = Set(readState.dismissedKeys)
            recalculateUnreadCount()
        } catch {
            await loadIfNeeded(isLoggedIn: true, force: true)
        }
    }

    private func recalculateUnreadCount() {
        unreadCount = visibleNotifications.filter { !seenKeys.contains($0.key) }.count
    }
}
