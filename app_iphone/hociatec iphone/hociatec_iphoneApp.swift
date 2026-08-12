import SwiftUI

@main
struct hociatec_iphoneApp: App {
    @StateObject private var container = AppContainer()

    var body: some Scene {
        WindowGroup {
            ContentView()
                .environmentObject(container)
                .environmentObject(container.cart)
                .environmentObject(container.account)
        }
    }
}
