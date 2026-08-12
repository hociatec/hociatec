import SwiftUI
#if canImport(UIKit)
import UIKit
#endif

struct QuoteRequestView: View {
    @StateObject private var viewModel: QuoteViewModel
    @State private var showingAddLineSheet = false
    @Environment(\.dismiss) private var dismiss

    init(api: APIClient, account: AccountViewModel) {
        _viewModel = StateObject(wrappedValue: QuoteViewModel(api: api, account: account))
    }

    var body: some View {
        Form {
            if let error = viewModel.error, !error.isEmpty {
                Section { Text(error).foregroundStyle(.red) }
            }
            if let success = viewModel.successMessage, !success.isEmpty {
                Section { Label(success, systemImage: "checkmark.seal.fill").foregroundStyle(.green) }
            }

            Section("Client") {
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

            Section("Lignes") {
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

            Section("Résumé") {
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
        .navigationTitle("Demander un devis")
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

private struct QuoteLineRow: View {
    let item: QuoteDraftItem

    private var badge: String {
        if item.serviceId != nil { return "Service" }
        if item.productId != nil { return "Produit" }
        return "Libre"
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            HStack(alignment: .firstTextBaseline, spacing: 8) {
                Text(item.displayTitle)
                    .fontWeight(.semibold)
                    .lineLimit(1)
                Text(badge)
                    .font(.caption2)
                    .padding(.horizontal, 6)
                    .padding(.vertical, 2)
                    .background(Color.blue.opacity(0.1))
                    .foregroundColor(.blue)
                    .clipShape(Capsule())
            }
            HStack {
                Text("\(item.quantity) × \(PriceFormatter.format(cents: item.unitPriceCents ?? 0))")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
                Spacer()
                Text(PriceFormatter.format(cents: item.lineTotalCents))
                    .fontWeight(.semibold)
            }
            if !item.description.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
                Text(item.description)
                    .font(.footnote)
                    .foregroundStyle(.secondary)
                    .lineLimit(2)
            }
        }
    }
}

private struct QuoteLineEditorView: View {
    @Binding var item: QuoteDraftItem
    @Environment(\.dismiss) private var dismiss
    @State private var unitPriceText: String = ""

    var body: some View {
        Form {
            Section("Ligne") {
                if item.isCustom {
                    TextField("Titre", text: Binding(
                        get: { item.title ?? "" },
                        set: { item.title = $0 }
                    ))
                    TextField("Prix unitaire (€)", text: $unitPriceText)
                        .keyboardType(.decimalPad)
                } else {
                    Text(item.displayTitle)
                    if let unitPriceCents = item.unitPriceCents {
                        LabeledContent("Prix unitaire") { Text(PriceFormatter.format(cents: unitPriceCents)) }
                    }
                }
            }

            Section("Quantité") {
                Stepper("Quantité: \(item.quantity)", value: $item.quantity, in: 1...999)
            }

            Section("Description (optionnel)") {
                TextEditor(text: $item.description)
                    .frame(minHeight: 120)
            }
        }
        .navigationTitle("Modifier")
        .navigationBarTitleDisplayMode(.inline)
        .toolbar {
            ToolbarItem(placement: .confirmationAction) {
                Button("OK") {
                    if item.isCustom {
                        if let cents = QuoteMoneyParser.cents(from: unitPriceText) {
                            item.unitPriceCents = cents
                        }
                    }
                    dismiss()
                }
            }
        }
        .onAppear {
            unitPriceText = QuoteMoneyParser.string(fromCents: item.unitPriceCents)
        }
    }
}

private struct QuoteAddLineSheet: View {
    enum Mode: String, CaseIterable, Identifiable {
        case service = "Service"
        case product = "Produit"
        case custom = "Libre"
        var id: String { rawValue }
    }

    @ObservedObject var viewModel: QuoteViewModel
    @Environment(\.dismiss) private var dismiss
    @State private var mode: Mode = .service

    @State private var searchText: String = ""
    @State private var customTitle: String = ""
    @State private var customUnitPrice: String = ""
    @State private var customQuantity: Int = 1
    @State private var customDescription: String = ""

    var body: some View {
        NavigationStack {
            Form {
                Section {
                    Picker("Type", selection: $mode) {
                        ForEach(Mode.allCases) { m in
                            Text(m.rawValue).tag(m)
                        }
                    }
                    .pickerStyle(.segmented)
                }

                if mode == .service {
                    serviceSection
                } else if mode == .product {
                    productSection
                } else {
                    customSection
                }
            }
            .navigationTitle("Ajouter une ligne")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Fermer") { dismiss() }
                }
            }
            .task {
                if viewModel.services.isEmpty {
                    await viewModel.loadServices()
                }
            }
        }
    }

    private var serviceSection: some View {
        Section("Services") {
            if viewModel.isLoadingServices && viewModel.services.isEmpty {
                ProgressView("Chargement...")
            } else {
                TextField("Rechercher", text: $searchText)
                let items = filteredServices
                if items.isEmpty {
                    Text("Aucun service correspondant.")
                        .foregroundStyle(.secondary)
                } else {
                    ForEach(items) { service in
                        Button {
                            viewModel.addServiceLine(service)
                            dismiss()
                        } label: {
                            HStack {
                                VStack(alignment: .leading, spacing: 4) {
                                    Text(service.title).fontWeight(.semibold)
                                    if let description = service.description, !description.isEmpty {
                                        Text(description)
                                            .font(.caption)
                                            .foregroundStyle(.secondary)
                                            .lineLimit(2)
                                    }
                                }
                                Spacer()
                                Text(PriceFormatter.format(cents: service.priceCents))
                                    .foregroundStyle(.secondary)
                            }
                        }
                        .buttonStyle(.plain)
                    }
                }
            }
        }
    }

    private var productSection: some View {
        Section("Produits") {
            TextField("Rechercher", text: $searchText)
            HStack {
                Button("Rechercher") {
                    Task {
                        viewModel.searchText = searchText
                        await viewModel.searchProducts()
                    }
                }
                .disabled(searchText.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
                if viewModel.isSearching {
                    Spacer()
                    ProgressView()
                }
            }

            if !viewModel.productResults.isEmpty {
                ForEach(viewModel.productResults) { product in
                    Button {
                        viewModel.addProductLine(product)
                        dismiss()
                    } label: {
                        VStack(alignment: .leading, spacing: 4) {
                            Text(product.name).fontWeight(.semibold)
                            Text(product.shortDescription)
                                .font(.caption)
                                .foregroundStyle(.secondary)
                                .lineLimit(2)
                            Text(PriceFormatter.format(cents: product.effectivePriceCents))
                                .font(.footnote)
                                .foregroundStyle(.secondary)
                        }
                    }
                    .buttonStyle(.plain)
                }
            } else {
                Text("Lancez une recherche pour afficher des produits.")
                    .foregroundStyle(.secondary)
            }
        }
    }

    private var customSection: some View {
        Section("Ligne libre") {
            TextField("Titre", text: $customTitle)
            TextField("Prix unitaire (€)", text: $customUnitPrice)
                .keyboardType(.decimalPad)
            Stepper("Quantité: \(customQuantity)", value: $customQuantity, in: 1...999)
            TextEditor(text: $customDescription)
                .frame(minHeight: 120)

            Button("Ajouter") {
                guard let cents = QuoteMoneyParser.cents(from: customUnitPrice) else { return }
                viewModel.addCustomLine(
                    title: customTitle,
                    unitPriceCents: cents,
                    quantity: customQuantity,
                    description: customDescription
                )
                dismiss()
            }
            .disabled(!canAddCustom)
        }
    }

    private var canAddCustom: Bool {
        !customTitle.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
            && QuoteMoneyParser.cents(from: customUnitPrice) != nil
    }

    private var filteredServices: [QuoteService] {
        let q = searchText.trimmingCharacters(in: .whitespacesAndNewlines).lowercased()
        guard !q.isEmpty else { return viewModel.services }
        return viewModel.services.filter {
            $0.title.lowercased().contains(q) || ($0.description?.lowercased().contains(q) ?? false)
        }
    }
}

private enum QuoteMoneyParser {
    static func string(fromCents cents: Int?) -> String {
        guard let cents else { return "" }
        let value = Double(cents) / 100.0
        if value == floor(value) {
            return String(Int(value))
        }
        return String(format: "%.2f", value).replacingOccurrences(of: ".", with: ",")
    }

    static func cents(from input: String) -> Int? {
        let cleaned = input
            .replacingOccurrences(of: "€", with: "")
            .replacingOccurrences(of: " ", with: "")
            .trimmingCharacters(in: .whitespacesAndNewlines)
            .replacingOccurrences(of: ",", with: ".")
        guard !cleaned.isEmpty else { return nil }
        guard let value = Double(cleaned) else { return nil }
        if value < 0 { return nil }
        return Int((value * 100).rounded())
    }
}
