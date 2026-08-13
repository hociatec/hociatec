import SwiftUI

struct AppointmentSummaryHeader: View {
    let allCount: Int
    let upcomingCount: Int
    let pastCount: Int
    var isLoading: Bool

    var body: some View {
        VStack(alignment: .leading, spacing: 10) {
            HStack(spacing: 10) {
                SummaryPill(title: "Tout", value: allCount, color: .blue)
                SummaryPill(title: "À venir", value: upcomingCount, color: .blue)
                SummaryPill(title: "Passés", value: pastCount, color: .gray)
            }
            .frame(maxWidth: .infinity, alignment: .leading)
        }
        .padding(12)
        .background(RoundedRectangle(cornerRadius: 12).fill(Color.gray.opacity(0.08)))
        .listRowInsets(EdgeInsets())
        .listRowSeparator(.hidden)
        .accessibilityElement(children: .ignore)
        .accessibilityLabel("Résumé: \(allCount) rendez-vous, \(upcomingCount) à venir, \(pastCount) passés\(isLoading ? ". Mise à jour en cours." : ".").")
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
