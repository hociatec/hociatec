import SwiftUI
import Combine
import Foundation

/// Conteneur d’injection simple pour partager API + session dans toute l’app.
final class AppContainer: ObservableObject {
    let objectWillChange = ObservableObjectPublisher()
    let session: SessionStore
    let services: AppServices
    let cart: CartViewModel
    let account: AccountViewModel

    init() {
        let session = SessionStore()
        self.session = session
        let apiClient = APIClient(sessionStore: session)
        let services = AppServices(apiClient: apiClient)
        self.services = services

        let cartVM = CartViewModel(service: services.cart)
        self.cart = cartVM
        self.account = AccountViewModel(service: services.account, session: session)
    }
}
