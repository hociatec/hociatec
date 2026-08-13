import SwiftUI

struct BetaReportDetailView: View {
    @ObservedObject var viewModel: BetaProgramViewModel
    let reportId: Int

    @State private var comment = ""

    var body: some View {
        List {
            if let report = viewModel.selectedReport, report.id == reportId {
                Section {
                    Text(report.title)
                        .font(.headline)
                    Text(viewModel.reportStatusLabel(for: report.status))
                        .font(.caption)
                        .foregroundStyle(.secondary)
                    Text(report.description)
                }

                Section("Commentaires") {
                    if viewModel.reportComments.isEmpty {
                        Text("Aucun commentaire pour le moment.")
                            .foregroundStyle(.secondary)
                    } else {
                        ForEach(viewModel.reportComments) { item in
                            VStack(alignment: .leading, spacing: 4) {
                                Text("\(item.author.firstName) \(item.author.lastName)")
                                    .fontWeight(.semibold)
                                Text(item.content)
                                Text(DateFormatters.frDateTime.string(from: item.createdAt))
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                            }
                            .padding(.vertical, 4)
                        }
                    }
                }

                Section("Répondre") {
                    TextEditor(text: $comment)
                        .frame(minHeight: 120)
                    Button("Envoyer") {
                        Task {
                            let success = await viewModel.postComment(reportId: reportId, content: comment)
                            if success {
                                comment = ""
                            }
                        }
                    }
                    .disabled(comment.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
                }
            } else if viewModel.isLoading {
                ProgressView("Chargement...")
            } else {
                Text("Signalement introuvable.")
                    .foregroundStyle(.secondary)
            }
        }
        .navigationTitle("Détail bêta")
        .task { await viewModel.loadReport(id: reportId) }
    }
}
