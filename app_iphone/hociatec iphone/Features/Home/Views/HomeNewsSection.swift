import SwiftUI

struct HomeNewsSection: View {
    @EnvironmentObject private var container: AppContainer
    @ObservedObject var home: HomeViewModel

    var body: some View {
        Section("Actualités") {
            if home.isLoading && home.news.isEmpty {
                ProgressView("Chargement...")
                    .frame(maxWidth: .infinity, alignment: .center)
            } else if home.news.isEmpty {
                Text("Aucune actualité disponible pour le moment.")
                    .foregroundStyle(.secondary)
            } else {
                ForEach(home.news) { article in
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
                        .accessibilityHidden(true)

                        HStack(alignment: .top, spacing: 12) {
                            NavigationLink {
                                NewsDetailView(api: container.services.news, slug: article.slug)
                            } label: {
                                Text(article.title)
                                    .fontWeight(.semibold)
                                    .multilineTextAlignment(.leading)
                                    .frame(maxWidth: .infinity, alignment: .leading)
                            }
                            .buttonStyle(.plain)
                            .accessibilityAddTraits(.isHeader)
                            .accessibilityHint("Ouvrir l’actualité")

                            FavoriteToggleButton(category: .news, targetId: article.id)
                        }

                        Text(article.excerpt)
                            .lineLimit(3)
                            .foregroundStyle(.secondary)
                    }
                    .padding(.vertical, 4)
                    .accessibilityElement(children: .contain)
                }

                if home.isLoading {
                    InlineLoadingStatus(message: "Actualisation des actualités…")
                }
            }
        }
    }
}
