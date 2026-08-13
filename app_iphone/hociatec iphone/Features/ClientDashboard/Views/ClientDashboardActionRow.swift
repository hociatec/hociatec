import SwiftUI

struct ClientDashboardActionRow: View {
    let action: ClientDashboardAction

    var body: some View {
        VStack(alignment: .leading, spacing: 4) {
            Text(action.title)
                .fontWeight(.semibold)
            Text(action.detail)
                .font(.footnote)
                .foregroundStyle(.secondary)
        }
        .padding(.vertical, 4)
    }
}
