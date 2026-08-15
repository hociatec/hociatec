import Foundation
import SwiftUI

struct ProductsListView: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var cart: CartViewModel
    @StateObject var viewModel: ProductsViewModel
    @Binding var selectedTab: Int
    @Binding var filtersBadge: Int?
    private let navigationTitle: String
    @State private var useGrid: Bool = false
    @State private var showSortSheet: Bool = false
    @State private var showFilterSheet: Bool = false
    @State private var filterDraft = ProductCatalogFilterDraft()
    @State private var didLoadInitialContent = false

    init(
        viewModel: ProductsViewModel,
        selectedTab: Binding<Int>,
        filtersBadge: Binding<Int?>,
        navigationTitle: String = "Produits",
        initialSearch: String = ""
    ) {
        viewModel.search = initialSearch
        _viewModel = StateObject(wrappedValue: viewModel)
        self._selectedTab = selectedTab
        self._filtersBadge = filtersBadge
        self.navigationTitle = navigationTitle
    }

    var body: some View {
        List {
            ProductsListToolbarSection(
                viewModel: viewModel,
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
                },
                onClearBrand: {
                    viewModel.clearBrandFilter()
                    Task { await viewModel.load(force: true) }
                },
                onClearAttributeFilter: { code in
                    viewModel.clearAttributeFilter(code: code)
                    Task { await viewModel.load(force: true) }
                }
            )

            ProductsListContentSection(
                viewModel: viewModel,
                selectedTab: $selectedTab,
                useGrid: useGrid
            )
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
                facets: viewModel.availableFacets,
                selectedCategoryID: $filterDraft.selectedCategoryID,
                selectedSellingType: $filterDraft.selectedSellingType,
                selectedBrand: $filterDraft.selectedBrand,
                selectedAttributeFilters: $filterDraft.selectedAttributeFilters,
                currentCategoryID: viewModel.selectedCategory?.id,
                currentSellingType: viewModel.selectedSellingType,
                currentBrand: viewModel.selectedBrand,
                currentAttributeFilters: viewModel.selectedAttributeFilters,
                didInitDraftFilters: $filterDraft.didInit,
                onClose: { showFilterSheet = false },
                onApply: {
                    viewModel.selectedCategory = viewModel.categories.first(where: { $0.id == filterDraft.selectedCategoryID })
                    viewModel.selectedSellingType = filterDraft.selectedSellingType
                    viewModel.selectedBrand = filterDraft.selectedBrand
                    viewModel.selectedAttributeFilters = filterDraft.selectedAttributeFilters
                    Task { await viewModel.load(force: true) }
                    showFilterSheet = false
                }
            )
        }
        .navigationTitle(navigationTitle)
        .searchable(text: $viewModel.search, placement: .navigationBarDrawer(displayMode: .always), prompt: "Rechercher")
        .onSubmit(of: .search) {
            viewModel.applySearch()
            Task { await viewModel.load(force: true) }
        }
        .task {
            guard !didLoadInitialContent else { return }
            didLoadInitialContent = true
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
        .onChangeCompat(viewModel.selectedBrand) { _ in if !showFilterSheet { updateFiltersBadge() } }
        .toolbar {
            ToolbarItem(placement: .topBarTrailing) {
                Button {
                    useGrid.toggle()
                } label: {
                    Image(systemName: useGrid ? "list.bullet" : "square.grid.2x2")
                }
            }
        }
        .feedbackDialog(error: $viewModel.error)
    }
}
