import SwiftUI

struct GlobalSearchEmptyRow: View {
    let message: String

    var body: some View {
        Text(message)
            .foregroundStyle(.secondary)
    }
}
