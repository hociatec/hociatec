import SwiftUI

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
