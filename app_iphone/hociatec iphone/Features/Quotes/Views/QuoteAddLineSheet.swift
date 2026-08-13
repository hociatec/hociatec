import SwiftUI

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
