import SwiftUI

struct ProductCatalogActiveFiltersRow: View {
    let selectedCategory: CategorySummary?
    let selectedSellingType: SellingType?
    let selectedBrand: String?
    let selectedAttributeFilters: [String: String]
    let availableAttributeFacets: [CatalogAttributeFacet]
    let onClearCategory: () -> Void
    let onClearSellingType: () -> Void
    let onClearBrand: () -> Void
    let onClearAttributeFilter: (String) -> Void

    var body: some View {
        ScrollView(.horizontal, showsIndicators: false) {
            HStack(spacing: 8) {
                if let selectedCategory {
                    ProductFilterChip(
                        title: selectedCategory.name,
                        accessibilityLabel: "Retirer le filtre \(selectedCategory.name)",
                        onRemove: onClearCategory
                    )
                }

                if let selectedSellingType {
                    ProductFilterChip(
                        title: ProductCatalogFilterPresentation.sellingTypeLabel(for: selectedSellingType),
                        accessibilityLabel: "Retirer le filtre type",
                        onRemove: onClearSellingType
                    )
                }

                if let selectedBrand, !selectedBrand.isEmpty {
                    ProductFilterChip(
                        title: selectedBrand,
                        accessibilityLabel: "Retirer le filtre marque \(selectedBrand)",
                        onRemove: onClearBrand
                    )
                }

                ForEach(availableAttributeFacets) { facet in
                    if let value = selectedAttributeFilters[facet.code], !value.isEmpty {
                        ProductFilterChip(
                            title: "\(facet.label): \(value)",
                            accessibilityLabel: "Retirer le filtre \(facet.label)",
                            onRemove: { onClearAttributeFilter(facet.code) }
                        )
                    }
                }
            }
        }
    }
}

struct ProductCatalogCategoryFilterSection: View {
    let categories: [CategorySummary]
    @Binding var selectedCategoryID: Int?

    var body: some View {
        Section("Catégories") {
            if categories.isEmpty {
                Text("Chargement...")
                    .foregroundStyle(.secondary)
            } else {
                Picker("Catégorie", selection: $selectedCategoryID) {
                    Text("Toutes").tag(Int?.none)
                    ForEach(categories) { category in
                        Text(category.name).tag(Optional(category.id))
                    }
                }
                .pickerStyle(.inline)
            }
        }
    }
}

struct ProductCatalogSellingTypeFilterSection: View {
    @Binding var selectedSellingType: SellingType?

    var body: some View {
        Section("Type") {
            Picker("Type", selection: $selectedSellingType) {
                Text("Tous").tag(SellingType?.none)
                Text("Vente").tag(Optional(SellingType.sale))
                Text("Location").tag(Optional(SellingType.rental))
            }
            .pickerStyle(.segmented)
        }
    }
}

struct ProductCatalogBrandFilterSection: View {
    let brands: [CatalogFacetCount]
    @Binding var selectedBrand: String?

    var body: some View {
        Section("Marques") {
            Picker("Marque", selection: $selectedBrand) {
                Text("Toutes").tag(String?.none)
                ForEach(brands) { brand in
                    Text("\(brand.value) (\(brand.count))").tag(Optional(brand.value))
                }
            }
            .pickerStyle(.inline)
        }
    }
}

struct ProductCatalogAttributeFilterSection: View {
    let facet: CatalogAttributeFacet
    @Binding var selectedValue: String?

    var body: some View {
        Section(facet.label) {
            Picker(facet.label, selection: $selectedValue) {
                Text("Tous").tag(String?.none)
                ForEach(facet.values) { value in
                    Text("\(value.value) (\(value.count))").tag(Optional(value.value))
                }
            }
            .pickerStyle(.inline)
        }
    }
}

struct ProductCatalogResetFiltersButton: View {
    @Binding var selectedCategoryID: Int?
    @Binding var selectedSellingType: SellingType?
    @Binding var selectedBrand: String?
    @Binding var selectedAttributeFilters: [String: String]

    var body: some View {
        Button("Réinitialiser") {
            selectedCategoryID = nil
            selectedSellingType = nil
            selectedBrand = nil
            selectedAttributeFilters = [:]
        }
    }
}

private struct ProductFilterChip: View {
    let title: String
    let accessibilityLabel: String
    let onRemove: () -> Void

    var body: some View {
        HStack(spacing: 6) {
            Text(title)
            Button(action: onRemove) {
                Image(systemName: "xmark.circle.fill")
            }
            .buttonStyle(.plain)
            .accessibilityLabel(accessibilityLabel)
        }
        .padding(.horizontal, 10)
        .padding(.vertical, 6)
        .background(Color.blue.opacity(0.1))
        .foregroundColor(.blue)
        .clipShape(Capsule())
    }
}

struct ProductSortOptionRow: View {
    let title: String
    let isSelected: Bool
    let action: () -> Void

    var body: some View {
        Button(action: action) {
            HStack {
                Text(title)
                if isSelected {
                    Spacer()
                    Image(systemName: "checkmark")
                }
            }
        }
    }
}
