import SwiftUI

struct HomeNotificationsShortcutSection: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var account: AccountViewModel
    @StateObject private var viewModel: HomeNotificationsViewModel
    @State private var isExpanded = false

    init(workspaceService: WorkspaceServing) {
        _viewModel = StateObject(wrappedValue: HomeNotificationsViewModel(workspaceService: workspaceService))
    }

    var body: some View {
        Group {
            if account.isLoggedIn {
                Section {
                    DisclosureGroup(
                        isExpanded: Binding(
                            get: { isExpanded },
                            set: { newValue in
                                isExpanded = newValue
                                Task {
                                    await viewModel.setOpen(newValue, isLoggedIn: account.isLoggedIn)
                                }
                            }
                        )
                    ) {
                        notificationsContent
                            .padding(.top, 8)
                    } label: {
                        Label(
                            "Notifications (\(viewModel.unreadCount))",
                            systemImage: "bell.badge"
                        )
                        .fontWeight(.semibold)
                    }
                    .accessibilityRemoveTraits(.isHeader)
                }
            }
        }
        .task(id: account.isLoggedIn) {
            await viewModel.loadIfNeeded(isLoggedIn: account.isLoggedIn)
        }
        .onChangeCompat(viewModel.isOpen) { newValue in
            isExpanded = newValue
        }
        .onDisappear {
            isExpanded = false
            Task {
                await viewModel.setOpen(false, isLoggedIn: account.isLoggedIn)
            }
        }
    }

    @ViewBuilder
    private var notificationsContent: some View {
        if let loadError = viewModel.loadError, viewModel.visibleNotifications.isEmpty {
            Text(loadError)
                .font(.subheadline)
                .foregroundStyle(.secondary)
        } else if viewModel.visibleNotifications.isEmpty {
            Text("Aucune notification prioritaire.")
                .foregroundStyle(.secondary)
        } else {
            if let loadError = viewModel.loadError {
                Text(loadError)
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }

            if viewModel.visibleNotifications.count > 1 {
                Button("Tout supprimer", role: .destructive) {
                    Task { await viewModel.dismissAll() }
                }
            }

            ForEach(viewModel.visibleNotifications) { notification in
                VStack(alignment: .leading, spacing: 8) {
                    Text(notification.label)
                        .font(.headline)
                        .foregroundStyle(viewModel.isUnread(notification) ? .primary : .secondary)

                    Text(notification.message)
                        .font(.subheadline)
                        .foregroundStyle(.secondary)

                    HStack {
                        NavigationLink {
                            destination(for: notification)
                        } label: {
                            Text(notificationLinkLabel(for: notification))
                                .fontWeight(.semibold)
                        }

                        Spacer()

                        Button("Supprimer", role: .destructive) {
                            Task { await viewModel.dismiss(notification) }
                        }
                    }
                    .font(.footnote)
                }
                .padding(.vertical, 4)
                .accessibilityElement(children: .contain)
            }
        }
    }

    @ViewBuilder
    private func destination(for notification: AccountNotificationItem) -> some View {
        if let slug = newsSlug(from: notification.to) {
            NewsDetailView(api: container.services.news, slug: slug)
        } else if notification.to == "/actualites" || notification.to.hasPrefix("/actualites?") {
            NewsListView(api: container.services.news)
        } else if notification.to == "/mon-espace" || notification.to.hasPrefix("/profile") || notification.to.hasPrefix("/orders") || notification.to.hasPrefix("/beta") {
            AccountScreen()
        } else {
            AccountScreen()
        }
    }

    private func notificationLinkLabel(for notification: AccountNotificationItem) -> String {
        if notification.type.hasPrefix("beta_") || notification.to.hasPrefix("/beta") {
            return "Accéder à l’espace bêta"
        }

        return "Consulter"
    }

    private func newsSlug(from path: String) -> String? {
        let cleaned = path.trimmingCharacters(in: .whitespacesAndNewlines)
        guard cleaned.hasPrefix("/actualites/") else { return nil }
        let slug = String(cleaned.dropFirst("/actualites/".count))
        return slug.isEmpty ? nil : slug
    }
}
