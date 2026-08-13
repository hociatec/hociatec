import Foundation

enum TradeInSubmissionValidator {
    static func validate(_ draft: TradeInSubmissionDraft) -> Result<TradeInValidatedSubmissionFields, String> {
        guard !draft.firstName.isEmpty else {
            return .failure("Renseignez votre prénom.")
        }
        guard !draft.lastName.isEmpty else {
            return .failure("Renseignez votre nom.")
        }
        guard !draft.email.isEmpty, draft.email.contains("@") else {
            return .failure("Renseignez un e-mail valide.")
        }
        guard !draft.category.isEmpty else {
            return .failure("Choisissez une catégorie.")
        }
        guard !draft.condition.isEmpty else {
            return .failure("Choisissez un état.")
        }
        guard !draft.productName.isEmpty else {
            return .failure("Renseignez le produit.")
        }
        guard draft.purchasePriceCents != nil else {
            return .failure("Renseignez un prix d’achat valide.")
        }
        guard let purchaseYear = draft.purchaseYear, (1980...2100).contains(purchaseYear) else {
            return .failure("Renseignez une année d’achat valide.")
        }
        guard !draft.phone.isEmpty else {
            return .failure("Renseignez votre téléphone.")
        }
        guard !draft.description.isEmpty else {
            return .failure("Décrivez l’état du produit.")
        }
        guard draft.consent else {
            return .failure("Vous devez accepter le traitement de la demande.")
        }

        return .success(
            TradeInValidatedSubmissionFields(
                firstName: draft.firstName,
                lastName: draft.lastName,
                email: draft.email,
                productName: draft.productName,
                phone: draft.phone,
                description: draft.description,
                brand: draft.brand,
                model: draft.model,
                serialNumber: draft.serialNumber,
                category: draft.category,
                condition: draft.condition,
                purchasePriceCents: draft.purchasePriceCents,
                purchaseYear: draft.purchaseYear,
                functional: draft.functional,
                hasAccessories: draft.hasAccessories,
                hasProofOfPurchase: draft.hasProofOfPurchase
            )
        )
    }
}

struct TradeInValidatedSubmissionFields {
    let firstName: String
    let lastName: String
    let email: String
    let productName: String
    let phone: String
    let description: String
    let brand: String
    let model: String
    let serialNumber: String
    let category: String
    let condition: String
    let purchasePriceCents: Int?
    let purchaseYear: Int?
    let functional: Bool
    let hasAccessories: Bool
    let hasProofOfPurchase: Bool
}
