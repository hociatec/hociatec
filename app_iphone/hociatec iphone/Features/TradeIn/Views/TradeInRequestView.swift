import SwiftUI
import UniformTypeIdentifiers
#if canImport(UIKit)
import UIKit
#endif

struct TradeInRequestView: View {
    @StateObject private var viewModel: TradeInViewModel
    @State private var showingFileImporter = false
    @Environment(\.dismiss) private var dismiss

    init(service: TradeInServing, account: AccountViewModel) {
        _viewModel = StateObject(wrappedValue: TradeInViewModel(service: service, account: account))
    }

    var body: some View {
        Form {
            if let error = viewModel.error, !error.isEmpty {
                Section { Text(error).foregroundStyle(.red) }
            }
            if let success = viewModel.successMessage, !success.isEmpty {
                Section { Label(success, systemImage: "checkmark.seal.fill").foregroundStyle(.green) }
            }

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

            Section("RIB") {
                Button("Choisir un PDF") {
                    showingFileImporter = true
                }
                if let ribFileName = viewModel.ribFileName {
                    Text(ribFileName)
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                }
            }

            Section {
                Toggle("J’accepte le traitement de ma demande", isOn: $viewModel.consent)
            }

            Section {
                Button {
                    Task {
                        let ok = await viewModel.submit()
                        if ok {
#if canImport(UIKit)
                            UINotificationFeedbackGenerator().notificationOccurred(.success)
#endif
                            dismiss()
                        }
                    }
                } label: {
                    if viewModel.isSubmitting {
                        ProgressView()
                            .frame(maxWidth: .infinity)
                    } else {
                        Text("Envoyer la reprise")
                            .fontWeight(.semibold)
                            .frame(maxWidth: .infinity)
                    }
                }
                .disabled(viewModel.isSubmitting)
            }
        }
        .navigationTitle("Reprise")
        .task { await viewModel.loadMetadata() }
        .fileImporter(
            isPresented: $showingFileImporter,
            allowedContentTypes: [.pdf],
            allowsMultipleSelection: false
        ) { result in
            switch result {
            case .success(let urls):
                guard let url = urls.first else { return }
                let accessed = url.startAccessingSecurityScopedResource()
                defer {
                    if accessed {
                        url.stopAccessingSecurityScopedResource()
                    }
                }

                do {
                    let data = try Data(contentsOf: url)
                    let fileName = url.lastPathComponent.isEmpty ? "rib.pdf" : url.lastPathComponent
                    viewModel.setRib(fileName: fileName, data: data)
                } catch {
                    viewModel.error = "Impossible de lire le PDF sélectionné."
                }
            case .failure:
                viewModel.error = "Sélection du PDF annulée ou invalide."
            }
        }
    }
}
