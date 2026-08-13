import SwiftUI

struct DateBadge: View {
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
