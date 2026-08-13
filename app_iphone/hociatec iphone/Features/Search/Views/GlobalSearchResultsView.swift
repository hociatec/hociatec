import SwiftUI

struct GlobalSearchResultsView: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var cart: CartViewModel

    @ObservedObject var viewModel: GlobalSearchViewModel

    var body: some View {
        Group {
            if viewModel.shouldShow(.products) {
                productsSection
            }

            if viewModel.shouldShow(.services) {
                servicesSection
            }

            if viewModel.shouldShow(.trainings) {
                trainingsSection
            }

            if viewModel.shouldShow(.news) {
                newsSection
            }
        }
    }

    private var productsSection: some View {
        Section {
            if viewModel.products.isEmpty {
                Text("Aucun résultat produit.")
                    .foregroundStyle(.secondary)
            } else {
                ForEach(viewModel.products) { product in
                    NavigationLink {
                        ProductDetailView(
                            viewModel: container.makeProductDetailViewModel(product: product),
                            imageURL: container.services.assets.assetURL(for: product.imageUrl),
                            cart: cart,
                            selectedTab: .constant(0)
                        )
                        .environmentObject(container)
                    } label: {
                        GlobalSearchProductRow(product: product)
                    }
                }
            }
        } header: {
            GlobalSearchResultHeader(title: "Produits", total: viewModel.productTotal, query: viewModel.query) {
                ProductsListView(
                    viewModel: container.makeProductsViewModel(),
                    selectedTab: .constant(1),
                    filtersBadge: .constant(nil),
                    navigationTitle: "Produits",
                    initialSearch: viewModel.query
                )
            }
        }
    }

    private var servicesSection: some View {
        Section {
            if viewModel.services.isEmpty {
                Text("Aucun résultat service.")
                    .foregroundStyle(.secondary)
            } else {
                ForEach(viewModel.services) { service in
                    NavigationLink {
                        ServiceDetailView(api: container.services.serviceCatalog, serviceID: service.id)
                    } label: {
                        GlobalSearchServiceRow(service: service)
                    }
                }
            }
        } header: {
            GlobalSearchResultHeader(title: "Services", total: viewModel.serviceTotal, query: viewModel.query) {
                ServicesCatalogView(
                    api: container.services.serviceCatalog,
                    initialSearch: viewModel.query
                )
            }
        }
    }

    private var trainingsSection: some View {
        Section {
            if viewModel.trainings.isEmpty {
                Text("Aucun résultat formation.")
                    .foregroundStyle(.secondary)
            } else {
                ForEach(viewModel.trainings) { training in
                    NavigationLink {
                        TrainingDetailView(api: container.services.training, slug: training.slug)
                    } label: {
                        GlobalSearchTrainingRow(training: training)
                    }
                }
            }
        } header: {
            GlobalSearchResultHeader(title: "Formations", total: viewModel.trainingTotal, query: viewModel.query) {
                TrainingsCatalogView(
                    api: container.services.training,
                    initialSearch: viewModel.query
                )
            }
        }
    }

    private var newsSection: some View {
        Section {
            if viewModel.news.isEmpty {
                Text("Aucun résultat actualité.")
                    .foregroundStyle(.secondary)
            } else {
                ForEach(viewModel.news) { article in
                    NavigationLink {
                        NewsDetailView(api: container.services.news, slug: article.slug)
                    } label: {
                        GlobalSearchNewsRow(article: article)
                    }
                }
            }
        } header: {
            GlobalSearchResultHeader(title: "Actualités", total: viewModel.newsTotal, query: viewModel.query) {
                NewsListView(
                    api: container.services.news,
                    initialSearch: viewModel.query
                )
            }
        }
    }
}
