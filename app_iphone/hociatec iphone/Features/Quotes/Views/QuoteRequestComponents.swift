import SwiftUI

struct QuoteLineRow: View {
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

struct QuoteLineEditorView: View {
    @Binding var item: QuoteDraftItem
    @Environment(\.dismiss) private var dismiss
    @State private var unitPriceText: String = ""

    var body: some View {
        Form {
            Section {
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
                        LabeledContent("Prix unitaire") {
                            Text(PriceFormatter.format(cents: unitPriceCents))
                        }
                    }
                }
            }

            Section {
                Stepper("Quantité: \(item.quantity)", value: $item.quantity, in: 1...999)
            }

            Section {
                TextEditor(text: $item.description)
                    .frame(minHeight: 120)
            }
        }
        .navigationTitle("Modifier")
        .navigationBarTitleDisplayMode(.inline)
        .toolbar {
            ToolbarItem(placement: .confirmationAction) {
                Button("OK") {
                    if item.isCustom, let cents = QuoteMoneyParser.cents(from: unitPriceText) {
                        item.unitPriceCents = cents
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

struct QuoteAddLineSheet: View {
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
                        ForEach(Mode.allCases) { item in
                            Text(item.rawValue).tag(item)
                        }
                    }
                    .pickerStyle(.segmented)
                }

                switch mode {
                case .service:
                    QuoteServicePickerSection(
                        services: viewModel.filteredServices(matching: searchText),
                        isLoading: viewModel.isLoadingServices && viewModel.services.isEmpty,
                        searchText: $searchText,
                        onSelect: { service in
                            viewModel.addServiceLine(service)
                            dismiss()
                        }
                    )
                case .product:
                    QuoteProductPickerSection(
                        products: viewModel.productResults,
                        isSearching: viewModel.isSearching,
                        searchText: $searchText,
                        onSearch: {
                            viewModel.searchText = searchText
                            await viewModel.searchProducts()
                        },
                        onSelect: { product in
                            viewModel.addProductLine(product)
                            dismiss()
                        }
                    )
                case .custom:
                    QuoteCustomLineSection(
                        title: $customTitle,
                        unitPrice: $customUnitPrice,
                        quantity: $customQuantity,
                        description: $customDescription,
                        onAdd: {
                            guard let cents = QuoteMoneyParser.cents(from: customUnitPrice) else { return }
                            viewModel.addCustomLine(
                                title: customTitle,
                                unitPriceCents: cents,
                                quantity: customQuantity,
                                description: customDescription
                            )
                            dismiss()
                        }
                    )
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
}

private struct QuoteServicePickerSection: View {
    let services: [QuoteService]
    let isLoading: Bool
    @Binding var searchText: String
    let onSelect: (QuoteService) -> Void

    var body: some View {
        Section {
            if isLoading {
                ProgressView("Chargement...")
            } else {
                TextField("Rechercher", text: $searchText)
                if services.isEmpty {
                    Text("Aucun service correspondant.")
                        .foregroundStyle(.secondary)
                } else {
                    ForEach(services) { service in
                        Button {
                            onSelect(service)
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
}

private struct QuoteProductPickerSection: View {
    let products: [Product]
    let isSearching: Bool
    @Binding var searchText: String
    let onSearch: () async -> Void
    let onSelect: (Product) -> Void

    var body: some View {
        Section {
            TextField("Rechercher", text: $searchText)
            HStack {
                Button("Rechercher") {
                    Task { await onSearch() }
                }
                .disabled(searchText.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)

                if isSearching {
                    Spacer()
                    ProgressView()
                }
            }

            if !products.isEmpty {
                ForEach(products) { product in
                    Button {
                        onSelect(product)
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
}

private struct QuoteCustomLineSection: View {
    @Binding var title: String
    @Binding var unitPrice: String
    @Binding var quantity: Int
    @Binding var description: String
    let onAdd: () -> Void

    private var canAdd: Bool {
        !title.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
            && QuoteMoneyParser.cents(from: unitPrice) != nil
    }

    var body: some View {
        Section {
            TextField("Titre", text: $title)
            TextField("Prix unitaire (€)", text: $unitPrice)
                .keyboardType(.decimalPad)
            Stepper("Quantité: \(quantity)", value: $quantity, in: 1...999)
            TextEditor(text: $description)
                .frame(minHeight: 120)

            Button("Ajouter") {
                onAdd()
            }
            .disabled(!canAdd)
        }
    }
}
