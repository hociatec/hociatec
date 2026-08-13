import SwiftUI

struct AuditChecklistSection: View {
    let items: [AuditChecklistItem]

    var body: some View {
        Section("Checklist") {
            ForEach(items.sorted(by: { $0.position < $1.position })) { item in
                AuditChecklistRow(item: item)
            }
        }
    }
}

private struct AuditChecklistRow: View {
    let item: AuditChecklistItem

    var body: some View {
        VStack(alignment: .leading, spacing: 4) {
            Text(item.label)
                .fontWeight(.semibold)
            Text(item.category)
                .font(.caption)
                .foregroundStyle(.secondary)

            if let level = item.level, !level.isEmpty {
                Text(level)
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }

            if let isCompliant = item.isCompliant {
                Text(isCompliant ? "Conforme" : "Non conforme")
                    .font(.footnote)
            } else {
                Text("À évaluer")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }

            if let comment = item.comment, !comment.isEmpty {
                Text(comment)
                    .font(.footnote)
            }
        }
        .padding(.vertical, 4)
    }
}
