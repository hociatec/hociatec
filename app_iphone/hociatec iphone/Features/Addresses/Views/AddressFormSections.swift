import SwiftUI

struct AddressFormFieldsSection: View {
    @Binding var label: String
    @Binding var address: String
    @Binding var postalCode: String
    @Binding var city: String
    @Binding var isDefault: Bool

    var body: some View {
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
    }
}

struct AddressFormErrorSection: View {
    let error: String?

    var body: some View {
        if let error {
            Section {
                Text(error)
                    .foregroundStyle(.red)
            }
        }
    }
}

struct AddressFormDeleteSection: View {
    let onDelete: () -> Void

    var body: some View {
        Section {
            Button(role: .destructive, action: onDelete) {
                Text("Supprimer cette adresse")
            }
        }
    }
}

struct AddressFormSaveBar: View {
    let isSaving: Bool
    let isDisabled: Bool
    let onSave: () -> Void

    var body: some View {
        VStack(spacing: 8) {
            Button(action: onSave) {
                if isSaving {
                    ProgressView()
                } else {
                    Text("Enregistrer")
                        .fontWeight(.semibold)
                        .frame(maxWidth: .infinity)
                }
            }
            .buttonStyle(.borderedProminent)
            .disabled(isDisabled)
        }
    }
}
