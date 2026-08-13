import SwiftUI

struct ProductCatalogActiveFiltersRow: View {
    let selectedCategory: CategorySummary?
    let selectedSellingType: SellingType?
    let onClearCategory: () -> Void
    let onClearSellingType: () -> Void

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

struct ProductCatalogResetFiltersButton: View {
    @Binding var selectedCategoryID: Int?
    @Binding var selectedSellingType: SellingType?

    var body: some View {
        Button("Réinitialiser") {
            selectedCategoryID = nil
            selectedSellingType = nil
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
