import SwiftUI

struct AppointmentRowContent: View {
    let appointment: AppointmentSummary
    let statusStyle: (text: String, color: Color)
    let timeRange: String

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            AppointmentRowHeader(
                title: appointment.prestation.name,
                statusText: appointment.status == nil ? nil : statusStyle.text,
                statusColor: statusStyle.color
            )
            Text(dayFormatter.string(from: appointment.startAt))
                .font(.subheadline)
                .foregroundStyle(.secondary)
            Text(timeRange)
                .font(.footnote)
                .foregroundStyle(.secondary)
            if appointment.canCancel {
                AppointmentRowBadge(
                    text: "Annulable",
                    systemImage: "checkmark.shield",
                    color: .orange
                )
            }
        }
    }
}

private struct AppointmentRowHeader: View {
    let title: String
    let statusText: String?
    let statusColor: Color

    var body: some View {
        HStack {
            Text(title)
                .fontWeight(.semibold)
                .lineLimit(1)
            Spacer()
            if let statusText {
                AppointmentRowStatusCapsule(text: statusText, color: statusColor)
            }
        }
    }
}

private struct AppointmentRowStatusCapsule: View {
    let text: String
    let color: Color

    var body: some View {
        Text(text)
            .font(.caption2)
            .padding(.horizontal, 8)
            .padding(.vertical, 4)
            .background(color.opacity(0.12))
            .foregroundColor(color)
            .clipShape(Capsule())
    }
}

private struct AppointmentRowBadge: View {
    let text: String
    let systemImage: String
    let color: Color

    var body: some View {
        Label(text, systemImage: systemImage)
            .font(.caption2)
            .padding(.horizontal, 6)
            .padding(.vertical, 3)
            .background(color.opacity(0.15))
            .foregroundColor(color)
            .clipShape(Capsule())
    }
}
