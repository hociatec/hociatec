import Foundation
import SwiftUI

struct ProductsListView: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var cart: CartViewModel
    @StateObject private var viewModel: ProductsViewModel
    @Binding var selectedTab: Int
    @Binding var filtersBadge: Int?
    private let navigationTitle: String
    @State private var useGrid: Bool = false
    @State private var showSortSheet: Bool = false
    @State private var showFilterSheet: Bool = false
    @State private var draftSelectedCategoryID: Int? = nil
    @State private var draftSelectedType: SellingType? = nil
    @State private var didInitDraftFilters: Bool = false

    init(
        viewModel: ProductsViewModel,
        selectedTab: Binding<Int>,
        filtersBadge: Binding<Int?>,
        navigationTitle: String = "Produits"
    ) {
        _viewModel = StateObject(wrappedValue: viewModel)
        self._selectedTab = selectedTab
        self._filtersBadge = filtersBadge
        self.navigationTitle = navigationTitle
    }

    var body: some View {
        List {
            if let error = viewModel.error {
                Section {
                    Text(error).foregroundStyle(.red)
                }
            }

            Section {
                ProductCatalogToolbar(
                    selectedCategory: viewModel.selectedCategory,
                    selectedSellingType: viewModel.selectedSellingType,
                    sort: viewModel.sort,
                    summaryText: summaryText,
                    onOpenFilters: { showFilterSheet = true },
                    onOpenSort: { showSortSheet = true },
                    onClearCategory: {
                        viewModel.selectedCategory = nil
                        Task { await viewModel.load(force: true) }
                    },
                    onClearSellingType: {
                        viewModel.selectedSellingType = nil
                        Task { await viewModel.load(force: true) }
                    }
                )
            }

            Section {
                if viewModel.isLoading && viewModel.products.isEmpty {
                    if useGrid {
                        let columns = [GridItem(.flexible()), GridItem(.flexible())]
                        LazyVGrid(columns: columns, spacing: 12) {
                            ForEach(0..<6, id: \.self) { _ in ShimmerTile() }
                        }
                        .listRowInsets(EdgeInsets())
                    } else {
                        VStack(spacing: 12) {
                            ForEach(0..<6, id: \.self) { _ in ShimmerRow() }
                        }
                    }
                } else {
                    if useGrid {
                        let columns = [GridItem(.flexible()), GridItem(.flexible())]
                        LazyVGrid(columns: columns, spacing: 12) {
                            ForEach(viewModel.products) { product in
                                ProductCatalogCard(
                                    product: product,
                                    imageURL: container.services.assets.assetURL(for: product.imageUrl),
                                    cart: cart,
                                    selectedTab: $selectedTab,
                                    isCompact: true
                                )
                                .environmentObject(container)
                            }
                        }
                        .listRowInsets(EdgeInsets())
                    } else {
                        ForEach(viewModel.products) { product in
                            ProductCatalogCard(
                                product: product,
                                imageURL: container.services.assets.assetURL(for: product.imageUrl),
                                cart: cart,
                                selectedTab: $selectedTab,
                                isCompact: false
                            )
                            .environmentObject(container)
                        }
                    }
                }
            }
        }
        .sheet(isPresented: $showSortSheet) {
            ProductSortSheet(
                selectedSort: viewModel.sort,
                onSelect: { sort in
                    viewModel.updateSort(sort)
                    showSortSheet = false
                },
                onClose: { showSortSheet = false }
            )
        }
        .sheet(isPresented: $showFilterSheet) {
            ProductFiltersSheet(
                categories: viewModel.categories,
                selectedCategoryID: $draftSelectedCategoryID,
                selectedSellingType: $draftSelectedType,
                currentCategoryID: viewModel.selectedCategory?.id,
                currentSellingType: viewModel.selectedSellingType,
                didInitDraftFilters: $didInitDraftFilters,
                onClose: { showFilterSheet = false },
                onApply: {
                    viewModel.selectedCategory = viewModel.categories.first(where: { $0.id == draftSelectedCategoryID })
                    viewModel.selectedSellingType = draftSelectedType
                    Task { await viewModel.load(force: true) }
                    showFilterSheet = false
                }
            )
        }
        .navigationTitle(navigationTitle)
        .searchable(text: $viewModel.search, placement: .navigationBarDrawer(displayMode: .always), prompt: "Rechercher")
        .onSubmit(of: .search) {
            Task { await viewModel.load(force: true) }
        }
        .task {
            await viewModel.loadCategoriesIfNeeded()
            await viewModel.load()
            await cart.refresh()
            updateFiltersBadge()
        }
        .refreshable {
            await viewModel.load(force: true)
            await cart.refresh()
        }
        .onChangeCompat(viewModel.selectedCategory?.id) { _ in if !showFilterSheet { updateFiltersBadge() } }
        .onChangeCompat(viewModel.selectedSellingType) { _ in if !showFilterSheet { updateFiltersBadge() } }
        .toolbar {
            ToolbarItem(placement: .topBarTrailing) {
                Button {
                    useGrid.toggle()
                } label: {
                    Image(systemName: useGrid ? "list.bullet" : "square.grid.2x2")
                }
            }
        }
    }

    private var summaryText: String {
        var parts: [String] = []
        if let category = viewModel.selectedCategory {
            parts.append("Catégorie: \(category.name)")
        }
        if let t = viewModel.selectedSellingType {
            parts.append("Type: " + (t == .rental ? "Location" : "Vente"))
        }
        return parts.joined(separator: " • ")
    }

    private func updateFiltersBadge() {
        let count = (viewModel.selectedCategory == nil ? 0 : 1) + (viewModel.selectedSellingType == nil ? 0 : 1)
        filtersBadge = count == 0 ? nil : count
    }
}
