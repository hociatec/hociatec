import SwiftUI

struct QuoteProductPickerSection: View {
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
