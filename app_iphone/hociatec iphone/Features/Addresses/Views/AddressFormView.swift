import SwiftUI

struct AddressFormView: View {
    @EnvironmentObject private var account: AccountViewModel
    @Environment(\.dismiss) private var dismiss
    @StateObject private var locationLookup = AddressLocationLookupViewModel()

    let existing: UserAddress?

    @State private var type: String
    @State private var label: String
    @State private var address: String
    @State private var addressComplement: String
    @State private var postalCode: String
    @State private var city: String
    @State private var company: String
    @State private var companySiren: String
    @State private var companyVatNumber: String
    @State private var isDefault: Bool
    @State private var isSaving = false
    @State private var showDeleteAlert = false
    @State private var feedbackDialog: FeedbackDialogState?
    @State private var shouldDismissAfterFeedback = false

    init(address: UserAddress? = nil) {
        self.existing = address
        _type = State(initialValue: address?.type ?? "personal")
        _label = State(initialValue: address?.label ?? "")
        _address = State(initialValue: address?.address ?? "")
        _addressComplement = State(initialValue: address?.addressComplement ?? "")
        _postalCode = State(initialValue: address?.postalCode ?? "")
        _city = State(initialValue: address?.city ?? "")
        _company = State(initialValue: address?.company ?? "")
        _companySiren = State(initialValue: address?.companySiren ?? "")
        _companyVatNumber = State(initialValue: address?.companyVatNumber ?? "")
        _isDefault = State(initialValue: address?.isDefault ?? false)
    }

    var body: some View {
        Form {
            AddressFormFieldsSection(
                type: $type,
                label: $label,
                address: $address,
                addressComplement: $addressComplement,
                postalCode: $postalCode,
                city: $city,
                company: $company,
                companySiren: $companySiren,
                companyVatNumber: $companyVatNumber,
                isDefault: $isDefault,
                isLocating: locationLookup.isLoading,
                onUseCurrentLocation: fillFromCurrentLocation
            )
            if existing?.id != nil {
                AddressFormDeleteSection {
                    showDeleteAlert = true
                }
            }
        }
        .navigationTitle(existing == nil ? "Nouvelle adresse" : "Modifier l’adresse")
        .safeAreaInset(edge: .bottom) {
            AddressFormSaveBar(
                isSaving: isSaving,
                isDisabled: isSaving || label.isEmpty || address.isEmpty || postalCode.isEmpty || city.isEmpty || (type == "professional" && company.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty),
                onSave: save
            )
            .padding([.horizontal, .top])
            .padding(.bottom, 8)
            .background(.thinMaterial)
        }
        .alert("Supprimer cette adresse ?", isPresented: $showDeleteAlert) {
            Button("Annuler", role: .cancel) {}
            Button("Supprimer", role: .destructive) {
                deleteAddress()
            }
        } message: {
            Text("Cette action est irréversible.")
        }
        .feedbackDialog($feedbackDialog) {
            if shouldDismissAfterFeedback {
                shouldDismissAfterFeedback = false
                dismiss()
            }
        }
        .onChangeCompat(locationLookup.error) { newValue in
            guard let newValue, !newValue.isEmpty else { return }
            shouldDismissAfterFeedback = false
            feedbackDialog = .error(newValue)
        }
    }

    private func save() {
        Task {
            isSaving = true
            feedbackDialog = nil
            locationLookup.error = nil
            let mutationError: String?
            if let existing {
                var updated = existing
                updated.type = type
                updated.label = label
                updated.address = address
                updated.addressComplement = addressComplement.nilIfBlank
                updated.postalCode = postalCode
                updated.city = city
                updated.company = type == "professional" ? company.nilIfBlank : nil
                updated.companySiren = type == "professional" ? companySiren.nilIfBlank : nil
                updated.companyVatNumber = type == "professional" ? companyVatNumber.nilIfBlank : nil
                updated.isDefault = isDefault
                mutationError = await account.updateAddress(updated, reportErrors: false)
            } else {
                mutationError = await account.addAddress(
                    type: type,
                    label: label,
                    address: address,
                    addressComplement: addressComplement.nilIfBlank,
                    postalCode: postalCode,
                    company: type == "professional" ? company.nilIfBlank : nil,
                    companySiren: type == "professional" ? companySiren.nilIfBlank : nil,
                    companyVatNumber: type == "professional" ? companyVatNumber.nilIfBlank : nil,
                    city: city,
                    isDefault: isDefault,
                    reportErrors: false
                )
            }
            isSaving = false
            if mutationError == nil {
                shouldDismissAfterFeedback = true
                feedbackDialog = .success(existing == nil ? "Adresse enregistrée." : "Adresse mise à jour.")
            } else if let message = mutationError, !message.isEmpty {
                shouldDismissAfterFeedback = false
                feedbackDialog = .error(message)
            }
        }
    }
    
    private func deleteAddress() {
        guard let id = existing?.id else { return }
        Task {
            isSaving = true
            feedbackDialog = nil
            let mutationError = await account.deleteAddress(id: id, reportErrors: false)
            isSaving = false
            if mutationError == nil {
                shouldDismissAfterFeedback = true
                feedbackDialog = .success("Adresse supprimée.")
            } else if let message = mutationError, !message.isEmpty {
                shouldDismissAfterFeedback = false
                feedbackDialog = .error(message)
            }
        }
    }

    private func fillFromCurrentLocation() {
        locationLookup.error = nil
        locationLookup.fillFromCurrentLocation { resolved in
            address = resolved.address
            postalCode = resolved.postalCode
            city = resolved.city
        }
    }
}

private extension String {
    var nilIfBlank: String? {
        let trimmed = trimmingCharacters(in: .whitespacesAndNewlines)
        return trimmed.isEmpty ? nil : trimmed
    }
}
