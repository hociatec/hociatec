import SwiftUI

struct AuditHistorySection: View {
    let events: [AuditEvent]

    var body: some View {
        if !events.isEmpty {
            Section("Historique") {
                ForEach(events) { event in
                    AuditHistoryRow(event: event)
                }
            }
        }
    }
}

private struct AuditHistoryRow: View {
    let event: AuditEvent

    var body: some View {
        VStack(alignment: .leading, spacing: 4) {
            Text(event.message ?? event.type)
            Text(DateFormatters.frDateTime.string(from: event.createdAt))
                .font(.caption)
                .foregroundStyle(.secondary)
        }
        .padding(.vertical, 2)
    }
}
