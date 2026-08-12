import SwiftUI
import Combine
import Foundation

/// Conteneur d’injection simple pour partager API + session dans toute l’app.
final class AppContainer: ObservableObject {
    let objectWillChange = ObservableObjectPublisher()
    let session: SessionStore
    let api: APIClient
    let cart: CartViewModel
    let account: AccountViewModel

    init() {
        let session = SessionStore()
        self.session = session
        let apiClient = APIClient(sessionStore: session)
        self.api = apiClient

        let cartVM = CartViewModel(api: apiClient)
        self.cart = cartVM
        self.account = AccountViewModel(api: apiClient, session: session)
    }
}
