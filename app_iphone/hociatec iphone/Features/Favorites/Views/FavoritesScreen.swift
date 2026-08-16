import SwiftUI

@MainActor
struct FavoritesScreen: View {
    @EnvironmentObject private var container: AppContainer
    @StateObject private var viewModel: FavoritesViewModel

    init(service: FavoritesServing) {
        _viewModel = StateObject(wrappedValue: FavoritesViewModel(service: service))
    }

    var body: some View {
        List {
            Section {
                Text("Catégories favoris")
                    .font(.headline)
                    .accessibilityAddTraits(.isHeader)

                ScrollView(.horizontal, showsIndicators: false) {
                    HStack(spacing: 10) {
                        categoryButton(title: "Tous", isSelected: viewModel.selectedCategory == nil) {
                            Task { await viewModel.setCategory(nil) }
                        }
                        ForEach(FavoriteCategory.allCases) { category in
                            categoryButton(title: category.label, isSelected: viewModel.selectedCategory == category) {
                                Task { await viewModel.setCategory(category) }
                            }
                        }
                    }
                    .padding(.vertical, 4)
                }
                .accessibilityLabel("Catégories favoris")
                .listRowInsets(EdgeInsets(top: 8, leading: 16, bottom: 8, trailing: 16))
            }
            if viewModel.isLoading && viewModel.items.isEmpty {
                Section { ProgressView("Chargement...") }
            } else if viewModel.items.isEmpty {
                Section { Text("Aucun favori enregistré.").foregroundStyle(.secondary) }
            } else {
                Section {
                    ForEach(viewModel.items) { favorite in
                        favoriteRow(favorite)
                    }

                    if viewModel.isLoading {
                        InlineLoadingStatus(message: "Actualisation des favoris…")
                    }
                }
            }
        }
        .navigationTitle("Mes favoris")
        .task { await viewModel.load() }
        .refreshable { await viewModel.load(force: true) }
        .feedbackDialog(error: $viewModel.error)
    }

    @ViewBuilder
    private func favoriteRow(_ favorite: FavoriteEntry) -> some View {
        switch favorite.category {
        case .product:
            if let product = favorite.product {
                ProductCatalogCard(
                    product: product,
                    imageURL: container.services.assets.assetURL(for: product.imageUrl),
                    cart: container.cart,
                    selectedTab: .constant(0),
                    isCompact: false,
                    onFavoriteRemoved: {
                        viewModel.removeLocally(category: .product, targetId: product.id)
                    }
                )
                .environmentObject(container)
            }
        case .service:
            if let service = favorite.service {
                VStack(alignment: .leading, spacing: 8) {
                    Text(service.title)
                        .font(.headline)

                    if let description = service.description, !description.isEmpty {
                        Text(description)
                            .foregroundStyle(.secondary)
                    }

                    Text("Ajouté le \(favorite.addedAt.formatted(date: .abbreviated, time: .omitted))")
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                    NavigationLink {
                        ServiceDetailView(api: container.services.serviceCatalog, serviceID: service.id)
                    } label: {
                        Label("Voir le détail", systemImage: "arrow.right.circle")
                            .font(.footnote.weight(.semibold))
                    }
                    .buttonStyle(.borderless)
                }
                .padding(.vertical, 4)
            }
        case .news:
            if let article = favorite.article {
                VStack(alignment: .leading, spacing: 8) {
                    Text(article.title)
                        .font(.headline)

                    Text(article.excerpt)
                        .foregroundStyle(.secondary)

                    Text("Ajouté le \(favorite.addedAt.formatted(date: .abbreviated, time: .omitted))")
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                    NavigationLink {
                        NewsDetailView(api: container.services.news, slug: article.slug)
                    } label: {
                        Label("Lire l’actualité", systemImage: "arrow.right.circle")
                            .font(.footnote.weight(.semibold))
                    }
                    .buttonStyle(.borderless)
                }
                .padding(.vertical, 4)
            }
        case .podcast:
            Text("Les podcasts favoris seront disponibles prochainement.")
                .foregroundStyle(.secondary)
        }
    }

    private func categoryButton(title: String, isSelected: Bool, action: @escaping () -> Void) -> some View {
        Button(action: action) {
            Text(title)
                .font(.footnote.weight(.semibold))
                .padding(.horizontal, 12)
                .padding(.vertical, 8)
                .background(isSelected ? Color.accentColor : Color(.systemBackground))
                .foregroundStyle(isSelected ? .white : .primary)
                .clipShape(Capsule())
                .overlay(
                    Capsule()
                        .stroke(isSelected ? Color.accentColor : Color(.separator), lineWidth: 1)
                )
        }
        .buttonStyle(.plain)
        .accessibilityLabel(title)
        .accessibilityAddTraits(isSelected ? .isSelected : [])
    }
}
