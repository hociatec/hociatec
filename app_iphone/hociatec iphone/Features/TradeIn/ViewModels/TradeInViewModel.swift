import Foundation
import Combine

@MainActor
final class TradeInViewModel: ObservableObject {
    @Published var categories: [TradeInOption] = []
    @Published var conditions: [TradeInOption] = []
    @Published var selectedCategory: String = ""
    @Published var selectedCondition: String = ""
    @Published var firstName: String = ""
    @Published var lastName: String = ""
    @Published var email: String = ""
    @Published var productName: String = ""
    @Published var brand: String = ""
    @Published var model: String = ""
    @Published var serialNumber: String = ""
    @Published var purchasePrice: String = ""
    @Published var purchaseYear: String = ""
    @Published var phone: String = ""
    @Published var description: String = ""
    @Published var functional: Bool = true
    @Published var hasAccessories: Bool = false
    @Published var hasProofOfPurchase: Bool = false
    @Published var consent: Bool = false
    @Published var ribFileName: String?
    @Published var ribData: Data?
    @Published var isLoading = false
    @Published var isSubmitting = false
    @Published var error: String?
    @Published var successMessage: String?

    let service: TradeInServing
    let account: AccountViewModel
    var metadataRequestID = 0
    var hasLoadedMetadataOnce = false

    init(service: TradeInServing, account: AccountViewModel) {
        self.service = service
        self.account = account
        self.firstName = account.profile?.firstName ?? account.firstName
        self.lastName = account.profile?.lastName ?? account.lastName
        self.email = account.profile?.email ?? account.email
        self.phone = account.profile?.phoneNumber ?? account.phoneNumber
    }
}

enum TradeInMoneyParser {
    static func cents(from input: String) -> Int? {
        let cleaned = input
            .replacingOccurrences(of: "€", with: "")
            .replacingOccurrences(of: " ", with: "")
            .trimmingCharacters(in: .whitespacesAndNewlines)
            .replacingOccurrences(of: ",", with: ".")
        guard !cleaned.isEmpty else { return nil }
        guard let value = Double(cleaned), value >= 0 else { return nil }
        return Int((value * 100).rounded())
    }
}
