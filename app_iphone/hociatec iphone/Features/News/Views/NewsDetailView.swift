import SwiftUI

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
