import SwiftUI

struct AddressFormView: View {
    @EnvironmentObject private var account: AccountViewModel
    @Environment(\.dismiss) private var dismiss

    let existing: UserAddress?

    @State private var label: String
    @State private var address: String
    @State private var postalCode: String
    @State private var city: String
    @State private var isDefault: Bool
    @State private var isSaving = false
    @State private var showDeleteAlert = false

    init(address: UserAddress? = nil) {
        self.existing = address
        _label = State(initialValue: address?.label ?? "")
        _address = State(initialValue: address?.address ?? "")
        _postalCode = State(initialValue: address?.postalCode ?? "")
        _city = State(initialValue: address?.city ?? "")
        _isDefault = State(initialValue: address?.isDefault ?? false)
    }

    var body: some View {
        Form {
            AddressFormFieldsSection(
                label: $label,
                address: $address,
                postalCode: $postalCode,
                city: $city,
                isDefault: $isDefault
            )
            AddressFormErrorSection(error: account.error)
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
                isDisabled: isSaving || label.isEmpty || address.isEmpty || postalCode.isEmpty || city.isEmpty,
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
    }

    private func save() {
        Task {
            isSaving = true
            if let existing {
                var updated = existing
                updated.label = label
                updated.address = address
                updated.postalCode = postalCode
                updated.city = city
                updated.isDefault = isDefault
                await account.updateAddress(updated)
            } else {
                await account.addAddress(label: label, address: address, postalCode: postalCode, city: city, isDefault: isDefault)
            }
            isSaving = false
            if account.error == nil {
                dismiss()
            }
        }
    }
    
    private func deleteAddress() {
        guard let id = existing?.id else { return }
        Task {
            isSaving = true
            await account.deleteAddress(id: id)
            isSaving = false
            if account.error == nil {
                dismiss()
            }
        }
    }
}
