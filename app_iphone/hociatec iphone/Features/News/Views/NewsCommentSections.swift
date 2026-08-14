import SwiftUI

struct NewsCommentsListSection: View {
    @ObservedObject var viewModel: NewsDetailViewModel

    var body: some View {
        if viewModel.isLoadingComments && viewModel.comments.isEmpty {
            ProgressView("Chargement des commentaires...")
        } else if viewModel.comments.isEmpty {
            Text("Aucun commentaire pour le moment.")
                .foregroundStyle(.secondary)
        } else {
            ForEach(viewModel.comments) { comment in
                NewsCommentRow(comment: comment)
            }
        }
    }
}

private struct NewsCommentRow: View {
    let comment: NewsComment

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            HStack {
                Text(comment.author.name)
                    .fontWeight(.semibold)
                Spacer()
                Text(NewsDetailPresentation.commentDate(comment.createdAt))
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }
            .accessibilityElement(children: .combine)
            .accessibilityLabel("Commentaire de \(comment.author.name). \(NewsDetailPresentation.commentDate(comment.createdAt))")
            Text(comment.content)
        }
        .padding(.vertical, 6)
        .accessibilityElement(children: .contain)
    }
}

struct NewsCommentsPaginationSection: View {
    @ObservedObject var viewModel: NewsDetailViewModel

    var body: some View {
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
    }
}

struct NewsCommentComposerSection: View {
    @ObservedObject var viewModel: NewsDetailViewModel
    let isLoggedIn: Bool

    var body: some View {
        if isLoggedIn {
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
                .disabled(
                    viewModel.isSubmittingComment
                        || !NewsDetailPresentation.canSubmitComment(viewModel.newComment)
                )
            }
        } else {
            Text("Connectez-vous pour ajouter un commentaire.")
                .foregroundStyle(.secondary)
        }
    }
}
