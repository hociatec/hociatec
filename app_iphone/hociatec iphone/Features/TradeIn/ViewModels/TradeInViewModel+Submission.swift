import Foundation

extension TradeInViewModel {
    func submit() async -> Bool {
        guard let context = submissionContext() else {
            return false
        }

        isSubmitting = true
        error = nil
        successMessage = nil
        defer { isSubmitting = false }

        do {
            let created = try await service.createTradeIn(
                payload: context.payload,
                ribFilename: context.ribFileName,
                ribData: context.ribData,
                authorized: account.isLoggedIn
            )
            successMessage = "Demande enregistrée (\(created.reference))."
            resetSubmissionState()
            return true
        } catch {
            self.error = error.localizedDescription
            return false
        }
    }

    private func submissionContext() -> TradeInSubmissionContext? {
        guard let fields = validatedSubmissionFields() else {
            return nil
        }
        guard let ribAttachment = validatedRIBAttachment() else {
            return nil
        }

        return TradeInSubmissionContext(
            payload: TradeInPayloadBuilder.makePayload(from: fields),
            ribFileName: ribAttachment.fileName,
            ribData: ribAttachment.data
        )
    }

    private func validatedSubmissionFields() -> TradeInValidatedSubmissionFields? {
        let draft = TradeInSubmissionDraft.from(viewModel: self)

        switch TradeInSubmissionValidator.validate(draft) {
        case .success(let fields):
            return fields
        case .failure(let validationError):
            error = validationError.localizedDescription
            return nil
        }
    }

    private func validatedRIBAttachment() -> (fileName: String, data: Data)? {
        guard let ribData, let ribFileName, !ribData.isEmpty else {
            error = "Ajoutez votre RIB en PDF."
            return nil
        }

        return (ribFileName, ribData)
    }

    private func makeTradeInPayload(from fields: TradeInValidatedSubmissionFields) -> TradeInRequestPayload {
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

    private func resetSubmissionState() {
        ribData = nil
        ribFileName = nil
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
    }
}
