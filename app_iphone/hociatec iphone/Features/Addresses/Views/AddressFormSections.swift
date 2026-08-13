import SwiftUI

struct AddressFormFieldsSection: View {
    @Binding var type: String
    @Binding var label: String
    @Binding var address: String
    @Binding var addressComplement: String
    @Binding var postalCode: String
    @Binding var city: String
    @Binding var company: String
    @Binding var companySiren: String
    @Binding var companyVatNumber: String
    @Binding var isDefault: Bool
    let isLocating: Bool
    let onUseCurrentLocation: () -> Void

    var body: some View {
        Section {
            Button(action: onUseCurrentLocation) {
                if isLocating {
                    HStack {
                        ProgressView()
                        Text("Localisation en cours...")
                    }
                } else {
                    Label("Utiliser ma position actuelle", systemImage: "location")
                }
            }
            .disabled(isLocating)
        }

        Section("Adresse") {
            Picker("Type", selection: $type) {
                Text("Personnelle").tag("personal")
                Text("Professionnelle").tag("professional")
            }

            LabeledContent("Intitulé") {
                TextField("Domicile, bureau…", text: $label)
                    .textContentType(.nickname)
            }

            LabeledContent("Adresse") {
                TextField("Adresse complète", text: $address)
                    .textContentType(.fullStreetAddress)
            }

            LabeledContent("Complément") {
                TextField("Bâtiment, étage, appartement...", text: $addressComplement)
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

            if type == "professional" {
                LabeledContent("Société") {
                    TextField("Nom de la société", text: $company)
                }

                LabeledContent("SIREN client") {
                    TextField("123456789", text: $companySiren)
                        .keyboardType(.numbersAndPunctuation)
                }

                LabeledContent("TVA intracommunautaire") {
                    TextField("FR12345678901", text: $companyVatNumber)
                }
            }

            Toggle("Définir comme adresse par défaut", isOn: $isDefault)
        }
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
