import Foundation

extension AccountViewModel {
    func loadAddresses() async {
        guard isLoggedIn else {
            addresses = []
            return
        }

        do {
            let items = try await useCases.loadAddresses.execute()
            addresses = items
        } catch let err {
            error = err.localizedDescription
        }
    }
}
