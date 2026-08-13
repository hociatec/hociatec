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
