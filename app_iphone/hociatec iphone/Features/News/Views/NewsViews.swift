import Foundation
import SwiftUI
import Combine

let newsDateFormatter: DateFormatter = {
    let formatter = DateFormatter()
    formatter.locale = Locale(identifier: "fr_FR")
    formatter.dateStyle = .medium
    formatter.timeStyle = .none
    return formatter
}()

struct NewsListView: View {
    let service: NewsServing
    @StateObject private var viewModel: NewsListViewModel

    init(api: NewsServing) {
        self.service = api
        _viewModel = StateObject(wrappedValue: NewsListViewModel(service: api))
    }

    var body: some View {
        List {
            Section {
                TextField("Rechercher une actualité", text: $viewModel.searchText)
                Button("Rechercher") {
                    viewModel.applySearch()
                    Task { await viewModel.load() }
                }
            }

            Section("Actualités") {
                if viewModel.isLoading && viewModel.articles.isEmpty {
                    ProgressView("Chargement des actualités...")
                } else if let error = viewModel.error {
                    Text(error).foregroundStyle(.red)
                } else if viewModel.articles.isEmpty {
                    Text("Aucune actualité disponible pour le moment.")
                        .foregroundStyle(.secondary)
                } else {
                    ForEach(viewModel.articles) { article in
                        NavigationLink {
                            NewsDetailView(api: service, slug: article.slug)
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

            if viewModel.totalPages > 1 {
                Section {
                    HStack {
                        Button("Précédent") {
                            viewModel.previousPage()
                            Task { await viewModel.load() }
                        }
                        .disabled(viewModel.page <= 1 || viewModel.isLoading)
                        Spacer()
                        Text("\(viewModel.page)/\(viewModel.totalPages)")
                            .font(.footnote)
                            .foregroundStyle(.secondary)
                        Spacer()
                        Button("Suivant") {
                            viewModel.nextPage()
                            Task { await viewModel.load() }
                        }
                        .disabled(viewModel.page >= viewModel.totalPages || viewModel.isLoading)
                    }
                }
            }
        }
        .navigationTitle("Actualités")
        .task { await viewModel.load() }
    }
}

struct NewsDetailView: View {
    @StateObject private var viewModel: NewsDetailViewModel
    @EnvironmentObject private var account: AccountViewModel

    init(api: NewsServing, slug: String) {
        _viewModel = StateObject(wrappedValue: NewsDetailViewModel(service: api, slug: slug))
    }

    var body: some View {
        List {
            if viewModel.isLoading && viewModel.article == nil {
                Section {
                    ProgressView("Chargement de l’actualité...")
                        .frame(maxWidth: .infinity, alignment: .center)
                }
            } else if let error = viewModel.error {
                Section {
                    Text(error).foregroundStyle(.red)
                }
            } else if let article = viewModel.article {
                Section {
                    VStack(alignment: .leading, spacing: 12) {
                        HStack {
                            if let publishedAt = article.publishedAt {
                                Label(newsDateFormatter.string(from: publishedAt), systemImage: "calendar")
                                    .font(.footnote)
                                    .foregroundStyle(.secondary)
                            }
                            if let category = article.category, !category.isEmpty {
                                Spacer()
                                Text(category)
                                    .font(.caption)
                                    .padding(.horizontal, 8)
                                    .padding(.vertical, 4)
                                    .background(Color(.secondarySystemBackground))
                                    .clipShape(Capsule())
                            }
                        }
                        Text(article.title)
                            .font(.title2)
                            .fontWeight(.bold)
                        Text(article.excerpt)
                            .foregroundStyle(.secondary)
#if canImport(UIKit)
                        ShareLink(
                            item: newsShareURL(for: article),
                            subject: Text(article.title),
                            message: Text(article.excerpt)
                        ) {
                            Label("Partager l’actualité", systemImage: "square.and.arrow.up")
                                .fontWeight(.semibold)
                        }
#endif
                    }
                }

                Section("Contenu") {
                    Text(article.content)
                        .textSelection(.enabled)
                }

                Section("Commentaires") {
                    if let commentsError = viewModel.commentsError {
                        Text(commentsError).foregroundStyle(.red)
                    } else if viewModel.isLoadingComments && viewModel.comments.isEmpty {
                        ProgressView("Chargement des commentaires...")
                    } else if viewModel.comments.isEmpty {
                        Text("Aucun commentaire pour le moment.")
                            .foregroundStyle(.secondary)
                    } else {
                        ForEach(viewModel.comments) { comment in
                            VStack(alignment: .leading, spacing: 6) {
                                HStack {
                                    Text(comment.author.name)
                                        .fontWeight(.semibold)
                                    Spacer()
                                    Text(comment.createdAt.formatted(date: .abbreviated, time: .shortened))
                                        .font(.caption)
                                        .foregroundStyle(.secondary)
                                }
                                Text(comment.content)
                            }
                            .padding(.vertical, 6)
                        }
                    }

                    if viewModel.commentsTotalPages > 1 {
                        HStack {
                            Button("Précédents") {
                                viewModel.previousCommentsPage()
                                Task { await viewModel.loadComments() }
                            }
                            .disabled(viewModel.commentsPage <= 1 || viewModel.isLoadingComments)
                            Spacer()
                            Text("\(viewModel.commentsPage)/\(viewModel.commentsTotalPages)")
                                .font(.footnote)
                                .foregroundStyle(.secondary)
                            Spacer()
                            Button("Suivants") {
                                viewModel.nextCommentsPage()
                                Task { await viewModel.loadComments() }
                            }
                            .disabled(viewModel.commentsPage >= viewModel.commentsTotalPages || viewModel.isLoadingComments)
                        }
                    }

                    if account.isLoggedIn {
                        VStack(alignment: .leading, spacing: 8) {
                            Text("Ajouter un commentaire")
                                .fontWeight(.semibold)
                            TextEditor(text: $viewModel.newComment)
                                .frame(minHeight: 120)
                            Button {
                                Task { await viewModel.submitComment() }
                            } label: {
                                if viewModel.isSubmittingComment {
                                    ProgressView()
                                        .frame(maxWidth: .infinity)
                                } else {
                                    Text("Publier le commentaire")
                                        .fontWeight(.semibold)
                                        .frame(maxWidth: .infinity)
                                }
                            }
                            .disabled(viewModel.isSubmittingComment || viewModel.newComment.trimmingCharacters(in: .whitespacesAndNewlines).count < 3)
                        }
                    } else {
                        Text("Connectez-vous pour ajouter un commentaire.")
                            .foregroundStyle(.secondary)
                    }
                }
            }
        }
        .navigationTitle(viewModel.article?.title ?? "Actualité")
        .navigationBarTitleDisplayMode(.inline)
        .task {
            await viewModel.loadArticle()
            await viewModel.loadComments()
        }
    }
}

@MainActor
private final class NewsListViewModel: ObservableObject {
    @Published var articles: [NewsArticle] = []
    @Published var page = 1
    @Published var totalPages = 1
    @Published var searchText = ""
    @Published var appliedSearch = ""
    @Published var isLoading = false
    @Published var error: String?

    private let service: NewsServing

    init(service: NewsServing) {
        self.service = service
    }

    func applySearch() {
        appliedSearch = searchText.trimmingCharacters(in: .whitespacesAndNewlines)
        page = 1
    }

    func previousPage() { guard page > 1 else { return }; page -= 1 }
    func nextPage() { guard page < totalPages else { return }; page += 1 }

    func load() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            let data = try await service.newsArticles(page: page, perPage: 9, query: appliedSearch.isEmpty ? nil : appliedSearch)
            articles = data.items
            totalPages = max(1, data.meta.totalPages)
        } catch {
            self.error = error.localizedDescription
        }
    }
}

@MainActor
private final class NewsDetailViewModel: ObservableObject {
    @Published var article: NewsArticle?
    @Published var comments: [NewsComment] = []
    @Published var commentsPage = 1
    @Published var commentsTotalPages = 1
    @Published var isLoading = false
    @Published var isLoadingComments = false
    @Published var isSubmittingComment = false
    @Published var error: String?
    @Published var commentsError: String?
    @Published var newComment = ""

    private let service: NewsServing
    private let slug: String

    init(service: NewsServing, slug: String) {
        self.service = service
        self.slug = slug
    }

    func previousCommentsPage() { guard commentsPage > 1 else { return }; commentsPage -= 1 }
    func nextCommentsPage() { guard commentsPage < commentsTotalPages else { return }; commentsPage += 1 }

    func loadArticle() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            article = try await service.newsArticle(slug: slug)
        } catch {
            self.error = error.localizedDescription
        }
    }

    func loadComments() async {
        guard !isLoadingComments else { return }
        isLoadingComments = true
        commentsError = nil
        defer { isLoadingComments = false }

        do {
            let data = try await service.newsComments(slug: slug, page: commentsPage, perPage: 10)
            comments = data.items
            commentsTotalPages = max(1, data.meta.totalPages)
        } catch {
            self.commentsError = error.localizedDescription
        }
    }

    func submitComment() async {
        let content = newComment.trimmingCharacters(in: .whitespacesAndNewlines)
        guard content.count >= 3 else { return }
        guard !isSubmittingComment else { return }
        isSubmittingComment = true
        commentsError = nil
        defer { isSubmittingComment = false }

        do {
            _ = try await service.createNewsComment(slug: slug, content: content)
            newComment = ""
            commentsPage = 1
            await loadComments()
        } catch {
            commentsError = error.localizedDescription
        }
    }
}
