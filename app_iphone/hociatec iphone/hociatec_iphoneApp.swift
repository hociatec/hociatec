import SwiftUI

@main
struct hociatec_iphoneApp: App {
    @StateObject private var container = AppContainer()
    @StateObject private var navigation = AppNavigationState()

    var body: some Scene {
        WindowGroup {
            ContentView()
                .environmentObject(container)
                .environmentObject(container.cart)
                .environmentObject(container.account)
                .environmentObject(navigation)
                .onOpenURL { url in
                    container.session.handleIncomingURL(url)
                    navigation.handle(url: url)
                }
        }
    }
}
