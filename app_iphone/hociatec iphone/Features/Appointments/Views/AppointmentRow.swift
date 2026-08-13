import SwiftUI

struct AppointmentRow: View {
    let appointment: AppointmentSummary
    var accentColor: Color = Color.gray.opacity(0.12)

    private var accessibilitySummary: String {
        var parts: [String] = []
        parts.append("Rendez-vous")
        parts.append(appointment.prestation.name)
        parts.append("le \(spokenDayFormatter.string(from: appointment.startAt))")
        parts.append("de \(timeFormatter.string(from: appointment.startAt)) à \(timeFormatter.string(from: appointment.endAt))")
        if let status = appointment.status, !status.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
            parts.append("Statut: \(status.capitalized)")
        }
        if appointment.canCancel {
            parts.append("Annulable")
        }
        return parts.joined(separator: ", ")
    }

    private var timeRange: String {
        "\(timeFormatter.string(from: appointment.startAt)) - \(timeFormatter.string(from: appointment.endAt))"
    }

    private var statusStyle: (text: String, color: Color) {
        guard let status = appointment.status else { return ("-", .gray) }
        let normalized = status.lowercased()
        if normalized.contains("annul") || normalized.contains("cancel") { return (status.capitalized, .red) }
        if normalized.contains("conf") { return (status.capitalized, .green) }
        if normalized.contains("att") || normalized.contains("pend") { return (status.capitalized, .orange) }
        return (status.capitalized, .gray)
    }

    var body: some View {
        HStack(alignment: .center, spacing: 12) {
            DateBadge(date: appointment.startAt)
            VStack(alignment: .leading, spacing: 6) {
                HStack {
                    Text(appointment.prestation.name)
                        .fontWeight(.semibold)
                        .lineLimit(1)
                    Spacer()
                    if appointment.status != nil {
                        Text(statusStyle.text)
                            .font(.caption2)
                            .padding(.horizontal, 8)
                            .padding(.vertical, 4)
                            .background(statusStyle.color.opacity(0.12))
                            .foregroundColor(statusStyle.color)
                            .clipShape(Capsule())
                    }
                }
                Text(dayFormatter.string(from: appointment.startAt))
                    .font(.subheadline)
                    .foregroundStyle(.secondary)
                Text(timeRange)
                    .font(.footnote)
                    .foregroundStyle(.secondary)
                if appointment.canCancel {
                    Label("Annulable", systemImage: "checkmark.shield")
                        .font(.caption2)
                        .padding(.horizontal, 6)
                        .padding(.vertical, 3)
                        .background(Color.orange.opacity(0.15))
                        .foregroundColor(.orange)
                        .clipShape(Capsule())
                }
            }
        }
        .padding(.vertical, 8)
        .padding(.horizontal, 4)
        .background(RoundedRectangle(cornerRadius: 12).fill(accentColor))
        .accessibilityElement(children: .ignore)
        .accessibilityLabel(Text(accessibilitySummary))
        .accessibilityHint("Touchez pour ouvrir les détails.")
    }
}

private struct DateBadge: View {
    let date: Date

    private var day: String { dayFormatter.string(from: date) }
    private var hour: String { timeFormatter.string(from: date) }

    var body: some View {
        VStack(spacing: 4) {
            Text(day)
                .font(.caption)
                .fontWeight(.semibold)
            Text(hour)
                .font(.footnote)
                .foregroundStyle(.secondary)
        }
        .padding(10)
        .background(RoundedRectangle(cornerRadius: 10).fill(Color.blue.opacity(0.1)))
        .accessibilityHidden(true)
    }
}
