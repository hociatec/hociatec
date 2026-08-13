import SwiftUI
import Foundation

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
                                ProductCatalogCard(
                                    product: product,
                                    imageURL: container.api.assetURL(for: product.imageUrl),
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
                                imageURL: container.api.assetURL(for: product.imageUrl),
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

private struct ProductCatalogCard: View {
    let product: Product
    let imageURL: URL?
    let cart: CartViewModel
    @Binding var selectedTab: Int
    let isCompact: Bool
    @EnvironmentObject private var container: AppContainer

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            NavigationLink {
                ProductDetailView(product: product, imageURL: imageURL, cart: cart, selectedTab: $selectedTab)
                    .environmentObject(container)
            } label: {
                VStack(alignment: .leading, spacing: 12) {
                    productImage
                    VStack(alignment: .leading, spacing: 8) {
                        Text(product.name)
                            .font(isCompact ? .subheadline.weight(.semibold) : .headline)
                            .lineLimit(2)
                            .multilineTextAlignment(.leading)

                        VStack(alignment: .leading, spacing: 4) {
                            ProductFactLine(label: "Référence", value: product.sku)
                            ProductFactLine(label: "Type", value: productSellingContext(product))
                            if let configuration = productConfiguration(product) {
                                ProductFactLine(label: "Configuration", value: configuration)
                            }
                        }
                        .font(.footnote)
                        .foregroundStyle(.secondary)

                        if !product.shortDescription.isEmpty {
                            Text(product.shortDescription)
                                .font(.footnote)
                                .foregroundStyle(.secondary)
                                .lineLimit(isCompact ? 3 : 4)
                        }

                        Text(productPriceLabel(product))
                            .font(.title3.weight(.bold))
                            .foregroundStyle(.primary)
                    }
                }
            }
            .buttonStyle(.plain)
            .accessibilityHint("Afficher le détail du produit")

            VStack(alignment: .leading, spacing: 8) {
                Button {
                    Task { await cart.add(product: product) }
                } label: {
                    Text("Ajouter au panier")
                        .fontWeight(.semibold)
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.borderedProminent)

                HStack(spacing: 12) {
                    Link(destination: facebookShareURL(for: product)) {
                        Label("Partager sur Facebook", systemImage: "square.and.arrow.up")
                            .font(.footnote)
                    }

                    Link(destination: emailShareURL(for: product)) {
                        Label("Partager par e-mail", systemImage: "envelope")
                            .font(.footnote)
                    }
                }
                .foregroundStyle(.blue)
            }
        }
        .padding(.vertical, 6)
    }

    @ViewBuilder
    private var productImage: some View {
        AsyncImage(url: imageURL) { phase in
            switch phase {
            case .success(let image):
                image
                    .resizable()
                    .scaledToFill()
                    .frame(height: isCompact ? 140 : 180)
                    .frame(maxWidth: .infinity)
                    .clipped()
                    .cornerRadius(12)
            case .failure:
                RoundedRectangle(cornerRadius: 12)
                    .fill(.gray.opacity(0.1))
                    .frame(height: isCompact ? 140 : 180)
                    .overlay(Image(systemName: "photo").foregroundStyle(.secondary))
            default:
                RoundedRectangle(cornerRadius: 12)
                    .fill(.gray.opacity(0.08))
                    .frame(height: isCompact ? 140 : 180)
                    .overlay(ProgressView())
            }
        }
        .accessibilityHidden(true)
    }
}

private struct ProductFactLine: View {
    let label: String
    let value: String

    var body: some View {
        Text("\(label): \(value)")
            .multilineTextAlignment(.leading)
    }
}

private func productSellingContext(_ product: Product) -> String {
    let sellingTypeLabel = product.sellingTypeLabel ?? {
        switch product.sellingType {
        case .rental: return "Location"
        case .sale: return "Vente"
        case .unknown: return "Produit"
        }
    }()
    return "\(product.category.name) (\(sellingTypeLabel))"
}

private func productConfiguration(_ product: Product) -> String? {
    let compactSpecs = [
        nonEmptyValue(product.brand),
        nonEmptyValue(product.memoryRam),
        (product.variantsCount ?? 1) > 1 ? nil : nonEmptyValue(product.storageCapacity),
        (product.variantsCount ?? 1) > 1 ? nil : nonEmptyValue(product.color)
    ].compactMap { $0 }

    guard !compactSpecs.isEmpty else { return nil }
    return compactSpecs.joined(separator: " • ")
}

private func productPriceLabel(_ product: Product) -> String {
    let unitSuffix = nonEmptyValue(product.priceUnitLabel) ?? (product.sellingType == .rental ? "/mois" : "")
    return PriceFormatter.format(cents: product.effectivePriceCents) + unitSuffix
}

private func facebookShareURL(for product: Product) -> URL {
    let target = "https://hociatec.fr/catalogue/produits/\(product.slug)"
    let encodedTarget = target.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? target
    return URL(string: "https://www.facebook.com/sharer/sharer.php?u=\(encodedTarget)")!
}

private func emailShareURL(for product: Product) -> URL {
    let subject = "Découvrir \(product.name)"
    let body = "\(product.name)\n\(productPriceLabel(product))\nhttps://hociatec.fr/catalogue/produits/\(product.slug)"
    let encodedSubject = subject.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? subject
    let encodedBody = body.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? body
    return URL(string: "mailto:?subject=\(encodedSubject)&body=\(encodedBody)")!
}

private func nonEmptyValue(_ value: String?) -> String? {
    guard let trimmed = value?.trimmingCharacters(in: .whitespacesAndNewlines), !trimmed.isEmpty else {
        return nil
    }
    return trimmed
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
