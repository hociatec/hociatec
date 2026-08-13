import SwiftUI

struct ProductCatalogToolbar: View {
    let selectedCategory: CategorySummary?
    let selectedSellingType: SellingType?
    let sort: ProductSortOption
    let summaryText: String
    let onOpenFilters: () -> Void
    let onOpenSort: () -> Void
    let onClearCategory: () -> Void
    let onClearSellingType: () -> Void

    private var filtersCount: Int {
        (selectedCategory == nil ? 0 : 1) + (selectedSellingType == nil ? 0 : 1)
    }

    private var sortLabel: String {
        switch sort {
        case .relevance: return "Pertinence"
        case .priceLowHigh: return "Prix croissant"
        case .priceHighLow: return "Prix décroissant"
        case .newest: return "Nouveautés"
        }
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            HStack {
                Button(action: onOpenFilters) {
                    Label(filtersCount > 0 ? "Filtres (\(filtersCount))" : "Filtres", systemImage: "line.3.horizontal.decrease.circle")
                }
                Spacer()
                Button(action: onOpenSort) {
                    Label("Trier (\(sortLabel))", systemImage: "arrow.up.arrow.down")
                }
            }

            if selectedCategory != nil || selectedSellingType != nil {
                Text(summaryText)
                    .font(.footnote)
                    .foregroundStyle(.secondary)

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
                                title: selectedSellingType == .rental ? "Location" : "Vente",
                                accessibilityLabel: "Retirer le filtre type",
                                onRemove: onClearSellingType
                            )
                        }
                    }
                }
            }
        }
    }
}

struct ProductSortSheet: View {
    let selectedSort: ProductSortOption
    let onSelect: (ProductSortOption) -> Void
    let onClose: () -> Void

    var body: some View {
        NavigationStack {
            List {
                ProductSortOptionRow(title: "Pertinence", isSelected: selectedSort == .relevance) {
                    onSelect(.relevance)
                }
                ProductSortOptionRow(title: "Prix croissant", isSelected: selectedSort == .priceLowHigh) {
                    onSelect(.priceLowHigh)
                }
                ProductSortOptionRow(title: "Prix décroissant", isSelected: selectedSort == .priceHighLow) {
                    onSelect(.priceHighLow)
                }
                ProductSortOptionRow(title: "Nouveautés", isSelected: selectedSort == .newest) {
                    onSelect(.newest)
                }
            }
            .navigationTitle("Trier")
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Fermer", action: onClose)
                }
            }
        }
    }
}

struct ProductFiltersSheet: View {
    let categories: [CategorySummary]
    @Binding var selectedCategoryID: Int?
    @Binding var selectedSellingType: SellingType?
    let currentCategoryID: Int?
    let currentSellingType: SellingType?
    @Binding var didInitDraftFilters: Bool
    let onClose: () -> Void
    let onApply: () -> Void

    private var hasChanges: Bool {
        selectedCategoryID != currentCategoryID || selectedSellingType != currentSellingType
    }

    var body: some View {
        NavigationStack {
            Form {
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

                Section("Type") {
                    Picker("Type", selection: $selectedSellingType) {
                        Text("Tous").tag(SellingType?.none)
                        Text("Vente").tag(Optional(SellingType.sale))
                        Text("Location").tag(Optional(SellingType.rental))
                    }
                    .pickerStyle(.segmented)
                }
            }
            .onAppear {
                guard !didInitDraftFilters else { return }
                selectedCategoryID = currentCategoryID
                selectedSellingType = currentSellingType
                didInitDraftFilters = true
            }
            .onDisappear {
                didInitDraftFilters = false
            }
            .navigationTitle("Filtres")
            .interactiveDismissDisabled(true)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Annuler", action: onClose)
                }
                ToolbarItem(placement: .bottomBar) {
                    Button("Réinitialiser") {
                        selectedCategoryID = nil
                        selectedSellingType = nil
                    }
                }
                ToolbarItem(placement: .confirmationAction) {
                    Button("Appliquer", action: onApply)
                        .disabled(!hasChanges)
                }
            }
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

private struct ProductSortOptionRow: View {
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
