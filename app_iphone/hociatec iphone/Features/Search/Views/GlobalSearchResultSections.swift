import SwiftUI

struct GlobalSearchProductsSection: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var cart: CartViewModel

    @ObservedObject var viewModel: GlobalSearchViewModel

    var body: some View {
        Section {
            if viewModel.products.isEmpty {
                GlobalSearchEmptyRow(message: "Aucun résultat produit.")
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
}

struct GlobalSearchServicesSection: View {
    @EnvironmentObject private var container: AppContainer

    @ObservedObject var viewModel: GlobalSearchViewModel

    var body: some View {
        Section {
            if viewModel.services.isEmpty {
                GlobalSearchEmptyRow(message: "Aucun résultat service.")
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
}

struct GlobalSearchTrainingsSection: View {
    @EnvironmentObject private var container: AppContainer

    @ObservedObject var viewModel: GlobalSearchViewModel

    var body: some View {
        Section {
            if viewModel.trainings.isEmpty {
                GlobalSearchEmptyRow(message: "Aucun résultat formation.")
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
}

struct GlobalSearchNewsSection: View {
    @EnvironmentObject private var container: AppContainer

    @ObservedObject var viewModel: GlobalSearchViewModel

    var body: some View {
        Section {
            if viewModel.news.isEmpty {
                GlobalSearchEmptyRow(message: "Aucun résultat actualité.")
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

private struct GlobalSearchEmptyRow: View {
    let message: String

    var body: some View {
        Text(message)
            .foregroundStyle(.secondary)
    }
}
