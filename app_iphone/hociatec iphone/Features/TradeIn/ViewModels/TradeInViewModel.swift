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

    private let service: TradeInServing
    private let account: AccountViewModel

    init(service: TradeInServing, account: AccountViewModel) {
        self.service = service
        self.account = account
        self.firstName = account.profile?.firstName ?? account.firstName
        self.lastName = account.profile?.lastName ?? account.lastName
        self.email = account.profile?.email ?? account.email
        self.phone = account.profile?.phoneNumber ?? account.phoneNumber
    }

    func loadMetadata() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            let metadata = try await service.tradeInMetadata()
            categories = metadata.categories
            conditions = metadata.conditions
            if selectedCategory.isEmpty {
                selectedCategory = metadata.categories.first?.value ?? ""
            }
            if selectedCondition.isEmpty {
                selectedCondition = metadata.conditions.first?.value ?? ""
            }
        } catch {
            self.error = error.localizedDescription
        }
    }

    func setRib(fileName: String, data: Data) {
        ribFileName = fileName
        ribData = data
    }

    func submit() async -> Bool {
        let trimmedFirstName = firstName.trimmingCharacters(in: .whitespacesAndNewlines)
        let trimmedLastName = lastName.trimmingCharacters(in: .whitespacesAndNewlines)
        let trimmedEmail = email.trimmingCharacters(in: .whitespacesAndNewlines)
        let trimmedProductName = productName.trimmingCharacters(in: .whitespacesAndNewlines)
        let trimmedPhone = phone.trimmingCharacters(in: .whitespacesAndNewlines)
        let trimmedDescription = description.trimmingCharacters(in: .whitespacesAndNewlines)
        let trimmedBrand = brand.trimmingCharacters(in: .whitespacesAndNewlines)
        let trimmedModel = model.trimmingCharacters(in: .whitespacesAndNewlines)
        let trimmedSerial = serialNumber.trimmingCharacters(in: .whitespacesAndNewlines)

        guard !trimmedFirstName.isEmpty else {
            error = "Renseignez votre prénom."
            return false
        }
        guard !trimmedLastName.isEmpty else {
            error = "Renseignez votre nom."
            return false
        }
        guard !trimmedEmail.isEmpty, trimmedEmail.contains("@") else {
            error = "Renseignez un e-mail valide."
            return false
        }
        guard !selectedCategory.isEmpty else {
            error = "Choisissez une catégorie."
            return false
        }
        guard !selectedCondition.isEmpty else {
            error = "Choisissez un état."
            return false
        }
        guard !trimmedProductName.isEmpty else {
            error = "Renseignez le produit."
            return false
        }
        guard let purchasePriceCents = TradeInMoneyParser.cents(from: purchasePrice) else {
            error = "Renseignez un prix d’achat valide."
            return false
        }
        guard let purchaseYearValue = Int(purchaseYear), (1980...2100).contains(purchaseYearValue) else {
            error = "Renseignez une année d’achat valide."
            return false
        }
        guard !trimmedPhone.isEmpty else {
            error = "Renseignez votre téléphone."
            return false
        }
        guard !trimmedDescription.isEmpty else {
            error = "Décrivez l’état du produit."
            return false
        }
        guard consent else {
            error = "Vous devez accepter le traitement de la demande."
            return false
        }
        guard let selectedRibData = ribData, let ribFileName, !selectedRibData.isEmpty else {
            error = "Ajoutez votre RIB en PDF."
            return false
        }

        isSubmitting = true
        error = nil
        successMessage = nil
        defer { isSubmitting = false }

        do {
            let payload = TradeInRequestPayload(
                firstName: trimmedFirstName,
                lastName: trimmedLastName,
                email: trimmedEmail,
                phone: trimmedPhone,
                category: selectedCategory,
                productName: trimmedProductName,
                purchasePriceCents: purchasePriceCents,
                purchaseYear: purchaseYearValue,
                brand: trimmedBrand.isEmpty ? nil : trimmedBrand,
                model: trimmedModel.isEmpty ? nil : trimmedModel,
                serialNumber: trimmedSerial.isEmpty ? nil : trimmedSerial,
                conditionGrade: selectedCondition,
                functional: functional,
                hasAccessories: hasAccessories,
                hasProofOfPurchase: hasProofOfPurchase,
                description: trimmedDescription,
                catalogProductId: nil,
                consent: true
            )
            let created = try await service.createTradeIn(
                payload: payload,
                ribFilename: ribFileName,
                ribData: selectedRibData,
                authorized: account.isLoggedIn
            )
            successMessage = "Demande enregistrée (\(created.reference))."
            ribData = nil
            self.ribFileName = nil
            productName = ""
            brand = ""
            model = ""
            serialNumber = ""
            purchasePrice = ""
            purchaseYear = ""
            description = ""
            hasAccessories = false
            hasProofOfPurchase = false
            consent = false
            return true
        } catch {
            self.error = error.localizedDescription
            return false
        }
    }
}

private enum TradeInMoneyParser {
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
