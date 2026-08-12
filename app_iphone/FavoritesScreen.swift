import SwiftUI

struct FavoritesScreen: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var cart: CartViewModel
    @StateObject private var viewModel: FavoritesViewModel
    @State private var selectedTab: Int = 1

    init(api: APIClient) {
        _viewModel = StateObject(wrappedValue: FavoritesViewModel(api: api))
    }

    var body: some View {
        List {
            if let error = viewModel.error { Section { Text(error).foregroundStyle(.red) } }

            Section {
                if viewModel.isLoading {
                    HStack { Spacer(); ProgressView(); Spacer() }
                } else if viewModel.items.isEmpty {
                    Text("Aucun favori pour le moment.").foregroundStyle(.secondary)
                } else {
                    ForEach(viewModel.items) { product in
                        NavigationLink {
                            ProductDetailView(product: product, imageURL: container.api.assetURL(for: product.imageUrl), cart: cart, selectedTab: $selectedTab)
                                .environmentObject(container)
                        } label: {
                            HStack(spacing: 12) {
                                AsyncImage(url: container.api.assetURL(for: product.imageUrl)) { phase in
                                    switch phase {
                                    case .success(let image): image.resizable().scaledToFill().frame(width: 64, height: 64).clipped().cornerRadius(8)
                                    case .failure: ZStack { RoundedRectangle(cornerRadius: 8).fill(.gray.opacity(0.1)); Image(systemName: "photo").foregroundStyle(.secondary) }.frame(width: 64, height: 64)
                                    default: ZStack { RoundedRectangle(cornerRadius: 8).fill(.gray.opacity(0.1)); ProgressView() }.frame(width: 64, height: 64)
                                    }
                                }
                                .accessibilityHidden(true)
                                VStack(alignment: .leading, spacing: 4) {
                                    Text(product.name).fontWeight(.semibold)
                                    HStack(spacing: 6) {
                                        Text(PriceFormatter.format(cents: product.effectivePriceCents)).fontWeight(.bold)
                                        if product.sellingType == .rental { Text("/mois").foregroundStyle(.secondary) }
                                        if product.effectivePriceCents < product.priceCents { Text(PriceFormatter.format(cents: product.priceCents)).strikethrough().foregroundStyle(.secondary) }
                                    }
                                    .font(.subheadline)
                                }
                                Spacer()
                                Button(role: .destructive) {
                                    Task { await viewModel.remove(product: product) }
                                } label: {
                                    Image(systemName: "heart.slash")
                                }
                                .buttonStyle(.bordered)
                                .accessibilityLabel("Retirer des favoris")
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
        .navigationTitle("Mes favoris")
        .task { await viewModel.load() }
        .refreshable { await viewModel.load() }
    }
}
