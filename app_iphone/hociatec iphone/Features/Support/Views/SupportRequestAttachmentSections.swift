import SwiftUI

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
