import SwiftUI

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
