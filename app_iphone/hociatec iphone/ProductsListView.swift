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
        api: APIClient,
        selectedTab: Binding<Int>,
        filtersBadge: Binding<Int?>,
        initialSellingType: SellingType? = nil,
        navigationTitle: String = "Produits"
    ) {
        _viewModel = StateObject(wrappedValue: ProductsViewModel(api: api, initialSellingType: initialSellingType))
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
                VStack(alignment: .leading, spacing: 6) {
                    HStack {
                        Button {
                            showFilterSheet = true
                        } label: {
                            let count = (viewModel.selectedCategory == nil ? 0 : 1) + (viewModel.selectedSellingType == nil ? 0 : 1)
                            Label(count > 0 ? "Filtres (\(count))" : "Filtres", systemImage: "line.3.horizontal.decrease.circle")
                        }
                        Spacer()
                        Button {
                            showSortSheet = true
                        } label: {
                            let sortLabel: String = {
                                switch viewModel.sort {
                                case .relevance: return "Pertinence"
                                case .priceLowHigh: return "Prix croissant"
                                case .priceHighLow: return "Prix décroissant"
                                case .newest: return "Nouveautés"
                                }
                            }()
                            Label("Trier (\(sortLabel))", systemImage: "arrow.up.arrow.down")
                        }
                    }
                    if viewModel.selectedCategory != nil || viewModel.selectedSellingType != nil {
                        Text(summaryText)
                            .font(.footnote)
                            .foregroundStyle(.secondary)
                    }
                    if viewModel.selectedCategory != nil || viewModel.selectedSellingType != nil {
                        ScrollView(.horizontal, showsIndicators: false) {
                            HStack(spacing: 8) {
                                if let category = viewModel.selectedCategory {
                                    HStack(spacing: 6) {
                                        Text(category.name)
                                        Button {
                                            viewModel.selectedCategory = nil
                                            Task { await viewModel.load(force: true) }
                                        } label: {
                                            Image(systemName: "xmark.circle.fill")
                                        }
                                        .buttonStyle(.plain)
                                        .accessibilityLabel("Retirer le filtre \(category.name)")
                                    }
                                    .padding(.horizontal, 10)
                                    .padding(.vertical, 6)
                                    .background(Color.blue.opacity(0.1))
                                    .foregroundColor(.blue)
                                    .clipShape(Capsule())
                                }
                                if let t = viewModel.selectedSellingType {
                                    HStack(spacing: 6) {
                                        Text(t == .rental ? "Location" : "Vente")
                                        Button {
                                            viewModel.selectedSellingType = nil
                                            Task { await viewModel.load(force: true) }
                                        } label: {
                                            Image(systemName: "xmark.circle.fill")
                                        }
                                        .buttonStyle(.plain)
                                        .accessibilityLabel("Retirer le filtre type")
                                    }
                                    .padding(.horizontal, 10)
                                    .padding(.vertical, 6)
                                    .background(Color.blue.opacity(0.1))
                                    .foregroundColor(.blue)
                                    .clipShape(Capsule())
                                }
                            }
                        }
                    }
                }
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
                                NavigationLink {
                                    ProductDetailView(product: product, imageURL: container.api.assetURL(for: product.imageUrl), cart: cart, selectedTab: $selectedTab)
                                        .environmentObject(container)
                                } label: {
                                    ZStack(alignment: .topTrailing) {
                                        VStack(alignment: .leading, spacing: 6) {
                                            ZStack(alignment: .topLeading) {
                                                AsyncImage(url: container.api.assetURL(for: product.imageUrl)) { phase in
                                                    switch phase {
                                                    case .success(let image):
                                                        image.resizable().scaledToFill().frame(height: 140).clipped().cornerRadius(10)
                                                    case .failure:
                                                        RoundedRectangle(cornerRadius: 10).fill(.gray.opacity(0.1)).frame(height: 140).overlay(Image(systemName: "photo").foregroundStyle(.secondary))
                                                    default:
                                                        RoundedRectangle(cornerRadius: 10).fill(.gray.opacity(0.08)).frame(height: 140).overlay(ProgressView())
                                                    }
                                                }
                                                .accessibilityHidden(true)
                                                if product.sellingType != .unknown {
                                                    Text(product.sellingType == .rental ? "Location" : "Vente")
                                                        .font(.caption2)
                                                        .padding(.horizontal, 6)
                                                        .padding(.vertical, 2)
                                                        .background(Color.blue.opacity(0.85))
                                                        .foregroundColor(.white)
                                                        .clipShape(Capsule())
                                                        .padding(6)
                                                }
                                            }
                                            Text(product.name).font(.subheadline).fontWeight(.semibold).lineLimit(2)
                                            HStack(spacing: 6) {
                                                Text(PriceFormatter.format(cents: product.effectivePriceCents)).fontWeight(.bold)
                                                if product.sellingType == .rental { Text("/mois").foregroundStyle(.secondary) }
                                                if product.effectivePriceCents < product.priceCents { Text(PriceFormatter.format(cents: product.priceCents)).strikethrough().foregroundStyle(.secondary) }
                                            }
                                            .font(.footnote)
                                        }
                                        Button {
                                            Task {
                                                do {
                                                    try await container.api.addFavorite(productId: product.id)
                                                    await cart.refresh()
                                                } catch {
                                                    // Ignore favorite errors to keep UI responsive
                                                }
                                            }
                                        } label: {
                                            Image(systemName: "heart")
                                                .padding(8)
                                        }
                                        .buttonStyle(.borderless)
                                        .accessibilityLabel("Ajouter aux favoris")
                                    }
                                }
                                .accessibilityElement(children: .ignore)
                                .accessibilityLabel("\(product.name), \(PriceFormatter.format(cents: product.effectivePriceCents))\(product.sellingType == .rental ? " par mois" : "")")
                                .accessibilityHint("Afficher le détail du produit")
                            }
                        }
                        .listRowInsets(EdgeInsets())
                    } else {
                        ForEach(viewModel.products) { product in
                            NavigationLink {
                                ProductDetailView(product: product, imageURL: container.api.assetURL(for: product.imageUrl), cart: cart, selectedTab: $selectedTab)
                                    .environmentObject(container)
                            } label: {
                                HStack(alignment: .top, spacing: 12) {
                                    AsyncImage(url: container.api.assetURL(for: product.imageUrl)) { phase in
                                        switch phase {
                                        case .success(let image):
                                            image.resizable().scaledToFill().frame(width: 64, height: 64).clipped().cornerRadius(8)
                                        case .failure:
                                            ZStack { RoundedRectangle(cornerRadius: 8).fill(.gray.opacity(0.1)); Image(systemName: "photo").foregroundStyle(.secondary) }.frame(width: 64, height: 64)
                                        default:
                                            ZStack { RoundedRectangle(cornerRadius: 8).fill(.gray.opacity(0.1)); ProgressView() }.frame(width: 64, height: 64)
                                        }
                                    }
                                    .accessibilityHidden(true)
                                    VStack(alignment: .leading, spacing: 4) {
                                        Text(product.name).fontWeight(.semibold)
                                        if product.sellingType != .unknown {
                                            Text(product.sellingType == .rental ? "Location" : "Vente")
                                                .font(.caption2)
                                                .padding(.horizontal, 6)
                                                .padding(.vertical, 2)
                                                .background(Color.blue.opacity(0.1))
                                                .foregroundColor(.blue)
                                                .clipShape(Capsule())
                                        }
                                        Text(product.shortDescription).lineLimit(2).foregroundStyle(.secondary)
                                        HStack(spacing: 6) {
                                            Text(PriceFormatter.format(cents: product.effectivePriceCents)).fontWeight(.bold)
                                            if product.sellingType == .rental { Text("/mois").foregroundStyle(.secondary) }
                                            if product.effectivePriceCents < product.priceCents { Text(PriceFormatter.format(cents: product.priceCents)).strikethrough().foregroundStyle(.secondary) }
                                        }
                                        .font(.subheadline)
                                    }
                                    Spacer()
                                    Button {
                                        Task {
                                            do {
                                                try await container.api.addFavorite(productId: product.id)
                                                await cart.refresh()
                                            } catch {
                                                // Ignore favorite errors to keep UI responsive
                                            }
                                        }
                                    } label: {
                                        Image(systemName: "heart")
                                    }
                                    .buttonStyle(.borderless)
                                    .accessibilityLabel("Ajouter aux favoris")
                                }
                                .padding(.vertical, 6)
                                .accessibilityElement(children: .ignore)
                                .accessibilityLabel("\(product.name), \(PriceFormatter.format(cents: product.effectivePriceCents))\(product.sellingType == .rental ? " par mois" : "")")
                                .accessibilityHint("Afficher le détail du produit")
                            }
                        }
                    }
                }
            }
        }
        .sheet(isPresented: $showSortSheet) {
            NavigationStack {
                List {
                    Button(action: { viewModel.updateSort(.relevance); showSortSheet = false }) { HStack { Text("Pertinence"); if viewModel.sort == .relevance { Spacer(); Image(systemName: "checkmark") } } }
                    Button(action: { viewModel.updateSort(.priceLowHigh); showSortSheet = false }) { HStack { Text("Prix croissant"); if viewModel.sort == .priceLowHigh { Spacer(); Image(systemName: "checkmark") } } }
                    Button(action: { viewModel.updateSort(.priceHighLow); showSortSheet = false }) { HStack { Text("Prix décroissant"); if viewModel.sort == .priceHighLow { Spacer(); Image(systemName: "checkmark") } } }
                    Button(action: { viewModel.updateSort(.newest); showSortSheet = false }) { HStack { Text("Nouveautés"); if viewModel.sort == .newest { Spacer(); Image(systemName: "checkmark") } } }
                }
                .navigationTitle("Trier")
                .toolbar { ToolbarItem(placement: .cancellationAction) { Button("Fermer") { showSortSheet = false } } }
            }
        }
        .sheet(isPresented: $showFilterSheet) {
            NavigationStack {
                Form {
                    Section("Catégories") {
                        if viewModel.categories.isEmpty {
                            Text("Chargement...").foregroundStyle(.secondary)
                        } else {
                            Picker("Catégorie", selection: $draftSelectedCategoryID) {
                                Text("Toutes").tag(Int?.none)
                                ForEach(viewModel.categories) { cat in
                                    Text(cat.name).tag(Optional(cat.id))
                                }
                            }
                            .pickerStyle(.inline)
                        }
                    }
                    Section("Type") {
                        Picker("Type", selection: $draftSelectedType) {
                            Text("Tous").tag(SellingType?.none)
                            Text("Vente").tag(Optional(SellingType.sale))
                            Text("Location").tag(Optional(SellingType.rental))
                        }
                        .pickerStyle(.segmented)
                    }
                }
                .onAppear {
                    guard !didInitDraftFilters else { return }
                    draftSelectedCategoryID = viewModel.selectedCategory?.id
                    draftSelectedType = viewModel.selectedSellingType
                    didInitDraftFilters = true
                }
                .onDisappear {
                    didInitDraftFilters = false
                }
                .navigationTitle("Filtres")
                .interactiveDismissDisabled(true)
                .toolbar {
                    ToolbarItem(placement: .cancellationAction) { Button("Annuler") { showFilterSheet = false } }
                    ToolbarItem(placement: .bottomBar) {
                        Button("Réinitialiser") {
                            draftSelectedCategoryID = nil
                            draftSelectedType = nil
                        }
                    }
                    ToolbarItem(placement: .confirmationAction) {
                        Button("Appliquer") {
                            viewModel.selectedCategory = viewModel.categories.first(where: { $0.id == draftSelectedCategoryID })
                            viewModel.selectedSellingType = draftSelectedType
                            Task { await viewModel.load(force: true) }
                            showFilterSheet = false
                        }
                        .disabled(draftSelectedCategoryID == viewModel.selectedCategory?.id && draftSelectedType == viewModel.selectedSellingType)
                    }
                }
            }
        }
        .navigationTitle(navigationTitle)
        .searchable(text: $viewModel.search, placement: .navigationBarDrawer(displayMode: .always), prompt: "Rechercher")
        .onSubmit(of: .search) {
            Task { await viewModel.load(force: true) }
        }
        .onChangeCompat(viewModel.search) { _ in }
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

private struct ShimmerView: View {
    @State private var phase: CGFloat = 0
    var body: some View {
        RoundedRectangle(cornerRadius: 8)
            .fill(
                LinearGradient(gradient: Gradient(colors: [Color.gray.opacity(0.15), Color.gray.opacity(0.05), Color.gray.opacity(0.15)]), startPoint: .leading, endPoint: .trailing)
            )
            .mask(
                Rectangle()
                    .fill(
                        LinearGradient(gradient: Gradient(colors: [Color.black.opacity(0.4), Color.black, Color.black.opacity(0.4)]), startPoint: .leading, endPoint: .trailing)
                    )
                    .offset(x: phase)
            )
            .onAppear { withAnimation(.linear(duration: 1.2).repeatForever(autoreverses: false)) { phase = 180 } }
    }
}

private struct ShimmerRow: View {
    var body: some View {
        HStack(spacing: 12) {
            ShimmerView().frame(width: 64, height: 64)
            VStack(alignment: .leading, spacing: 8) {
                ShimmerView().frame(height: 14)
                ShimmerView().frame(height: 10)
                ShimmerView().frame(height: 10)
            }
        }
    }
}

private struct ShimmerTile: View {
    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            ShimmerView().frame(height: 140).cornerRadius(10)
            ShimmerView().frame(height: 12)
            ShimmerView().frame(height: 10)
        }
    }
}
