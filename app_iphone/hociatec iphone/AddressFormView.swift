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
            LabeledContent("Intitulé") {
                TextField("Domicile, bureau…", text: $label)
                    .textContentType(.nickname)
            }

            LabeledContent("Adresse") {
                TextField("Adresse complète", text: $address)
                    .textContentType(.fullStreetAddress)
            }

            LabeledContent("Code postal") {
                TextField("75001", text: $postalCode)
                    .keyboardType(.numbersAndPunctuation)
                    .textContentType(.postalCode)
            }

            LabeledContent("Ville") {
                TextField("Paris", text: $city)
                    .textContentType(.addressCity)
            }

            Toggle("Définir comme adresse par défaut", isOn: $isDefault)

            if let error = account.error {
                Section {
                    Text(error)
                        .foregroundStyle(.red)
                }
            }
            
            if existing?.id != nil {
                Section {
                    Button(role: .destructive) {
                        showDeleteAlert = true
                    } label: {
                        Text("Supprimer cette adresse")
                    }
                }
            }
        }
        .navigationTitle(existing == nil ? "Nouvelle adresse" : "Modifier l’adresse")
        .safeAreaInset(edge: .bottom) {
            VStack(spacing: 8) {
                Button(action: save) {
                    if isSaving {
                        ProgressView()
                    } else {
                        Text("Enregistrer")
                            .fontWeight(.semibold)
                            .frame(maxWidth: .infinity)
                    }
                }
                .buttonStyle(.borderedProminent)
                .disabled(isSaving || label.isEmpty || address.isEmpty || postalCode.isEmpty || city.isEmpty)
            }
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
