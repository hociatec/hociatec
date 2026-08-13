import Foundation

enum TradeInPayloadBuilder {
    static func makePayload(from fields: TradeInValidatedSubmissionFields) -> TradeInRequestPayload {
        TradeInRequestPayload(
            firstName: fields.firstName,
            lastName: fields.lastName,
            email: fields.email,
            phone: fields.phone,
            category: fields.category,
            productName: fields.productName,
            purchasePriceCents: fields.purchasePriceCents ?? 0,
            purchaseYear: fields.purchaseYear ?? 0,
            brand: fields.brand.nonEmptyTradeInValue,
            model: fields.model.nonEmptyTradeInValue,
            serialNumber: fields.serialNumber.nonEmptyTradeInValue,
            conditionGrade: fields.condition,
            functional: fields.functional,
            hasAccessories: fields.hasAccessories,
            hasProofOfPurchase: fields.hasProofOfPurchase,
            description: fields.description,
            catalogProductId: nil,
            consent: true
        )
    }
}
