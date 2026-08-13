import SwiftUI

struct QuoteRequestFeedbackSection: View {
    let error: String?
    let success: String?

    var body: some View {
        if let error, !error.isEmpty {
            Section {
                Text(error)
                    .foregroundStyle(.red)
            }
        }

        if let success, !success.isEmpty {
            Section {
                Label(success, systemImage: "checkmark.seal.fill")
                    .foregroundStyle(.green)
            }
        }
    }
}

struct QuoteRequestIdentitySection: View {
    @ObservedObject var viewModel: QuoteViewModel

    var body: some View {
        Section {
            TextField("Nom", text: $viewModel.name)
                .textContentType(.name)
            TextField("Email", text: $viewModel.email)
                .keyboardType(.emailAddress)
                .textInputAutocapitalization(.never)
                .textContentType(.emailAddress)
            TextField("Société (optionnel)", text: $viewModel.company)
            TextField("Adresse (optionnel)", text: $viewModel.address)

            Button("Utiliser mon profil") {
                viewModel.prefillFromAccount()
            }
            .disabled(viewModel.isSubmitting)
        }
    }
}

struct QuoteRequestItemsSection: View {
    @ObservedObject var viewModel: QuoteViewModel
    @Binding var showingAddLineSheet: Bool
    let bindingForItem: (QuoteDraftItem) -> Binding<QuoteDraftItem>

    var body: some View {
        Section {
            if viewModel.items.isEmpty {
                Text("Ajoutez une ou plusieurs lignes (service, produit, ou ligne libre).")
                    .foregroundStyle(.secondary)
            } else {
                ForEach(viewModel.items) { item in
                    NavigationLink {
                        QuoteLineEditorView(item: bindingForItem(item))
                    } label: {
                        QuoteLineRow(item: item)
                    }
                    .swipeActions {
                        Button(role: .destructive) {
                            viewModel.removeLine(id: item.id)
                        } label: {
                            Label("Supprimer", systemImage: "trash")
                        }
                    }
                }
            }

            Button("Ajouter une ligne", systemImage: "plus") {
                showingAddLineSheet = true
            }
            .disabled(viewModel.isSubmitting)
        }
    }
}

struct QuoteRequestSummarySection: View {
    let estimatedTotalCents: Int

    var body: some View {
        Section {
            LabeledContent("Total estimé") {
                Text(PriceFormatter.format(cents: estimatedTotalCents))
                    .fontWeight(.semibold)
            }
            Text("Le total final (TVA, conditions) est calculé par le serveur.")
                .font(.footnote)
                .foregroundStyle(.secondary)
        }
    }
}

struct QuoteRequestSubmitSection: View {
    let isSubmitting: Bool
    let canSubmit: Bool
    let onSubmit: () -> Void

    var body: some View {
        Section {
            Button(action: onSubmit) {
                if isSubmitting {
                    ProgressView()
                        .frame(maxWidth: .infinity)
                } else {
                    Text("Envoyer la demande")
                        .fontWeight(.semibold)
                        .frame(maxWidth: .infinity)
                }
            }
            .disabled(!canSubmit)
        }
    }
}
