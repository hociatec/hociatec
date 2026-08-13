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

    private func submissionContext() -> (payload: TradeInRequestPayload, ribFileName: String, ribData: Data)? {
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
            return nil
        }
        guard !trimmedLastName.isEmpty else {
            error = "Renseignez votre nom."
            return nil
        }
        guard !trimmedEmail.isEmpty, trimmedEmail.contains("@") else {
            error = "Renseignez un e-mail valide."
            return nil
        }
        guard !selectedCategory.isEmpty else {
            error = "Choisissez une catégorie."
            return nil
        }
        guard !selectedCondition.isEmpty else {
            error = "Choisissez un état."
            return nil
        }
        guard !trimmedProductName.isEmpty else {
            error = "Renseignez le produit."
            return nil
        }
        guard let purchasePriceCents = TradeInMoneyParser.cents(from: purchasePrice) else {
            error = "Renseignez un prix d’achat valide."
            return nil
        }
        guard let purchaseYearValue = Int(purchaseYear), (1980...2100).contains(purchaseYearValue) else {
            error = "Renseignez une année d’achat valide."
            return nil
        }
        guard !trimmedPhone.isEmpty else {
            error = "Renseignez votre téléphone."
            return nil
        }
        guard !trimmedDescription.isEmpty else {
            error = "Décrivez l’état du produit."
            return nil
        }
        guard consent else {
            error = "Vous devez accepter le traitement de la demande."
            return nil
        }
        guard let ribData, let ribFileName, !ribData.isEmpty else {
            error = "Ajoutez votre RIB en PDF."
            return nil
        }

        return (
            TradeInRequestPayload(
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
            ),
            ribFileName,
            ribData
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
