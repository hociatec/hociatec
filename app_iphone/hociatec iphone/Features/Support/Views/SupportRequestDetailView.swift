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
            SupportRequestMetadataSection(item: item)
            SupportRequestInitialMessageSection(message: item.message)
            SupportRequestAttachmentsSection(
                attachments: item.attachments,
                requestId: requestId,
                viewModel: viewModel
            )
            SupportRequestTimelineSection(entries: item.timeline)
            SupportRequestReplySection(
                requestId: requestId,
                viewModel: viewModel,
                replySubject: $replySubject,
                replyMessage: $replyMessage,
                replyAttachmentURLs: $replyAttachmentURLs,
                showReplyFileImporter: $showReplyFileImporter
            )
        }
    }
}
