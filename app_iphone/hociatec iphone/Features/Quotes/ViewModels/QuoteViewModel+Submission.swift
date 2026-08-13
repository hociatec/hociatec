import Foundation

extension QuoteViewModel {
    func submit() async {
        guard account.isLoggedIn else {
            error = "Connectez-vous pour enregistrer ce devis dans votre espace client."
            return
        }

        let validItems = buildValidItems()
        guard !validItems.isEmpty else {
            error = "Ajoutez au moins une ligne valide."
            return
        }

        isSubmitting = true
        error = nil
        successMessage = nil
        defer { isSubmitting = false }

        do {
            _ = try await submitQuoteUseCase.execute(
                name: name,
                email: email,
                company: company.isEmpty ? nil : company,
                address: address.isEmpty ? nil : address,
                items: validItems
            )
            successMessage = "Demande de devis envoyée."
        } catch let err {
            error = err.localizedDescription
        }
    }
}
