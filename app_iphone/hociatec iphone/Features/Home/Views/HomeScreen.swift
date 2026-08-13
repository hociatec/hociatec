import SwiftUI

struct HomeScreen: View {
    @StateObject private var home: HomeViewModel
    @EnvironmentObject private var container: AppContainer
    @Binding private var selectedTab: Int

    init(services: AppServices, selectedTab: Binding<Int>) {
        _home = StateObject(wrappedValue: HomeViewModel(
            productsService: services.products,
            serviceCatalogService: services.serviceCatalog,
            newsService: services.news,
            appointmentsService: services.appointments
        ))
        _selectedTab = selectedTab
    }

    var body: some View {
        List {
            Section {
                VStack(alignment: .leading, spacing: 10) {
                    Text("Hociatec accompagne vos besoins en materiel informatique, services numeriques, formation et suivi client.")
                        .font(.body)
                    Text("Retrouvez nos nouveautes, nos offres et un parcours mobile aligne avec nos services.")
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                    NavigationLink {
                        NewsListView(api: container.services.news)
                    } label: {
                        Label("Actualités", systemImage: "newspaper")
                            .fontWeight(.semibold)
                            .frame(maxWidth: .infinity, alignment: .center)
                    }
                    .buttonStyle(.borderedProminent)
                }
            }

            Section("Services") {
                if home.isLoading && home.services.isEmpty {
                    ProgressView("Chargement...")
                        .frame(maxWidth: .infinity, alignment: .center)
                } else if let error = home.error, home.services.isEmpty {
                    Text(error)
                        .foregroundStyle(.red)
                } else if home.services.isEmpty {
                    Text("Aucun service mis en avant pour le moment.")
                        .foregroundStyle(.secondary)
                } else {
                    ForEach(home.services.prefix(6)) { service in
                        NavigationLink {
                            ServiceDetailView(api: container.services.serviceCatalog, serviceID: service.id)
                        } label: {
                            VStack(alignment: .leading, spacing: 6) {
                                Text(service.title)
                                    .fontWeight(.semibold)
                                if let description = service.description, !description.isEmpty {
                                    Text(description)
                                        .lineLimit(2)
                                        .foregroundStyle(.secondary)
                                }
                                HStack {
                                    Text(PriceFormatter.format(cents: service.priceCents))
                                        .font(.footnote)
                                        .fontWeight(.semibold)
                                    Spacer()
                                    if let durationLabel = service.durationLabel, !durationLabel.isEmpty {
                                        Text(durationLabel)
                                            .font(.footnote)
                                            .foregroundStyle(.secondary)
                                    }
                                }
                            }
                            .padding(.vertical, 4)
                        }
                    }
                }
            }

            Section("Produits en vedette") {
                if home.isLoading && home.featured.isEmpty {
                    ProgressView("Chargement...")
                        .frame(maxWidth: .infinity, alignment: .center)
                } else if let error = home.error {
                    Text(error)
                        .foregroundStyle(.red)
                } else if home.featured.isEmpty {
                    Text("Aucun produit disponible pour le moment.")
                        .foregroundStyle(.secondary)
                } else {
                    ForEach(home.featured.prefix(5)) { product in
                        NavigationLink {
                            ProductDetailView(
                                product: product,
                                imageURL: container.services.assets.assetURL(for: product.imageUrl),
                                cart: container.cart,
                                selectedTab: .constant(0)
                            )
                            .environmentObject(container)
                        } label: {
                            VStack(alignment: .leading, spacing: 4) {
                                Text(product.name)
                                    .fontWeight(.semibold)
                                Text(product.shortDescription)
                                    .lineLimit(2)
                                    .foregroundStyle(.secondary)
                            }
                            .accessibilityElement(children: .ignore)
                            .accessibilityLabel("Produit: \(product.name)")
                            .accessibilityHint("Afficher le détail du produit")
                        }
                    }
                }
            }

            Section("Actualités") {
                if home.isLoading && home.news.isEmpty {
                    ProgressView("Chargement...")
                        .frame(maxWidth: .infinity, alignment: .center)
                } else if let error = home.error, home.news.isEmpty {
                    Text(error)
                        .foregroundStyle(.red)
                } else if home.news.isEmpty {
                    Text("Aucune actualité disponible pour le moment.")
                        .foregroundStyle(.secondary)
                } else {
                    ForEach(home.news) { article in
                        NavigationLink {
                            NewsDetailView(api: container.services.news, slug: article.slug)
                        } label: {
                            VStack(alignment: .leading, spacing: 6) {
                                HStack {
                                    if let publishedAt = article.publishedAt {
                                        Text(newsDateFormatter.string(from: publishedAt))
                                            .font(.caption)
                                            .foregroundStyle(.secondary)
                                    }
                                    if let category = article.category, !category.isEmpty {
                                        Spacer()
                                        Text(category)
                                            .font(.caption)
                                            .foregroundStyle(.secondary)
                                    }
                                }
                                Text(article.title)
                                    .fontWeight(.semibold)
                                Text(article.excerpt)
                                    .lineLimit(3)
                                    .foregroundStyle(.secondary)
                            }
                            .padding(.vertical, 4)
                        }
                    }
                }
            }
        }
        .navigationTitle("Accueil")
        .task { await home.load() }
    }
}
