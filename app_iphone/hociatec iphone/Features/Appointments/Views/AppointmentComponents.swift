import SwiftUI

private let timeFormatter: DateFormatter = {
    let formatter = DateFormatter()
    formatter.locale = Locale(identifier: "fr_FR")
    formatter.timeStyle = .short
    formatter.dateStyle = .none
    return formatter
}()

private let dayFormatter: DateFormatter = {
    let formatter = DateFormatter()
    formatter.locale = Locale(identifier: "fr_FR")
    formatter.dateFormat = "dd/MM/yyyy"
    return formatter
}()

private let spokenDayFormatter: DateFormatter = {
    let formatter = DateFormatter()
    formatter.locale = Locale(identifier: "fr_FR")
    formatter.dateStyle = .full
    formatter.timeStyle = .none
    return formatter
}()

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

struct AppointmentCard<Destination: View>: View {
    let appointment: AppointmentSummary
    var accentColor: Color = Color.gray.opacity(0.08)
    @ViewBuilder var destination: () -> Destination

    var body: some View {
        NavigationLink {
            destination()
        } label: {
            AppointmentRow(
                appointment: appointment,
                accentColor: accentColor
            )
        }
        .buttonStyle(.plain)
        .accessibilityHint("Ouvrir les détails du rendez-vous")
    }
}

struct AppointmentSummaryHeader: View {
    let upcomingCount: Int
    let pastCount: Int
    let cancelledCount: Int
    var isLoading: Bool

    var body: some View {
        VStack(alignment: .leading, spacing: 10) {
            HStack(spacing: 10) {
                SummaryPill(title: "À venir", value: upcomingCount, color: .blue)
                SummaryPill(title: "Passés", value: pastCount, color: .gray)
                SummaryPill(title: "Annulés", value: cancelledCount, color: .red)
            }
            .frame(maxWidth: .infinity, alignment: .leading)
            HStack(spacing: 6) {
                Image(systemName: isLoading ? "clock.arrow.2.circlepath" : "arrow.clockwise")
                    .foregroundStyle(.secondary)
                Text(isLoading ? "Mise à jour..." : "Glissez vers le bas pour rafraîchir")
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }
        }
        .padding(12)
        .background(RoundedRectangle(cornerRadius: 12).fill(Color.gray.opacity(0.08)))
        .listRowInsets(EdgeInsets())
        .listRowSeparator(.hidden)
        .accessibilityElement(children: .ignore)
        .accessibilityLabel("Résumé: \(upcomingCount) à venir, \(pastCount) passés, \(cancelledCount) annulés. \(isLoading ? "Mise à jour en cours" : "Tirez pour rafraîchir").")
    }
}

private struct SummaryPill: View {
    let title: String
    let value: Int
    let color: Color

    var body: some View {
        VStack(alignment: .leading, spacing: 4) {
            Text(title)
                .font(.caption)
                .foregroundStyle(.secondary)
            HStack(spacing: 4) {
                Circle().fill(color).frame(width: 8, height: 8)
                Text("\(value)")
                    .fontWeight(.semibold)
            }
        }
        .padding(.vertical, 8)
        .padding(.horizontal, 10)
        .background(RoundedRectangle(cornerRadius: 12).fill(color.opacity(0.08)))
    }
}

struct AppointmentEmptyState: View {
    let icon: String
    let message: String
    var action: (() async -> Void)?

    var body: some View {
        VStack(spacing: 10) {
            Image(systemName: icon)
                .font(.largeTitle)
                .foregroundStyle(.secondary)
                .accessibilityHidden(true)
            Text(message)
                .multilineTextAlignment(.center)
                .foregroundStyle(.secondary)
            if let action {
                Button {
                    Task { await action() }
                } label: {
                    Label("Actualiser", systemImage: "arrow.clockwise")
                }
                .buttonStyle(.bordered)
                .accessibilityHint("Actualiser la liste des rendez-vous")
            }
        }
        .padding(.vertical, 12)
    }
}

struct AppointmentSuccessBanner: View {
    let message: String

    var body: some View {
        Text(message)
            .font(.subheadline)
            .padding(.horizontal, 12)
            .padding(.vertical, 8)
            .background(Color.green.opacity(0.9))
            .foregroundStyle(.white)
            .clipShape(Capsule())
            .transition(.move(edge: .top).combined(with: .opacity))
            .accessibilityLabel(message)
            .accessibilityHidden(false)
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
