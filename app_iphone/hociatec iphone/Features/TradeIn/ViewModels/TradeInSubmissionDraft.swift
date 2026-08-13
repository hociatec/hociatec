import Foundation

struct TradeInSubmissionDraft {
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
    let consent: Bool

    static func from(viewModel: TradeInViewModel) -> TradeInSubmissionDraft {
        TradeInSubmissionDraft(
            firstName: viewModel.firstName.trimmedForTradeInSubmission,
            lastName: viewModel.lastName.trimmedForTradeInSubmission,
            email: viewModel.email.trimmedForTradeInSubmission,
            productName: viewModel.productName.trimmedForTradeInSubmission,
            phone: viewModel.phone.trimmedForTradeInSubmission,
            description: viewModel.description.trimmedForTradeInSubmission,
            brand: viewModel.brand.trimmedForTradeInSubmission,
            model: viewModel.model.trimmedForTradeInSubmission,
            serialNumber: viewModel.serialNumber.trimmedForTradeInSubmission,
            category: viewModel.selectedCategory,
            condition: viewModel.selectedCondition,
            purchasePriceCents: TradeInMoneyParser.cents(from: viewModel.purchasePrice),
            purchaseYear: Int(viewModel.purchaseYear),
            functional: viewModel.functional,
            hasAccessories: viewModel.hasAccessories,
            hasProofOfPurchase: viewModel.hasProofOfPurchase,
            consent: viewModel.consent
        )
    }
}
