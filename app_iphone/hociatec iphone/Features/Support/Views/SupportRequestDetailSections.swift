import SwiftUI

struct SupportRequestMetadataSection: View {
    let item: SupportRequestSummary

    var body: some View {
        Section {
            LabeledContent("Sujet") { Text(item.subject) }
            LabeledContent("Statut") { Text(item.statusLabel) }
            if let orderNumber = item.order?.number, !orderNumber.isEmpty {
                LabeledContent("Commande") { Text(orderNumber) }
            }
            LabeledContent("Créée le") { Text(DateFormatters.frDateTime.string(from: item.createdAt)) }
        }
    }
}

struct SupportRequestInitialMessageSection: View {
    let message: String?

    var body: some View {
        if let message, !message.isEmpty {
            Section("Message initial") {
                Text(message)
            }
        }
    }
}

struct SupportRequestAttachmentsSection: View {
    let attachments: [SupportAttachment]
    let requestId: Int
    @ObservedObject var viewModel: SupportViewModel

    var body: some View {
        if !attachments.isEmpty {
            Section("Pièces jointes") {
                ForEach(attachments) { attachment in
                    Button {
                        Task { await viewModel.shareAttachment(requestId: requestId, attachment: attachment) }
                    } label: {
                        SupportRequestAttachmentRow(attachment: attachment)
                    }
                    .buttonStyle(.plain)
                }
            }
        }
    }
}

private struct SupportRequestAttachmentRow: View {
    let attachment: SupportAttachment

    var body: some View {
        VStack(alignment: .leading, spacing: 4) {
            Text(attachment.originalName)
                .frame(maxWidth: .infinity, alignment: .leading)
            Text("\(attachment.contentType) · \(attachment.size) octets")
                .font(.footnote)
                .foregroundStyle(.secondary)
                .frame(maxWidth: .infinity, alignment: .leading)
        }
    }
}

struct SupportRequestTimelineSection: View {
    let entries: [SupportTimelineEntry]

    var body: some View {
        Section("Historique") {
            ForEach(entries) { entry in
                SupportRequestTimelineRow(entry: entry)
            }
        }
    }
}

private struct SupportRequestTimelineRow: View {
    let entry: SupportTimelineEntry

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            HStack {
                Text(entry.authorLabel)
                    .fontWeight(.semibold)
                Spacer()
                Text(DateFormatters.frDateTime.string(from: entry.createdAt))
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }
            if let subject = entry.subject, !subject.isEmpty {
                Text(subject)
                    .font(.subheadline.weight(.medium))
            }
            if let message = entry.message, !message.isEmpty {
                Text(message)
                    .font(.footnote)
            }
            if let statusLabel = entry.statusLabel, !statusLabel.isEmpty {
                Text(statusLabel)
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }
        }
        .padding(.vertical, 4)
    }
}

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
