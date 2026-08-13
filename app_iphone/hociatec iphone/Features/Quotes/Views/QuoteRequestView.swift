import SwiftUI
#if canImport(UIKit)
import UIKit
#endif

struct QuoteRequestView: View {
    @StateObject private var viewModel: QuoteViewModel
    @State private var showingAddLineSheet = false
    @Environment(\.dismiss) private var dismiss

    init(viewModel: QuoteViewModel) {
        _viewModel = StateObject(wrappedValue: viewModel)
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

            Section {
                if viewModel.items.isEmpty {
                    Text("Ajoutez une ou plusieurs lignes (service, produit, ou ligne libre).")
                        .foregroundStyle(.secondary)
                } else {
                    ForEach(viewModel.items) { item in
                        NavigationLink {
                            QuoteLineEditorView(item: binding(for: item))
                        } label: {
                            QuoteLineRow(item: item)
                        }
                        .swipeActions {
                            Button(role: .destructive) { viewModel.removeLine(id: item.id) } label: {
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

            Section {
                LabeledContent("Total estimé") {
                    Text(PriceFormatter.format(cents: estimatedTotalCents))
                        .fontWeight(.semibold)
                }
                Text("Le total final (TVA, conditions) est calculé par le serveur.")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }

            Section {
                Button {
                    Task {
                        await viewModel.submit()
                        if viewModel.successMessage != nil {
#if canImport(UIKit)
                            UINotificationFeedbackGenerator().notificationOccurred(.success)
#endif
                            dismiss()
                        }
                    }
                } label: {
                    if viewModel.isSubmitting {
                        ProgressView().frame(maxWidth: .infinity)
                    } else {
                        Text("Envoyer la demande")
                            .fontWeight(.semibold)
                            .frame(maxWidth: .infinity)
                    }
                }
                .disabled(!canSubmit)
            }
        }
        .navigationTitle("Devis")
        .task { await viewModel.loadServices() }
        .sheet(isPresented: $showingAddLineSheet) {
            QuoteAddLineSheet(viewModel: viewModel)
        }
    }

    private var canSubmit: Bool {
        !viewModel.isSubmitting
            && !viewModel.items.isEmpty
            && !viewModel.name.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
            && !viewModel.email.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
    }

    private var estimatedTotalCents: Int {
        viewModel.items.reduce(0) { $0 + $1.lineTotalCents }
    }

    private func binding(for item: QuoteDraftItem) -> Binding<QuoteDraftItem> {
        guard let idx = viewModel.items.firstIndex(where: { $0.id == item.id }) else {
            return .constant(item)
        }
        return $viewModel.items[idx]
    }
}
