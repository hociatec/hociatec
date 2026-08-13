import SwiftUI

struct TradeInStatusSection: View {
    let error: String?
    let successMessage: String?

    var body: some View {
        if let error, !error.isEmpty {
            Section {
                Text(error)
                    .foregroundStyle(.red)
            }
        }

        if let successMessage, !successMessage.isEmpty {
            Section {
                Label(successMessage, systemImage: "checkmark.seal.fill")
                    .foregroundStyle(.green)
            }
        }
    }
}

struct TradeInProductSection: View {
    @ObservedObject var viewModel: TradeInViewModel

    var body: some View {
        Section {
            Picker("Catégorie", selection: $viewModel.selectedCategory) {
                ForEach(viewModel.categories) { option in
                    Text(option.label).tag(option.value)
                }
            }
            TextField("Nom du produit", text: $viewModel.productName)
            TextField("Marque", text: $viewModel.brand)
            TextField("Modèle", text: $viewModel.model)
            TextField("Numéro de série", text: $viewModel.serialNumber)
            TextField("Prix d’achat (€)", text: $viewModel.purchasePrice)
                .keyboardType(.decimalPad)
            TextField("Année d’achat", text: $viewModel.purchaseYear)
                .keyboardType(.numberPad)
        }
    }
}

struct TradeInConditionSection: View {
    @ObservedObject var viewModel: TradeInViewModel

    var body: some View {
        Section("État") {
            Picker("État", selection: $viewModel.selectedCondition) {
                ForEach(viewModel.conditions) { option in
                    Text(option.label).tag(option.value)
                }
            }
            Toggle("Appareil fonctionnel", isOn: $viewModel.functional)
            Toggle("Accessoires inclus", isOn: $viewModel.hasAccessories)
            Toggle("Preuve d’achat disponible", isOn: $viewModel.hasProofOfPurchase)
            TextEditor(text: $viewModel.description)
                .frame(minHeight: 120)
        }
    }
}

struct TradeInContactSection: View {
    @ObservedObject var viewModel: TradeInViewModel

    var body: some View {
        Section("Contact") {
            TextField("Prénom", text: $viewModel.firstName)
                .textInputAutocapitalization(.words)
            TextField("Nom", text: $viewModel.lastName)
                .textInputAutocapitalization(.words)
            TextField("E-mail", text: $viewModel.email)
                .keyboardType(.emailAddress)
                .textInputAutocapitalization(.never)
                .autocorrectionDisabled()
            TextField("Téléphone", text: $viewModel.phone)
                .keyboardType(.phonePad)
        }
    }
}

struct TradeInRibSection: View {
    let ribFileName: String?
    let onPickPDF: () -> Void

    var body: some View {
        Section("RIB") {
            Button("Choisir un PDF") {
                onPickPDF()
            }

            if let ribFileName {
                Text(ribFileName)
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
        }
    }
}

struct TradeInConsentSection: View {
    @Binding var consent: Bool

    var body: some View {
        Section {
            Toggle("J’accepte le traitement de ma demande", isOn: $consent)
        }
    }
}

struct TradeInSubmitSection: View {
    let isSubmitting: Bool
    let onSubmit: () async -> Void

    var body: some View {
        Section {
            Button {
                Task {
                    await onSubmit()
                }
            } label: {
                if isSubmitting {
                    ProgressView()
                        .frame(maxWidth: .infinity)
                } else {
                    Text("Envoyer la reprise")
                        .fontWeight(.semibold)
                        .frame(maxWidth: .infinity)
                }
            }
            .disabled(isSubmitting)
        }
    }
}
