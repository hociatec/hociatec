import SwiftUI

struct ProductCatalogActiveFiltersRow: View {
    let selectedCategory: CategorySummary?
    let selectedBrand: String?
    let selectedAttributeFilters: [String: String]
    let minPrice: Double?
    let maxPrice: Double?
    let inStockOnly: Bool
    let availableAttributeFacets: [CatalogAttributeFacet]
    let onClearCategory: () -> Void
    let onClearBrand: () -> Void
    let onClearAttributeFilter: (String) -> Void
    let onClearPriceRange: () -> Void
    let onClearInStock: () -> Void

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

                if let selectedBrand, !selectedBrand.isEmpty {
                    ProductFilterChip(
                        title: selectedBrand,
                        accessibilityLabel: "Retirer le filtre marque \(selectedBrand)",
                        onRemove: onClearBrand
                    )
                }

                if minPrice != nil || maxPrice != nil {
                    ProductFilterChip(
                        title: "Prix: \(priceRangeLabel(min: minPrice, max: maxPrice))",
                        accessibilityLabel: "Retirer le filtre prix",
                        onRemove: onClearPriceRange
                    )
                }

                if inStockOnly {
                    ProductFilterChip(
                        title: "En stock",
                        accessibilityLabel: "Retirer le filtre stock",
                        onRemove: onClearInStock
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
    let categoryFacets: [CatalogFacetCount]
    @Binding var selectedCategoryID: Int?

    private var categoryOptions: [(id: Int?, label: String)] {
        let bySlug = Dictionary(uniqueKeysWithValues: categories.map { ($0.slug, $0) })
        var options: [(id: Int?, label: String)] = [(nil, "Tout")]

        if !categoryFacets.isEmpty {
            for facet in categoryFacets {
                guard let slug = facet.extra, let category = bySlug[slug] else { continue }
                options.append((category.id, "\(category.name) (\(facet.count))"))
            }
        } else {
            options.append(contentsOf: categories.map { ($0.id, $0.name) })
        }

        if let selectedCategoryID,
           !options.contains(where: { $0.id == selectedCategoryID }),
           let selectedCategory = categories.first(where: { $0.id == selectedCategoryID }) {
            options.append((selectedCategory.id, selectedCategory.name))
        }

        return options
    }

    var body: some View {
        Section("Catégorie") {
            if categories.isEmpty {
                Text("Chargement...")
                    .foregroundStyle(.secondary)
            } else {
                Picker("Catégorie", selection: $selectedCategoryID) {
                    ForEach(Array(categoryOptions.enumerated()), id: \.offset) { entry in
                        let option = entry.element
                        Text(option.label).tag(option.id)
                    }
                }
                .pickerStyle(.inline)
                .labelsHidden()
            }
        }
    }
}

struct ProductCatalogBrandFilterSection: View {
    let brands: [CatalogFacetCount]
    @Binding var selectedBrand: String?

    var body: some View {
        Section("Marques") {
            Picker("Marque", selection: $selectedBrand) {
                Text("Tout").tag(String?.none)
                ForEach(brands) { brand in
                    Text("\(brand.value) (\(brand.count))").tag(Optional(brand.value))
                }
            }
            .pickerStyle(.inline)
        }
    }
}

struct ProductCatalogPriceFilterSection: View {
    @Binding var minPrice: String
    @Binding var maxPrice: String
    let availableRange: CatalogPriceRange

    var body: some View {
        Section("Prix") {
            TextField("Prix min", text: $minPrice)
                .keyboardType(.decimalPad)
            TextField("Prix max", text: $maxPrice)
                .keyboardType(.decimalPad)

            if availableRange.min != nil || availableRange.max != nil {
                Text("Plage disponible: \(ProductCatalogFilterPresentation.availablePriceLabel(for: availableRange))")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
        }
    }
}

struct ProductCatalogStockFilterSection: View {
    @Binding var inStockOnly: Bool

    var body: some View {
        Section("Disponibilité") {
            Toggle("Uniquement en stock", isOn: $inStockOnly)
        }
    }
}

struct ProductCatalogAttributeFilterSection: View {
    let facet: CatalogAttributeFacet
    @Binding var selectedValue: String?

    var body: some View {
        Section(facet.label) {
            Picker(facet.label, selection: $selectedValue) {
                Text("Tout").tag(String?.none)
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
    @Binding var selectedBrand: String?
    @Binding var selectedAttributeFilters: [String: String]
    @Binding var minPrice: String
    @Binding var maxPrice: String
    @Binding var inStockOnly: Bool

    var body: some View {
        Button("Réinitialiser") {
            selectedCategoryID = nil
            selectedBrand = nil
            selectedAttributeFilters = [:]
            minPrice = ""
            maxPrice = ""
            inStockOnly = false
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
