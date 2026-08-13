import Foundation

extension QuoteViewModel {
    func prefillFromAccount() {
        prefill()
    }

    func prefill() {
        if let profile = account.profile {
            name = "\(profile.firstName) \(profile.lastName)"
            email = profile.email
            company = ""
            address = profile.address ?? ""
        } else {
            name = ""
            email = account.email
        }
    }
}
