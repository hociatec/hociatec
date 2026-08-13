import SwiftUI
import UniformTypeIdentifiers

struct SupportRequestDetailView: View {
    @ObservedObject var viewModel: SupportViewModel
    let requestId: Int

    @State private var replySubject = ""
    @State private var replyMessage = ""
    @State private var replyAttachmentURLs: [URL] = []
    @State private var showReplyFileImporter = false

    var body: some View {
        Form {
            if let item = viewModel.selectedItem, item.id == requestId {
                SupportRequestDetailContent(
                    item: item,
                    viewModel: viewModel,
                    requestId: requestId,
                    replySubject: $replySubject,
                    replyMessage: $replyMessage,
                    replyAttachmentURLs: $replyAttachmentURLs,
                    showReplyFileImporter: $showReplyFileImporter
                )
            } else if viewModel.isLoading {
                ProgressView("Chargement...")
            } else {
                Text("Demande introuvable.")
                    .foregroundStyle(.secondary)
            }
        }
        .navigationTitle("Détail SAV")
        .task { await viewModel.loadDetail(id: requestId) }
        .fileImporter(
            isPresented: $showReplyFileImporter,
            allowedContentTypes: [.data, .pdf, .image],
            allowsMultipleSelection: true
        ) { result in
            if case let .success(urls) = result {
                replyAttachmentURLs.append(contentsOf: urls)
            }
        }
    }
}

private struct SupportRequestDetailContent: View {
    let item: SupportRequestSummary
    @ObservedObject var viewModel: SupportViewModel
    let requestId: Int

    @Binding var replySubject: String
    @Binding var replyMessage: String
    @Binding var replyAttachmentURLs: [URL]
    @Binding var showReplyFileImporter: Bool

    var body: some View {
        Group {
            metadataSection
            initialMessageSection
            attachmentsSection
            timelineSection
            replySection
        }
    }

    private var metadataSection: some View {
        Section {
            LabeledContent("Sujet") { Text(item.subject) }
            LabeledContent("Statut") { Text(item.statusLabel) }
            if let orderNumber = item.order?.number, !orderNumber.isEmpty {
                LabeledContent("Commande") { Text(orderNumber) }
            }
            LabeledContent("Créée le") { Text(DateFormatters.frDateTime.string(from: item.createdAt)) }
        }
    }

    @ViewBuilder
    private var initialMessageSection: some View {
        if let message = item.message, !message.isEmpty {
            Section("Message initial") {
                Text(message)
            }
        }
    }

    @ViewBuilder
    private var attachmentsSection: some View {
        if !item.attachments.isEmpty {
            Section("Pièces jointes") {
                ForEach(item.attachments) { attachment in
                    Button {
                        Task { await viewModel.shareAttachment(requestId: requestId, attachment: attachment) }
                    } label: {
                        VStack(alignment: .leading, spacing: 4) {
                            Text(attachment.originalName)
                                .frame(maxWidth: .infinity, alignment: .leading)
                            Text("\(attachment.contentType) · \(attachment.size) octets")
                                .font(.footnote)
                                .foregroundStyle(.secondary)
                                .frame(maxWidth: .infinity, alignment: .leading)
                        }
                    }
                    .buttonStyle(.plain)
                }
            }
        }
    }

    private var timelineSection: some View {
        Section("Historique") {
            ForEach(item.timeline) { entry in
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
    }

    private var replySection: some View {
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
