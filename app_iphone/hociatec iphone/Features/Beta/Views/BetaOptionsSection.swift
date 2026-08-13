import SwiftUI

struct BetaOptionsSection: View {
    let title: String
    let options: [BetaChoice]
    @Binding var selection: [String]

    var body: some View {
        Section(title) {
            if options.isEmpty {
                Text("Aucune option disponible.")
                    .foregroundStyle(.secondary)
            } else {
                ForEach(options) { option in
                    Button {
                        toggle(option.value)
                    } label: {
                        HStack {
                            Text(option.label)
                            Spacer()
                            if selection.contains(option.value) {
                                Image(systemName: "checkmark.circle.fill")
                                    .foregroundStyle(.accent)
                            }
                        }
                    }
                    .buttonStyle(.plain)
                }
            }
        }
    }

    private func toggle(_ value: String) {
        if selection.contains(value) {
            selection.removeAll(where: { $0 == value })
        } else {
            selection.append(value)
        }
    }
}
