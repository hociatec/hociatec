import SwiftUI

struct SupportRequestReplySection: View {
    let requestId: Int
    @ObservedObject var viewModel: SupportViewModel
    @Binding var replySubject: String
    @Binding var replyMessage: String
    @Binding var replyAttachmentURLs: [URL]
    @Binding var showReplyFileImporter: Bool

    var body: some View {
        Section("Répondre") {
            TextField("Sujet optionnel", text: $replySubject)
            TextEditor(text: $replyMessage)
                .frame(minHeight: 120)
            Button("Ajouter des pièces jointes") {
                showReplyFileImporter = true
            }
            if !replyAttachmentURLs.isEmpty {
                ForEach(replyAttachmentURLs, id: \.absoluteString) { url in
                    Text(url.lastPathComponent)
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                }
            }
            Button("Envoyer la réponse") {
                Task {
                    let attachments = await loadSupportMultipartFiles(from: replyAttachmentURLs)
                    let success = await viewModel.reply(
                        id: requestId,
                        subject: replySubject.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty ? nil : replySubject,
                        message: replyMessage,
                        attachments: attachments
                    )
                    if success {
                        replySubject = ""
                        replyMessage = ""
                        replyAttachmentURLs = []
                    }
                }
            }
            .disabled(viewModel.isSubmitting || replyMessage.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
        }
    }
}
