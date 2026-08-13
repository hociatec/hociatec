import SwiftUI

struct SupportRequestRow: View {
    let item: SupportRequestSummary

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(item.subject)
                .fontWeight(.semibold)
            Text(item.statusLabel)
                .font(.caption)
                .foregroundStyle(.secondary)
            if let orderNumber = item.order?.number, !orderNumber.isEmpty {
                Text("Commande \(orderNumber)")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
            Text(DateFormatters.frDateTime.string(from: item.updatedAt))
                .font(.footnote)
                .foregroundStyle(.secondary)
            if let awaiting = item.awaitingReplyLabel, !awaiting.isEmpty {
                Text(awaiting)
                    .font(.footnote)
                    .foregroundStyle(.blue)
            }
        }
        .padding(.vertical, 4)
    }
}
