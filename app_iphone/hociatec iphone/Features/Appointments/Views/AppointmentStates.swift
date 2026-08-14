import SwiftUI

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
